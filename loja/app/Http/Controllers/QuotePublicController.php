<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\QuoteApprovedAlert;

class QuotePublicController extends Controller
{
    private function findQuote(string $token): Quote
    {
        return Quote::with(['items', 'customer'])
            ->where('public_token', $token)
            ->firstOrFail();
    }

    public function show(Request $request, string $token): View
    {
        $quote = $this->findQuote($token);

        // Visualização do cliente (admins logados não contam)
        if (! $request->user()) {
            $quote->markViewed([
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        }

        if ($quote->isExpired() && $quote->status !== 'expirado') {
            $quote->forceFill(['status' => 'expirado'])->saveQuietly();
        }

        return view('quote.public', [
            'quote' => $quote->fresh(['items', 'customer']),
        ]);
    }

    public function pdf(string $token): Response
    {
        $quote = $this->findQuote($token);

        $pdf = Pdf::loadView('quote.pdf', ['quote' => $quote])
            ->setPaper('a4');

        return $pdf->download('orcamento-'.$quote->number.'.pdf');
    }

    public function approve(Request $request, string $token): RedirectResponse
    {
        $quote = $this->findQuote($token);

        if ($quote->status !== 'aprovado' && ! $quote->isExpired()) {
            $quote->markApproved([
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            // Dispara e-mail de alerta para o administrador sobre a aprovação
            $adminEmail = Setting::get('store_email') ?: config('mail.from.address');
            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->send(new QuoteApprovedAlert($quote));
                } catch (\Exception $e) {
                    Log::error("Failed to send QuoteApprovedAlert: " . $e->getMessage());
                }
            }
        }

        $storePhone = preg_replace('/\D/', '', (string) Setting::get('store_whatsapp', ''));
        if ($storePhone && ! str_starts_with($storePhone, '55')) {
            $storePhone = '55'.$storePhone;
        }

        $message = "Olá! APROVO o orçamento {$quote->number} no valor de R$ "
            .number_format((float) $quote->total, 2, ',', '.')
            .'. Como seguimos?';

        return redirect()->away('https://wa.me/'.$storePhone.'?text='.rawurlencode($message));
    }
}
