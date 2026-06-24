<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

/* ── CABEÇALHO ── */
.header { width: 100%; border-bottom: 4px solid #1b5e20; padding-bottom: 14px; margin-bottom: 0; }
.header td { vertical-align: middle; }
.store-name { font-size: 18px; font-weight: bold; color: #1b5e20; }
.store-info { color: #666; font-size: 9px; line-height: 1.6; }
.doc-badge { background: #1b5e20; color: #fff; font-weight: bold; font-size: 11px;
             padding: 5px 12px; border-radius: 6px; display: inline-block; }
.doc-number { font-weight: bold; font-size: 16px; color: #1a1a1a; }
.doc-date { color: #888; font-size: 9px; }

/* ── BLOCOS DE SEÇÃO ── */
.section { margin-top: 14px; }
.section-title { font-size: 9px; text-transform: uppercase; font-weight: bold;
                 color: #fff; background: #2e7d32; padding: 3px 8px;
                 letter-spacing: 0.05em; border-radius: 3px; display: inline-block; }
.section-body { margin-top: 6px; padding: 0 2px; }
.label { color: #666; }

/* ── CLIENTE ── */
.customer-name { font-size: 13px; font-weight: bold; }
.customer-sub  { font-size: 9px; color: #555; line-height: 1.8; }

/* ── TABELA DE ITENS ── */
table.items { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10px; }
table.items thead tr { background: #1a1a1a; color: #fff; }
table.items thead th { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: bold; }
table.items thead th.r { text-align: right; }
table.items thead th.c { text-align: center; }
table.items tbody tr.even { background: #f5f5f5; }
table.items tbody td { padding: 6px 8px; border-bottom: 1px solid #e8e8e8; }
table.items tbody td.r { text-align: right; font-weight: bold; }
table.items tbody td.c { text-align: center; font-weight: bold; }
table.items tbody td.sub { font-size: 8px; color: #888; }

/* ── TOTAIS ── */
.totals-wrap { width: 260px; margin-left: auto; margin-top: 12px; }
.totals-wrap table { width: 100%; }
.totals-wrap td { padding: 2.5px 0; font-size: 10px; }
.totals-wrap .tlabel { color: #555; }
.totals-wrap .tval   { text-align: right; font-weight: bold; }
.totals-wrap .grand-row td { border-top: 3px solid #1a1a1a; padding-top: 6px; font-size: 15px; font-weight: bold; }
.totals-wrap .grand-row .tval { color: #1b5e20; font-size: 20px; }
.totals-wrap .payment-row td { text-align: right; color: #c07c00; font-weight: bold; padding-top: 2px; }

/* ── PESO/VOLUME ── */
.weight-line { font-size: 9px; color: #777; margin-top: 6px; }

/* ── BOTÕES (links clicáveis no PDF) ── */
.action-section { margin-top: 16px; border-top: 1px solid #e0e0e0; padding-top: 12px; }
.action-title { font-size: 10px; font-weight: bold; color: #333; margin-bottom: 8px; }
table.actions { width: 100%; border-collapse: collapse; }
table.actions td { width: 33%; padding: 0 4px 0 0; vertical-align: top; }
.btn { display: block; text-align: center; text-decoration: none; font-weight: bold; font-size: 9px;
       padding: 7px 4px; border-radius: 6px; line-height: 1.4; }
.btn-primary  { background: #1b5e20; color: #fff; }
.btn-wa       { background: #25d366; color: #fff; }
.btn-outline  { border: 1px solid #aaa; color: #333; background: #fff; }
.btn-brand    { background: #1b5e20; color: #fff; }

/* ── QR CODE ── */
.qr-cell { width: 110px; text-align: center; }
.qr-label { font-size: 8px; color: #888; margin-top: 3px; }

/* ── OBSERVAÇÕES ── */
.notes-box { background: #f7f7f7; border-radius: 6px; padding: 8px 10px; margin-top: 12px;
             font-size: 10px; color: #444; border-left: 3px solid #1b5e20; }

/* ── GARANTIAS ── */
.guarantees { margin-top: 14px; }
table.g-table { width: 100%; }
table.g-table td { padding: 3px 8px 3px 0; font-size: 10px; width: 50%; }
.check { color: #1b5e20; font-weight: bold; }

/* ── RODAPÉ ── */
.footer { margin-top: 18px; border-top: 1px solid #ddd; padding-top: 7px;
          font-size: 9px; color: #999; text-align: center; }
</style>
</head>
<body>

{{-- CABEÇALHO --}}
<table class="header">
    <tr>
        <td style="width:60%;">
            <div class="store-name">{{ store_setting('store_name', 'Radovanski Big Bags') }}</div>
            <div class="store-info">
                {{ store_setting('store_address', '') }} — {{ store_setting('store_city', 'Curitiba - PR') }}<br>
                WhatsApp: {{ store_setting('store_whatsapp', '') }}
                @if(store_setting('store_cnpj')) · CNPJ: {{ store_setting('store_cnpj') }}@endif
            </div>
        </td>
        <td style="text-align:right; width:40%;">
            <span class="doc-badge">PEDIDO</span>
            <div class="doc-number" style="margin-top:5px;">{{ $quote->number }}</div>
            <div class="doc-date">Emitido em {{ $quote->created_at->format('d/m/Y') }}</div>
            @if($quote->valid_until)
                <div class="doc-date">Válido até {{ $quote->valid_until->format('d/m/Y') }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- CLIENTE E ENTREGA --}}
<table style="width: 100%; border-collapse: collapse; margin-top: 14px; margin-bottom: 6px;">
    <tr>
        <td style="width: 50%; vertical-align: top; padding-right: 10px;">
            <span class="section-title">Cliente</span>
            <div class="section-body">
                <div class="customer-name">{{ $quote->customer?->name }}@if($quote->customer?->company) — {{ $quote->customer->company }}@endif</div>
                <div class="customer-sub">
                    @if($quote->customer?->document)CNPJ/CPF: {{ $quote->customer->document }}<br>@endif
                    @if($quote->customer?->phone)WhatsApp: {{ $quote->customer->phone }}@endif
                    @if($quote->customer?->city) · {{ $quote->customer->city }}@if($quote->customer?->state)/{{ $quote->customer->state }}@endif@endif
                </div>
            </div>
        </td>
        <td style="width: 50%; vertical-align: top; padding-left: 10px;">
            @if($quote->shipping_method !== 'retirada' && $quote->formatted_delivery_address)
                <span class="section-title">Endereço de Entrega</span>
                <div class="section-body">
                    <div class="customer-name" style="font-size: 11px; font-weight: normal; line-height: 1.4;">
                        @if($quote->google_maps_link)
                            <a href="{{ $quote->google_maps_link }}" target="_blank" style="color: #1b5e20; text-decoration: underline; font-weight: bold;">
                                {{ $quote->formatted_delivery_address }}
                            </a>
                        @else
                            {{ $quote->formatted_delivery_address }}
                        @endif
                    </div>
                </div>
            @endif
        </td>
    </tr>
</table>

{{-- ITENS --}}
<div class="section">
    <span class="section-title">Itens do pedido</span>
    <table class="items">
        <thead>
            <tr>
                <th style="width:20px;">#</th>
                <th>Produto/Atributos</th>
                <th class="c" style="width:45px;">Qtde</th>
                <th class="r" style="width:80px;">Unit.</th>
                <th class="r" style="width:80px;">Desconto</th>
                <th class="r" style="width:85px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $item)
            <tr class="{{ $loop->even ? 'even' : '' }}">
                <td style="color:#999;">{{ $loop->iteration }}</td>
                <td>
                    @php
                        $lines = explode("\n", $item->description);
                        $firstLine = count($lines) > 0 ? trim($lines[0]) : '';
                        if ($item->product_variant_id) {
                            $variant = \App\Models\ProductVariant::find($item->product_variant_id);
                            if ($variant) {
                                $firstLine = str_replace(' — ' . $variant->name, '', $firstLine);
                                $firstLine = str_replace(' - ' . $variant->name, '', $firstLine);
                            }
                        }
                    @endphp
                    @if($firstLine)
                        &bull; {{ $firstLine }}<br>
                    @endif
                    @if($item->product && $item->product->attributeValues->isNotEmpty())
                        @foreach($item->product->attributeValues as $val)
                            @php
                                $displayVal = $val->value;
                                if ($val->attribute->type === 'boolean') {
                                    $displayVal = in_array(strtolower(trim($val->value)), ['1', 'sim', 'yes', 'true']) ? 'Sim' : 'Não';
                                }
                            @endphp
                            &bull; {{ $val->attribute->name }}/{{ $displayVal }}{{ $val->attribute->unit ? ' ' . $val->attribute->unit : '' }}<br>
                        @endforeach
                    @else
                        @foreach(array_slice($lines, 1) as $line)
                            @if(trim($line))
                                &bull; {{ trim($line) }}<br>
                            @endif
                        @endforeach
                    @endif
                    @if($item->weight_kg)
                        <span class="sub">{{ number_format($item->weight_kg, 1) }} kg/un.</span>
                    @endif
                </td>
                <td class="c">{{ $item->qty }}</td>
                <td class="r" style="color:#555;">{{ format_brl($item->unit_price) }}</td>
                <td class="r" style="color:#1b7a2b;">
                    @if($item->discountAmount() > 0)
                        − {{ format_brl($item->discountAmount()) }}
                        <br><span class="sub">
                            ({{ $item->discount_type === 'percent'
                                ? rtrim(rtrim(number_format($item->discount_value, 2, ',', '.'), '0'), ',').'%'
                                : 'valor fixo' }})
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td class="r">{{ format_brl($item->total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($quote->total_weight_kg)
        <div class="weight-line">
            Peso total estimado: <strong>{{ number_format($quote->total_weight_kg, 0) }} kg</strong>
            @if($quote->totalVolumeCbm()) · Volume: <strong>{{ number_format($quote->totalVolumeCbm(), 2) }} m³</strong>@endif
        </div>
    @endif
</div>

{{-- TOTAIS --}}
<div class="totals-wrap">
    <table>
        <tr><td class="tlabel">Subtotal</td><td class="tval">{{ format_brl($quote->subtotal) }}</td></tr>
        @if($quote->discountAmount() > 0)
            <tr><td class="tlabel">Desconto</td><td class="tval" style="color:#1b7a2b;">− {{ format_brl($quote->discountAmount()) }}</td></tr>
        @endif
        <tr>
            <td class="tlabel">{{ \App\Models\Quote::SHIPPING_METHODS[$quote->shipping_method] ?? 'Frete' }}@if($quote->shipping_carrier) ({{ $quote->shipping_carrier }})@endif</td>
            <td class="tval">{{ $quote->shipping_method === 'retirada' ? 'Grátis' : format_brl($quote->shipping_cost) }}</td>
        </tr>
        @if($quote->delivery_days)
            <tr><td class="tlabel">Prazo</td><td class="tval">{{ $quote->delivery_days }} {{ $quote->delivery_days == 1 ? 'dia útil' : 'dias úteis' }}</td></tr>
        @elseif($quote->shipping_deadline)
            <tr><td class="tlabel">Prazo</td><td class="tval">{{ $quote->shipping_deadline }}</td></tr>
        @endif
        <tr class="grand-row"><td>TOTAL GERAL</td><td class="tval">{{ format_brl($quote->total) }}</td></tr>
        @if($quote->payment_terms)
            <tr class="payment-row"><td colspan="2">{{ $quote->payment_terms }}</td></tr>
        @endif
    </table>
</div>

{{-- OBSERVAÇÕES --}}
@if($quote->notes)
    <div class="notes-box"><strong>Observações:</strong><br>{!! nl2br(e($quote->notes)) !!}</div>
@endif

{{-- BOTÕES DE AÇÃO --}}
@php
    $storePhone = preg_replace('/\D/', '', store_setting('store_whatsapp', ''));
    if ($storePhone && !str_starts_with($storePhone, '55')) { $storePhone = '55'.$storePhone; }
    $waConfirm = rawurlencode('Olá! CONFIRMO o pedido '.$quote->number.' — '.format_brl($quote->total).'. Como seguimos?');
    $publicUrl = $quote->publicUrl();
    $repeatUrl = route('quote.repeat', $quote->public_token);
    $newOrderUrl = route('products.index');
    $boletoUrl = route('credit-application.create');
@endphp

<div class="action-section">
    <div class="action-title">Ações</div>
    <table class="actions">
        <tr>
            <td>
                <a href="{{ $publicUrl }}" class="btn btn-primary">
                    ✓ Confirmar pedido<br>(link online)
                </a>
            </td>
            <td>
                <a href="https://wa.me/{{ $storePhone }}?text={{ $waConfirm }}" class="btn btn-wa">
                    WhatsApp<br>Confirmar agora
                </a>
            </td>
            <td>
                <a href="{{ $repeatUrl }}" class="btn btn-outline">
                    🔁 Repetir este<br>pedido
                </a>
            </td>
            <td class="qr-cell" rowspan="2">
                {!! qr_svg($publicUrl, 90) !!}
                <div class="qr-label">Escanear para<br>abrir online</div>
            </td>
        </tr>
        <tr>
            <td>
                <a href="{{ $newOrderUrl }}" class="btn btn-outline" style="margin-top:4px;">
                    🛍️ Novo pedido
                </a>
            </td>
            <td>
                <a href="tel:{{ store_setting('store_whatsapp') }}" class="btn btn-outline" style="margin-top:4px;">
                    📞 Ligar
                </a>
            </td>
            <td>
                <a href="{{ $boletoUrl }}" class="btn btn-outline" style="margin-top:4px;">
                    🏢 Solicitar boleto
                </a>
            </td>
        </tr>
    </table>
</div>

{{-- GARANTIAS --}}
<div class="guarantees">
    <table class="g-table">
        <tr>
            <td><span class="check">✓</span> Confirmação imediata via WhatsApp</td>
            <td><span class="check">✓</span> Pagamento na retirada ou entrega</td>
        </tr>
        <tr>
            <td><span class="check">✓</span> Retirada em {{ store_setting('store_city', 'Curitiba - PR') }}</td>
            <td><span class="check">✓</span> {{ store_setting('store_hours', 'Seg a Sex 8h às 18h') }}</td>
        </tr>
    </table>
</div>

{{-- RODAPÉ --}}
<div class="footer">
    Acesse este pedido: {{ $publicUrl }}<br>
    {{ store_setting('store_name', 'Radovanski Big Bags') }} — Curitiba/PR
</div>

</body>
</html>
