<?php

namespace App\Http\Controllers;

use App\Mail\NewCreditApplicationAlert;
use App\Models\CreditApplication;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CreditApplicationController extends Controller
{
    public function create(): View
    {
        return view('store.credit-application');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'trade_name' => ['nullable', 'string', 'max:150'],
            'document' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! cnpj_is_valid($value)) {
                    $fail('Informe um CNPJ válido.');
                }
            }],
            'state_registration' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'cep' => ['nullable', 'string', 'max:9'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:2'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'company_name.required' => 'Informe a razão social da empresa.',
            'document.required' => 'Informe o CNPJ da empresa.',
            'contact_name.required' => 'Informe o nome do responsável pelo contato.',
            'phone.required' => 'Informe um telefone/WhatsApp para contato.',
        ]);

        $data['document'] = preg_replace('/\D/', '', $data['document']);

        $creditApplication = CreditApplication::create([
            ...$data,
            'status' => 'pendente',
        ]);

        $adminEmail = Setting::get('store_email') ?: config('mail.from.address');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new NewCreditApplicationAlert($creditApplication));
            } catch (\Exception $e) {
                Log::error('Failed to send NewCreditApplicationAlert: '.$e->getMessage());
            }
        }

        return redirect()
            ->route('credit-application.create')
            ->with('status', 'Recebemos sua ficha cadastral! Nossa equipe avalia e retorna em breve pelo WhatsApp.');
    }
}
