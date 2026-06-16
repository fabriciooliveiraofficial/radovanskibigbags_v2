<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\Quote;
use App\Models\SmtpAccount;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class SmtpMailService
{
    public function send(
        Quote $quote,
        array $to,
        string $subject,
        string $body,
        bool $attachPdf = false,
        array $cc = [],
        ?SmtpAccount $account = null,
    ): EmailLog {
        $account ??= SmtpAccount::default();

        $log = EmailLog::create([
            'quote_id'        => $quote->id,
            'smtp_account_id' => $account?->id,
            'to_recipients'   => $to,
            'cc_recipients'   => $cc ?: null,
            'subject'         => $subject,
            'status'          => 'enviado',
        ]);

        try {
            $mailer = $account
                ? Mail::mailer($this->buildMailer($account))
                : Mail::mailer(config('mail.default'));

            $pixelUrl = $log->trackingPixelUrl();

            $mailer->send([], [], function (Message $msg) use (
                $to, $cc, $subject, $body, $attachPdf, $quote, $pixelUrl, $account
            ) {
                $fromAddress = $account?->from_address ?? config('mail.from.address');
                $fromName    = $account?->from_name    ?? config('mail.from.name');

                $msg->from($fromAddress, $fromName)
                    ->to($to)
                    ->subject($subject)
                    ->html($body.'<img src="'.e($pixelUrl).'" width="1" height="1" alt="" style="display:none">');

                if ($cc) {
                    $msg->cc($cc);
                }

                if ($attachPdf) {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('quote.pdf', ['quote' => $quote])->setPaper('a4');
                    $filename = 'pedido-'.$quote->number.'.pdf';
                    $msg->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
                }
            });

        } catch (\Throwable $e) {
            $log->forceFill(['status' => 'falhou', 'error' => $e->getMessage()])->save();
        }

        return $log->fresh();
    }

    private function buildMailer(SmtpAccount $account): string
    {
        $key = 'smtp_dynamic_'.$account->id;

        config(["mail.mailers.{$key}" => $account->mailerConfig()]);

        return $key;
    }
}
