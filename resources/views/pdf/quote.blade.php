<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Presupuesto {{ $quote->number }}</title>
    <style>
        @page { margin: 28mm 16mm 22mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1c1917; line-height: 1.45; margin: 0; }
        .header { position: fixed; top: -20mm; left: 0; right: 0; height: 16mm; border-bottom: 0.5pt solid #d6d3d1; }
        .footer { position: fixed; bottom: -14mm; left: 0; right: 0; height: 10mm;
                  border-top: 0.5pt solid #d6d3d1; padding-top: 3mm; font-size: 7.5pt; color: #78716c; }
        .muted { color: #78716c; }
        .right { text-align: right; }
        h1 { font-size: 16pt; margin: 0 0 2mm; }
        h2 { font-size: 10pt; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 1mm 0; vertical-align: top; }
        .chapter { background: #f5f5f4; font-weight: bold; }
        .chapter td { padding: 2mm 2.5mm; border-top: 0.5pt solid #d6d3d1; border-bottom: 0.5pt solid #d6d3d1; }
        .item td { padding: 2mm 2.5mm; border-bottom: 0.25pt solid #e7e5e4; vertical-align: top; }
        .item .desc { font-size: 8.5pt; color: #57534e; }
        .totals { width: 70mm; margin-left: auto; margin-top: 5mm; }
        .totals td { padding: 1.2mm 0; }
        .totals .grand td { border-top: 1pt solid #1c1917; font-size: 12pt; font-weight: bold; padding-top: 2mm; }
        .terms { margin-top: 8mm; font-size: 8.5pt; }
        .terms h3 { font-size: 9pt; margin: 0 0 1mm; }
        .terms div { margin-bottom: 4mm; }
        .sign { margin-top: 12mm; border: 0.5pt solid #d6d3d1; padding: 4mm; font-size: 8.5pt; }
        .badge { display: inline-block; padding: 0.8mm 2mm; background: #f5f5f4; border-radius: 2mm; font-size: 8pt; }
    </style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td>
                @if (! empty($company['logo']))
                    <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" style="max-height: 11mm; max-width: 45mm;">
                @else
                    <strong>{{ $company['name'] }}</strong>
                @endif
                <br>
                <span class="muted" style="font-size:8pt">
                    {{ $company['tax_id'] }}@if ($company['address']) · {{ $company['address'] }}@endif
                </span>
            </td>
            <td class="right muted" style="font-size:8pt">
                @if ($company['phone']) {{ $company['phone'] }}<br>@endif
                @if ($company['email']) {{ $company['email'] }}@endif
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <table>
        <tr>
            <td>{{ $company['name'] }} · Presupuesto {{ $quote->number }} v{{ $quote->version }}</td>
            <td class="right">Página <span class="pagenum"></span></td>
        </tr>
    </table>
</div>

<h1>Presupuesto {{ $quote->number }}</h1>
<p class="muted" style="margin:0 0 6mm">{{ $quote->title }}</p>

<table class="meta" style="margin-bottom:6mm">
    <tr>
        <td width="50%">
            <strong>Cliente</strong><br>
            {{ $quote->client->display_name }}<br>
            @if ($quote->client->tax_id) {{ $quote->client->tax_id }}<br>@endif
            <span class="muted">{{ $quote->client->full_address }}</span>
        </td>
        <td width="50%">
            <strong>Emplazamiento</strong><br>
            <span class="muted">{{ $quote->property?->full_address ?? 'Por determinar' }}</span><br><br>
            <strong>Fecha:</strong> {{ $quote->issue_date->format('d/m/Y') }}<br>
            @if ($quote->valid_until)
                <strong>Validez:</strong> {{ $quote->valid_until->format('d/m/Y') }}<br>
            @endif
            @if ($quote->estimated_duration_days)
                <strong>Duración estimada:</strong> {{ $quote->estimated_duration_days }} días
            @endif
        </td>
    </tr>
</table>

@if ($quote->description)
    <p style="margin-bottom:5mm">{{ $quote->description }}</p>
@endif

<table>
    @foreach ($quote->sections as $section)
        <tr class="chapter">
            <td colspan="3">{{ $loop->iteration }}. {{ $section->name }}</td>
            <td class="right">{{ money($section->subtotal) }}</td>
        </tr>

        @foreach ($section->items as $item)
            <tr class="item">
                <td width="52%">
                    {{ $item->name }}
                    @if ($item->is_optional)
                        <span class="badge">opcional{{ $item->is_included ? ' · incluida' : ' · no incluida' }}</span>
                    @endif
                    @if ($item->description)
                        <div class="desc">{{ $item->description }}</div>
                    @endif
                </td>
                <td width="16%" class="right">
                    {{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }} {{ $item->unit->label() }}
                </td>
                <td width="16%" class="right">{{ money($item->unit_price) }}</td>
                <td width="16%" class="right">
                    @if (! $item->is_optional || $item->is_included)
                        {{ money($item->total) }}
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
    @endforeach
</table>

<table class="totals">
    <tr><td>Suma de partidas</td><td class="right">{{ money($quote->items_total) }}</td></tr>
    @if ($quote->discount_amount > 0)
        <tr><td>Descuento</td><td class="right">-{{ money($quote->discount_amount) }}</td></tr>
    @endif
    <tr><td>Base imponible</td><td class="right">{{ money($quote->taxable_base) }}</td></tr>
    <tr><td>IVA ({{ (float) $quote->tax_rate }} %)</td><td class="right">{{ money($quote->tax_amount) }}</td></tr>
    <tr class="grand"><td>TOTAL</td><td class="right">{{ money($quote->total) }}</td></tr>
</table>

<div class="terms">
    @if ($quote->payment_terms)
        <div><h3>Forma de pago</h3>{!! nl2br(e($quote->payment_terms)) !!}</div>
    @endif
    @if ($quote->exclusions)
        <div><h3>No incluido en este presupuesto</h3>{!! nl2br(e($quote->exclusions)) !!}</div>
    @endif
    @if ($quote->terms)
        <div><h3>Condiciones generales</h3>{!! nl2br(e($quote->terms)) !!}</div>
    @endif
</div>

@if ($quote->status->value === 'approved')
    <div class="sign">
        <strong>Presupuesto aceptado</strong><br>
        Aceptado por {{ $quote->signer_name }} el {{ $quote->decided_at?->format('d/m/Y \a \l\a\s H:i') }}
        @if ($quote->decision_ip) (IP {{ $quote->decision_ip }})@endif.
    </div>
@else
    <div class="sign">
        <table>
            <tr>
                <td width="50%">Conforme, el cliente<br><br><br>________________________<br>
                    <span class="muted">Nombre, firma y fecha</span></td>
                <td width="50%">Por {{ $company['name'] }}<br><br><br>________________________<br>
                    <span class="muted">{{ $quote->author?->name }}</span></td>
            </tr>
        </table>
    </div>
@endif

<script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script('
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $pdf->text(185, 812, $PAGE_NUM . " / " . $PAGE_COUNT, $font, 7.5, [0.47, 0.44, 0.42]);
        ');
    }
</script>

</body>
</html>