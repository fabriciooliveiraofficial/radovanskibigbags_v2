<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #2b2b2b; padding: 24px; }
        .header { width: 100%; border-bottom: 3px solid #2E7D32; padding-bottom: 12px; margin-bottom: 16px; }
        .header td { vertical-align: middle; }
        .brand { font-size: 20px; font-weight: bold; color: #2E7D32; }
        .muted { color: #777; font-size: 10px; }
        .badge { background: #F5A623; color: #fff; font-weight: bold; padding: 4px 10px; border-radius: 6px; font-size: 13px; }
        .section-title { font-size: 10px; text-transform: uppercase; color: #999; font-weight: bold; margin: 14px 0 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.items th { background: #2b2b2b; color: #fff; padding: 6px 8px; text-align: left; font-size: 11px; }
        table.items th.num, table.items td.num { text-align: right; }
        table.items th.center, table.items td.center { text-align: center; }
        table.items td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        .totals { width: 260px; margin-left: auto; margin-top: 10px; }
        .totals td { padding: 3px 0; }
        .totals .label { color: #777; }
        .totals .value { text-align: right; font-weight: bold; }
        .grand { border-top: 2px solid #2b2b2b; font-size: 16px; color: #2E7D32; }
        .notes { background: #f7f7f7; border-radius: 8px; padding: 10px; margin-top: 14px; font-size: 11px; color: #555; }
        .footer { margin-top: 22px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 10px; color: #999; text-align: center; }
        .check { color: #2E7D32; font-weight: bold; }
        .infos { margin-top: 14px; font-size: 11px; }
        .infos td { padding: 2px 0; width: 50%; }
    </style>
</head>
<body>

<table class="header">
    <tr>
        <td>
            <div class="brand">{{ store_setting('store_name', 'Radovanski Big Bags') }}</div>
            <div class="muted">{{ store_setting('store_address', '') }} — {{ store_setting('store_city', 'Curitiba - PR') }}</div>
            <div class="muted">WhatsApp: {{ store_setting('store_whatsapp', '') }} @if(store_setting('store_cnpj')) · CNPJ: {{ store_setting('store_cnpj') }} @endif</div>
        </td>
        <td style="text-align: right;">
            <span class="badge">ORÇAMENTO</span>
            <div style="font-weight: bold; font-size: 14px; margin-top: 6px;">{{ $quote->number }}</div>
            <div class="muted">Emitido em {{ $quote->created_at->format('d/m/Y') }}</div>
            @if($quote->valid_until)
                <div class="muted">Válido até {{ $quote->valid_until->format('d/m/Y') }}</div>
            @endif
        </td>
    </tr>
</table>

<div class="section-title">Cliente</div>
<div>
    <strong>{{ $quote->customer?->name }}</strong>@if($quote->customer?->company) — {{ $quote->customer->company }}@endif<br>
    @if($quote->customer?->document)<span class="muted">CNPJ/CPF: {{ $quote->customer->document }}</span><br>@endif
    @if($quote->customer?->phone)<span class="muted">WhatsApp: {{ $quote->customer->phone }}</span>@endif
</div>

<div class="section-title">Itens</div>
<table class="items">
    <thead>
        <tr>
            <th style="width: 30px;">#</th>
            <th>Descrição</th>
            <th class="center" style="width: 50px;">Qtde</th>
            <th class="num" style="width: 90px;">Unitário</th>
            <th class="num" style="width: 90px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quote->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->description }}</td>
                <td class="center">{{ $item->qty }}</td>
                <td class="num">{{ format_brl($item->unit_price) }}</td>
                <td class="num">{{ format_brl($item->total) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td class="label">Subtotal</td><td class="value">{{ format_brl($quote->subtotal) }}</td></tr>
    @if($quote->discountAmount() > 0)
        <tr><td class="label">Desconto</td><td class="value" style="color: #c0392b;">− {{ format_brl($quote->discountAmount()) }}</td></tr>
    @endif
    <tr>
        <td class="label">{{ \App\Models\Quote::SHIPPING_METHODS[$quote->shipping_method] ?? 'Frete' }}@if($quote->shipping_carrier) ({{ $quote->shipping_carrier }})@endif</td>
        <td class="value">{{ $quote->shipping_method === 'retirada' ? 'Grátis' : format_brl($quote->shipping_cost) }}</td>
    </tr>
    @if($quote->shipping_deadline)
        <tr><td class="label">Prazo</td><td class="value">{{ $quote->shipping_deadline }}</td></tr>
    @endif
    <tr class="grand"><td style="font-weight: bold; padding-top: 6px;">TOTAL</td><td class="value grand" style="padding-top: 6px;">{{ format_brl($quote->total) }}</td></tr>
    @if($quote->payment_terms)
        <tr><td colspan="2" style="text-align: right; color: #d98c0f; font-weight: bold;">{{ $quote->payment_terms }}</td></tr>
    @endif
</table>

@if($quote->notes)
    <div class="notes"><strong>Observações:</strong><br>{!! nl2br(e($quote->notes)) !!}</div>
@endif

<table class="infos">
    <tr>
        <td><span class="check">✓</span> Confirmação imediata via WhatsApp</td>
        <td><span class="check">✓</span> Pagamento presencial na retirada/entrega</td>
    </tr>
    <tr>
        <td><span class="check">✓</span> Retirada em {{ store_setting('store_city', 'Curitiba - PR') }}</td>
        <td><span class="check">✓</span> Atendimento: {{ store_setting('store_hours', 'Seg a Sex 8h às 18h') }}</td>
    </tr>
</table>

<div class="footer">
    Para aprovar este orçamento, acesse: {{ $quote->publicUrl() }}<br>
    {{ store_setting('store_name', 'Radovanski Big Bags') }} — Curitiba/PR
</div>

</body>
</html>
