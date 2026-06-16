<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    public function pixel(string $token): Response
    {
        $log = EmailLog::where('open_token', $token)->first();

        if ($log && $log->status === 'enviado') {
            $log->forceFill(['status' => 'aberto', 'opened_at' => now()])->save();
        }

        // 1×1 GIF transparente
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }
}
