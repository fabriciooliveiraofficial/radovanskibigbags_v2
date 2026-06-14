<?php

use App\Models\Setting;

if (! function_exists('store_setting')) {
    function store_setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('store_whatsapp_link')) {
    /** Link wa.me para o WhatsApp da loja com mensagem opcional */
    function store_whatsapp_link(string $message = ''): string
    {
        $phone = preg_replace('/\D/', '', (string) Setting::get('store_whatsapp', ''));

        if ($phone && ! str_starts_with($phone, '55')) {
            $phone = '55'.$phone;
        }

        $url = 'https://wa.me/'.$phone;

        return $message !== '' ? $url.'?text='.rawurlencode($message) : $url;
    }
}

if (! function_exists('qr_svg')) {
    /** Gera QR code SVG inline (sem dependência externa de imagem) */
    function qr_svg(string $text, int $size = 160): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, 0),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );

        return (new \BaconQrCode\Writer($renderer))->writeString($text);
    }
}

if (! function_exists('format_brl')) {
    function format_brl(float|string|null $value): string
    {
        if ($value === null) {
            return 'Sob consulta';
        }

        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }
}
