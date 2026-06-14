<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta: Nova Solicitação de Orçamento</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2b2b2b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e1e4e8;">
        <!-- Header -->
        <tr>
            <td style="background-color: #2e7d32; padding: 25px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 0.5px;">ALERTA DE SISTEMA</h1>
                <p style="color: #e8f5e9; margin: 5px 0 0 0; font-size: 13px;">Nova solicitação de cotação via WhatsApp/Site</p>
            </td>
        </tr>
        
        <!-- Content -->
        <tr>
            <td style="padding: 30px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.5; color: #555555;">
                    Uma nova solicitação de cotação foi registrada no banco de dados da loja. O cliente foi redirecionado para o WhatsApp da empresa.
                </p>
                
                <!-- Customer Details -->
                <h3 style="color: #2e7d32; margin: 0 0 10px 0; font-size: 16px; border-bottom: 2px solid #e8f5e9; padding-bottom: 5px;">Dados do Lead</h3>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px; font-size: 14px; line-height: 1.6;">
                    <tr>
                        <td width="30%" style="color: #888888; padding: 4px 0;">Nome:</td>
                        <td style="color: #2b2b2b; font-weight: bold; padding: 4px 0;">{{ $quoteRequest->name ?? 'Não informado' }}</td>
                    </tr>
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">WhatsApp:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">
                            @if($quoteRequest->phone)
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $quoteRequest->phone) }}" style="color: #25d366; font-weight: bold; text-decoration: none;">
                                    {{ $quoteRequest->phone }} ↗
                                </a>
                            @else
                                Não informado
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">Cidade:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">{{ $quoteRequest->city ?? 'Não informado' }}</td>
                    </tr>
                    <tr>
                        <td style="color: #888888; padding: 4px 0;">Registrado em:</td>
                        <td style="color: #2b2b2b; padding: 4px 0;">{{ $quoteRequest->created_at?->timezone('America/Sao_Paulo')?->format('d/m/Y H:i') ?? now()->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
                
                <!-- Requested Items -->
                <h3 style="color: #2e7d32; margin: 0 0 10px 0; font-size: 16px; border-bottom: 2px solid #e8f5e9; padding-bottom: 5px;">Itens Solicitados</h3>
                @php
                    $items = collect($quoteRequest->items)->map(function ($item) {
                        $product = \App\Models\Product::find($item['product_id']);
                        $variant = !empty($item['variant_id']) ? \App\Models\ProductVariant::find($item['variant_id']) : null;
                        return [
                            'product' => $product,
                            'variant' => $variant,
                            'qty' => $item['qty']
                        ];
                    })->filter(fn($i) => $i['product'] !== null);
                @endphp
                
                @if($items->isNotEmpty())
                    <table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #e1e4e8; border-collapse: collapse; font-size: 14px; text-align: left; margin-bottom: 25px;">
                        <thead>
                            <tr style="background-color: #f4f6f8;">
                                <th style="border: 1px solid #e1e4e8; font-weight: bold; color: #2b2b2b;">Produto</th>
                                <th style="border: 1px solid #e1e4e8; font-weight: bold; color: #2b2b2b; text-align: center; width: 80px;">Qtd</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $i)
                                <tr>
                                    <td style="border: 1px solid #e1e4e8; color: #555555;">
                                        <strong>{{ $i['product']->name }}</strong>
                                        @if($i['variant'])
                                            <br><span style="font-size: 12px; color: #888888;">Variação: {{ $i['variant']->name }}</span>
                                        @endif
                                    </td>
                                    <td style="border: 1px solid #e1e4e8; color: #555555; text-align: center; font-weight: bold;">
                                        {{ $i['qty'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: #888888; font-style: italic; margin-bottom: 25px;">Nenhum produto listado ou não localizado no banco.</p>
                @endif
                
                <!-- CTA to admin panel -->
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center">
                            <a href="{{ url('/admin') }}" style="display: inline-block; background-color: #2e7d32; color: #ffffff; padding: 12px 24px; font-size: 14px; font-weight: bold; text-decoration: none; border-radius: 6px; box-shadow: 0 2px 4px rgba(46,125,50,0.2);">
                                Acessar Painel Administrativo
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
