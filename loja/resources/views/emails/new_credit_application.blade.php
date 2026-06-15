<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta: Nova Ficha Cadastral</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2b2b2b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e1e4e8;">
        <!-- Header -->
        <tr>
            <td style="background-color: #2e7d32; padding: 25px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 0.5px;">ALERTA DE SISTEMA</h1>
                <p style="color: #e8f5e9; margin: 5px 0 0 0; font-size: 13px;">Nova ficha cadastral B2B recebida pelo site</p>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 30px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.5; color: #555555;">
                    Uma empresa preencheu a ficha cadastral para liberação de pagamento via boleto. Avalie os dados abaixo e aprove ou reprove no painel.
                </p>

                <h3 style="color: #2e7d32; margin: 0 0 10px 0; font-size: 16px; border-bottom: 2px solid #e8f5e9; padding-bottom: 5px;">Dados da Empresa</h3>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px; font-size: 14px; line-height: 1.6;">
                    <tr>
                        <td width="30%" style="color: #888888; padding: 4px 0;">Razão social:</td>
                        <td style="color: #2b2b2b; font-weight: bold; padding: 4px 0;">{{ $creditApplication->company_name }}</td>
                    </tr>
                    @if($creditApplication->trade_name)
                        <tr>
                            <td style="color: #888888; padding: 4px 0;">Nome fantasia:</td>
                            <td style="color: #2b2b2b; padding: 4px 0;">{{ $creditApplication->trade_name }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">CNPJ:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">{{ $creditApplication->document }}</td>
                    </tr>
                    @if($creditApplication->state_registration)
                        <tr>
                            <td style="color: #888888; padding: 4px 0;">Inscrição estadual:</td>
                            <td style="color: #2b2b2b; padding: 4px 0;">{{ $creditApplication->state_registration }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">Contato:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">{{ $creditApplication->contact_name }}</td>
                    </tr>
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">WhatsApp:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $creditApplication->phone) }}" style="color: #25d366; font-weight: bold; text-decoration: none;">
                                {{ $creditApplication->phone }} ↗
                            </a>
                        </td>
                    </tr>
                    @if($creditApplication->email)
                        <tr>
                            <td style="color: #888888; padding: 4px 0;">E-mail:</td>
                            <td style="color: #2b2b2b; padding: 4px 0;">{{ $creditApplication->email }}</td>
                        </tr>
                    @endif
                    @if($creditApplication->address || $creditApplication->city)
                        <tr>
                            <td style="color: #888888; padding: 4px 0;">Endereço:</td>
                            <td style="color: #2b2b2b; padding: 4px 0;">
                                {{ implode(', ', array_filter([$creditApplication->address, $creditApplication->city, $creditApplication->state])) }}
                                @if($creditApplication->cep) — CEP {{ $creditApplication->cep }} @endif
                            </td>
                        </tr>
                    @endif
                    @if($creditApplication->notes)
                        <tr>
                            <td style="color: #888888; padding: 4px 0; vertical-align: top;">Observações:</td>
                            <td style="color: #2b2b2b; padding: 4px 0;">{{ $creditApplication->notes }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">Registrado em:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">{{ $creditApplication->created_at?->timezone('America/Sao_Paulo')?->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>

                <!-- CTA to admin panel -->
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center">
                            <a href="{{ url('/admin/credit-applications') }}" style="display: inline-block; background-color: #2e7d32; color: #ffffff; padding: 12px 24px; font-size: 14px; font-weight: bold; text-decoration: none; border-radius: 6px; box-shadow: 0 2px 4px rgba(46,125,50,0.2);">
                                Avaliar no Painel Administrativo
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
