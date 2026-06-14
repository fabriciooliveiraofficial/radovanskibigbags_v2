<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento {{ $quote->number }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2b2b2b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e1e4e8;">
        <!-- Header -->
        <tr>
            <td style="background-color: #2e7d32; padding: 30px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 0.5px;">{{ store_setting('store_name', 'Radovanski Big Bags') }}</h1>
                <p style="color: #e8f5e9; margin: 5px 0 0 0; font-size: 14px;">Embalagens e Big Bags de Alta Qualidade</p>
            </td>
        </tr>
        
        <!-- Content -->
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="color: #2b2b2b; margin: 0 0 20px 0; font-size: 20px; font-weight: 600;">Olá, {{ $quote->customer->name }}!</h2>
                
                @if($quote->customer->company)
                    <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6; color: #555555;">Representando a empresa <strong>{{ $quote->customer->company }}</strong>,</p>
                @endif
                
                <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6; color: #555555;">
                    Enviamos em anexo a esta mensagem a cópia em PDF do seu orçamento <strong>{{ $quote->number }}</strong>.
                </p>
                
                <!-- Quote Box -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fbf9; border-left: 4px solid #2e7d32; border-radius: 4px; margin: 0 0 30px 0; padding: 20px;">
                    <tr>
                        <td style="font-size: 15px; color: #555555; padding-bottom: 8px;">Número do orçamento:</td>
                        <td style="font-size: 15px; font-weight: bold; color: #2b2b2b; text-align: right; padding-bottom: 8px;">{{ $quote->number }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 15px; color: #555555; padding-bottom: 8px;">Validade:</td>
                        <td style="font-size: 15px; font-weight: bold; color: #f5a623; text-align: right; padding-bottom: 8px;">{{ $quote->valid_until?->format('d/m/Y') }}</td>
                    </tr>
                    @if($quote->shipping_method)
                        <tr>
                            <td style="font-size: 15px; color: #555555; padding-bottom: 8px;">Frete/Entrega:</td>
                            <td style="font-size: 15px; font-weight: bold; color: #2b2b2b; text-align: right; padding-bottom: 8px;">
                                {{ \App\Models\Quote::SHIPPING_METHODS[$quote->shipping_method] ?? $quote->shipping_method }}
                            </td>
                        </tr>
                    @endif
                    <tr style="border-top: 1px solid #e2ebd5;">
                        <td style="font-size: 18px; font-weight: bold; color: #2b2b2b; padding-top: 12px;">Total:</td>
                        <td style="font-size: 20px; font-weight: bold; color: #2e7d32; text-align: right; padding-top: 12px;">{{ format_brl($quote->total) }}</td>
                    </tr>
                </table>
                
                <!-- CTA -->
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center">
                            <a href="{{ $quote->publicUrl() }}" style="display: inline-block; background-color: #2e7d32; color: #ffffff; padding: 14px 28px; font-size: 16px; font-weight: bold; text-decoration: none; border-radius: 6px; box-shadow: 0 2px 4px rgba(46,125,50,0.2); transition: background-color 0.2s;">
                                Visualizar e Aceitar Orçamento
                            </a>
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 30px 0 0 0; font-size: 14px; line-height: 1.6; color: #888888; text-align: center;">
                    Você também pode responder diretamente a este e-mail ou entrar em contato pelo WhatsApp caso tenha qualquer dúvida ou deseje negociar.
                </p>
            </td>
        </tr>
        
        <!-- Footer -->
        <tr>
            <td style="background-color: #f4f6f8; padding: 25px 30px; text-align: center; border-top: 1px solid #e1e4e8;">
                <p style="margin: 0; font-size: 14px; font-weight: bold; color: #2b2b2b;">{{ store_setting('store_name', 'Radovanski Big Bags') }}</p>
                @if(store_setting('store_cnpj'))
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #888888;">CNPJ: {{ store_setting('store_cnpj') }}</p>
                @endif
                @if(store_setting('store_address'))
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #888888;">{{ store_setting('store_address') }}</p>
                @endif
                <table align="center" border="0" cellpadding="0" cellspacing="0" style="margin-top: 15px;">
                    <tr>
                        @if(store_setting('store_whatsapp'))
                            <td style="padding: 0 10px;">
                                <a href="{{ store_whatsapp_link() }}" style="color: #25d366; font-size: 13px; font-weight: bold; text-decoration: none;">WhatsApp</a>
                            </td>
                        @endif
                        @if(store_setting('store_email'))
                            <td style="padding: 0 10px;">
                                <a href="mailto:{{ store_setting('store_email') }}" style="color: #2e7d32; font-size: 13px; font-weight: bold; text-decoration: none;">E-mail</a>
                            </td>
                        @endif
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
