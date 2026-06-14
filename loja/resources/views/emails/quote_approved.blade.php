<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento Aprovado pelo Cliente</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2b2b2b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e1e4e8;">
        <!-- Header -->
        <tr>
            <td style="background-color: #2e7d32; padding: 25px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 0.5px;">ORÇAMENTO APROVADO</h1>
                <p style="color: #e8f5e9; margin: 5px 0 0 0; font-size: 13px;">O cliente aceitou e aprovou os valores</p>
            </td>
        </tr>
        
        <!-- Content -->
        <tr>
            <td style="padding: 30px;">
                <h2 style="color: #2e7d32; margin: 0 0 15px 0; font-size: 18px; font-weight: bold; text-align: center;">
                    Orçamento {{ $quote->number }} Aprovado!
                </h2>
                
                <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.5; color: #555555; text-align: center;">
                    O cliente clicou em "Aprovar Orçamento" e foi direcionado para finalizar no WhatsApp.
                </p>
                
                <!-- Quote Details -->
                <h3 style="color: #2e7d32; margin: 0 0 10px 0; font-size: 16px; border-bottom: 2px solid #e8f5e9; padding-bottom: 5px;">Detalhes do Orçamento</h3>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px; font-size: 14px; line-height: 1.6;">
                    <tr>
                        <td width="30%" style="color: #888888; padding: 4px 0;">Número:</td>
                        <td style="color: #2b2b2b; font-weight: bold; padding: 4px 0;">{{ $quote->number }}</td>
                    </tr>
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">Cliente:</td>
                        <td style="color: #2b2b2b; font-weight: bold; padding: 4px 0;">
                            {{ $quote->customer?->name }} 
                            @if($quote->customer?->company)
                                ({{ $quote->customer->company }})
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">WhatsApp:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">
                            @if($quote->customer?->phone)
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $quote->customer->phone) }}" style="color: #25d366; font-weight: bold; text-decoration: none;">
                                    {{ $quote->customer->phone }} ↗
                                </a>
                            @else
                                Não informado
                            @endif
                        </td>
                    </tr>
                    @if($quote->customer?->email)
                        <tr>
                            <td style="color: #888888; padding: 4px 0;">E-mail:</td>
                            <td style="color: #2b2b2b; padding: 4px 0;">
                                <a href="mailto:{{ $quote->customer->email }}" style="color: #2e7d32; text-decoration: none;">
                                    {{ $quote->customer->email }}
                                </a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">Valor Aprovado:</td>
                        <td style="color: #2e7d32; font-weight: bold; font-size: 16px; padding: 4px 0;">{{ format_brl($quote->total) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">Aprovado em:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">{{ $quote->approved_at?->timezone('America/Sao_Paulo')?->format('d/m/Y H:i') ?? now()->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
                
                <!-- CTA to admin edit -->
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center">
                            <a href="{{ url('/admin/quotes/' . $quote->id . '/edit') }}" style="display: inline-block; background-color: #2e7d32; color: #ffffff; padding: 12px 24px; font-size: 14px; font-weight: bold; text-decoration: none; border-radius: 6px; box-shadow: 0 2px 4px rgba(46,125,50,0.2);">
                                Editar Orçamento no Painel
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- Footer -->
        <tr>
            <td style="background-color: #f4f6f8; padding: 20px; text-align: center; border-top: 1px solid #e1e4e8; font-size: 12px; color: #888888;">
                Este é um alerta automático gerado pelo site radovanskibigbags.com.br.
            </td>
        </tr>
    </table>
</body>
</html>
