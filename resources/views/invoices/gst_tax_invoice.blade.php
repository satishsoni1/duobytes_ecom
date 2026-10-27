@php
    $s = $invoice['seller'];
    $b = $invoice['buyer'];
    $sh = $invoice['ship_to'];
    $t = $invoice['totals'];
    $intra = $invoice['intra_state'];
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice['title'] }} {{ $invoice['number'] }}</title>
    <style>
        body { font-family: {!! $font_family ?? "'DejaVu Sans', Arial, sans-serif" !!}; font-size: 10px; color: #1f2933; margin: 0; background: #fff; }
        .sheet { max-width: 900px; margin: 0 auto; padding: 18px; }
        table { width: 100%; border-collapse: collapse; }
        .box td, .box th { border: 1px solid #9aa5b1; padding: 5px 6px; vertical-align: top; }
        .items th { background: #eef2f6; font-weight: bold; text-align: center; font-size: 9px; }
        .r { text-align: right; } .c { text-align: center; } .b { font-weight: bold; }
        .muted { color: #616e7c; }
        .title { font-size: 16px; font-weight: bold; letter-spacing: 1px; text-align: center; padding: 6px; background: #1f2933; color: #fff; }
        .copy { font-size: 9px; text-align: right; }
        .seller-name { font-size: 15px; font-weight: bold; }
        .label { font-size: 8.5px; text-transform: uppercase; color: #616e7c; font-weight: bold; }
        .grand td { font-size: 12px; font-weight: bold; background: #eef2f6; }
        .sign { height: 54px; }
        @media print { .no-print { display: none; } .sheet { padding: 0; } }
        .no-print { text-align: right; margin-bottom: 10px; }
        .no-print a, .no-print button { font: 13px Arial, sans-serif; padding: 7px 14px; border: 1px solid #1f2933; background: #1f2933; color: #fff; border-radius: 4px; text-decoration: none; cursor: pointer; margin-left: 6px; }
    </style>
</head>
<body>
<div class="sheet">
    @if (!empty($actions))
        <div class="no-print">
            @foreach ($actions as $label => $url)
                <a href="{{ $url }}">{{ $label }}</a>
            @endforeach
            <button type="button" onclick="window.print()">Print</button>
        </div>
    @endif

    <table>
        <tr>
            <td class="copy muted">Original for Recipient</td>
        </tr>
    </table>
    <div class="title">{{ strtoupper($invoice['title']) }}</div>

    <table class="box">
        <tr>
            <td style="width:55%" rowspan="3">
                <div class="seller-name">{{ $s['name'] }}</div>
                <div>{{ $s['address'] }}</div>
                <div>{{ $s['state'] }}{{ $s['postal_code'] ? ' - ' . $s['postal_code'] : '' }}</div>
                @if ($s['phone'] || $s['email'])
                    <div>{{ $s['phone'] }}{{ $s['phone'] && $s['email'] ? ' | ' : '' }}{{ $s['email'] }}</div>
                @endif
                <div style="margin-top:4px"><span class="b">GSTIN:</span> {{ $s['gstin'] ?: '—' }} &nbsp; <span class="b">State:</span> {{ $s['state'] }}</div>
            </td>
            <td><span class="label">Invoice No.</span><br><span class="b">{{ $invoice['number'] }}</span></td>
            <td><span class="label">Invoice Date</span><br><span class="b">{{ $invoice['date'] }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Order No.</span><br>{{ $invoice['order_code'] }}</td>
            <td><span class="label">{{ $invoice['po_number'] ? 'Buyer PO No.' : 'Reverse Charge' }}</span><br>{{ $invoice['po_number'] ?: $invoice['reverse_charge'] }}</td>
        </tr>
        <tr>
            <td><span class="label">Place of Supply</span><br>{{ $invoice['place_of_supply'] }}</td>
            <td><span class="label">Payment</span><br>{{ $invoice['payment'] }} ({{ $invoice['payment_status'] }})</td>
        </tr>
    </table>

    <table class="box" style="margin-top:-1px">
        <tr>
            <td style="width:50%">
                <div class="label">Bill To ({{ $b['type'] }}{{ $b['code'] ? ' · ' . $b['code'] : '' }})</div>
                <div class="b" style="font-size:11px">{{ $b['name'] }}</div>
                <div>{{ $b['address'] }}</div>
                <div>{{ trim($b['city'] . ', ' . $b['state'] . ' ' . $b['postal_code'], ', ') }}</div>
                @if ($b['phone'])<div>Ph: {{ $b['phone'] }}</div>@endif
                <div style="margin-top:3px">
                    <span class="b">GSTIN:</span> {{ $b['gstin'] ?: 'Unregistered' }}
                    @if ($b['pan']) &nbsp; <span class="b">PAN:</span> {{ $b['pan'] }} @endif
                </div>
            </td>
            <td style="width:50%">
                <div class="label">Ship To</div>
                <div class="b" style="font-size:11px">{{ $sh['name'] }}</div>
                <div>{{ $sh['address'] }}</div>
                <div>{{ trim($sh['city'] . ', ' . $sh['state'] . ' ' . $sh['postal_code'], ', ') }}</div>
                @if ($sh['phone'])<div>Ph: {{ $sh['phone'] }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="box items" style="margin-top:-1px">
        <thead>
            <tr>
                <th rowspan="2" style="width:4%">#</th>
                <th rowspan="2">Description of Goods</th>
                <th rowspan="2" style="width:9%">HSN/SAC</th>
                <th rowspan="2" style="width:7%">Qty</th>
                <th rowspan="2" style="width:9%">Rate</th>
                <th rowspan="2" style="width:11%">Taxable Value</th>
                @if ($intra)
                    <th colspan="2">CGST</th>
                    <th colspan="2">SGST</th>
                @else
                    <th colspan="2">IGST</th>
                @endif
                <th rowspan="2" style="width:11%">Total</th>
            </tr>
            <tr>
                @foreach ($intra ? [1, 2] : [1] as $_)
                    <th style="width:5%">Rate</th>
                    <th style="width:8%">Amount</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice['lines'] as $i => $line)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $line['description'] }}@if ($line['variation'])<br><span class="muted">{{ str_replace('-', ' / ', $line['variation']) }}</span>@endif</td>
                    <td class="c">{{ $line['hsn'] ?: '—' }}</td>
                    <td class="c">{{ $line['quantity'] }} {{ $line['unit'] }}</td>
                    <td class="r">{{ $money($line['rate']) }}</td>
                    <td class="r">{{ $money($line['taxable']) }}</td>
                    @if ($intra)
                        <td class="c">{{ $line['gst_rate'] / 2 }}%</td>
                        <td class="r">{{ $money($line['cgst']) }}</td>
                        <td class="c">{{ $line['gst_rate'] / 2 }}%</td>
                        <td class="r">{{ $money($line['sgst']) }}</td>
                    @else
                        <td class="c">{{ $line['gst_rate'] }}%</td>
                        <td class="r">{{ $money($line['igst']) }}</td>
                    @endif
                    <td class="r">{{ $money($line['total']) }}</td>
                </tr>
            @endforeach
            <tr class="b">
                <td colspan="3" class="r">Total</td>
                <td class="c">{{ $t['quantity'] }}</td>
                <td></td>
                <td class="r">{{ $money($t['taxable']) }}</td>
                @if ($intra)
                    <td></td><td class="r">{{ $money($t['cgst']) }}</td>
                    <td></td><td class="r">{{ $money($t['sgst']) }}</td>
                @else
                    <td></td><td class="r">{{ $money($t['igst']) }}</td>
                @endif
                <td class="r">{{ $money($t['taxable'] + $t['tax']) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="box" style="margin-top:-1px">
        <tr>
            <td style="width:60%">
                <div class="label">Amount Chargeable (in words)</div>
                <div class="b">{{ $invoice['amount_in_words'] }}</div>
                <div class="label" style="margin-top:6px">Tax Amount (in words)</div>
                <div>{{ $invoice['tax_in_words'] }}</div>
            </td>
            <td style="width:40%;padding:0">
                <table>
                    <tr><td style="border:0;border-bottom:1px solid #9aa5b1">Taxable Value</td><td class="r" style="border:0;border-bottom:1px solid #9aa5b1">{{ $money($t['taxable']) }}</td></tr>
                    @if ($intra)
                        <tr><td style="border:0;border-bottom:1px solid #9aa5b1">Add: CGST</td><td class="r" style="border:0;border-bottom:1px solid #9aa5b1">{{ $money($t['cgst']) }}</td></tr>
                        <tr><td style="border:0;border-bottom:1px solid #9aa5b1">Add: SGST</td><td class="r" style="border:0;border-bottom:1px solid #9aa5b1">{{ $money($t['sgst']) }}</td></tr>
                    @else
                        <tr><td style="border:0;border-bottom:1px solid #9aa5b1">Add: IGST</td><td class="r" style="border:0;border-bottom:1px solid #9aa5b1">{{ $money($t['igst']) }}</td></tr>
                    @endif
                    <tr><td style="border:0;border-bottom:1px solid #9aa5b1">Round Off</td><td class="r" style="border:0;border-bottom:1px solid #9aa5b1">{{ $t['round_off'] >= 0 ? '+' : '' }}{{ $money($t['round_off']) }}</td></tr>
                    <tr class="grand"><td style="border:0">Invoice Total (₹)</td><td class="r" style="border:0">{{ $money($t['grand']) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="box items" style="margin-top:-1px">
        <thead>
            <tr>
                <th>HSN/SAC</th>
                <th>Taxable Value</th>
                @if ($intra)
                    <th>CGST Rate</th><th>CGST Amount</th><th>SGST Rate</th><th>SGST Amount</th>
                @else
                    <th>IGST Rate</th><th>IGST Amount</th>
                @endif
                <th>Total Tax</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice['hsn_summary'] as $row)
                <tr>
                    <td class="c">{{ $row['hsn'] ?: '—' }}</td>
                    <td class="r">{{ $money($row['taxable']) }}</td>
                    @if ($intra)
                        <td class="c">{{ $row['rate'] / 2 }}%</td><td class="r">{{ $money($row['cgst']) }}</td>
                        <td class="c">{{ $row['rate'] / 2 }}%</td><td class="r">{{ $money($row['sgst']) }}</td>
                    @else
                        <td class="c">{{ $row['rate'] }}%</td><td class="r">{{ $money($row['igst']) }}</td>
                    @endif
                    <td class="r">{{ $money($row['cgst'] + $row['sgst'] + $row['igst']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="box" style="margin-top:-1px">
        <tr>
            <td style="width:60%">
                @if (!empty($invoice['bank']))
                    <div class="label">Bank Details</div>
                    <div>Bank: {{ $invoice['bank']['name'] }} &nbsp; A/c No.: {{ $invoice['bank']['account'] }}</div>
                    <div>IFSC: {{ $invoice['bank']['ifsc'] }} &nbsp; Branch: {{ $invoice['bank']['branch'] }}</div>
                @endif
                <div class="label" style="margin-top:6px">Declaration</div>
                <div>We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.</div>
                @if ($invoice['footer'])<div class="muted" style="margin-top:4px">{{ $invoice['footer'] }}</div>@endif
            </td>
            <td style="width:40%" class="r">
                <div class="b">For {{ $s['name'] }}</div>
                <div class="sign"></div>
                <div>Authorised Signatory</div>
            </td>
        </tr>
    </table>
    <p class="c muted" style="margin-top:6px;font-size:8.5px">This is a computer-generated invoice.</p>
</div>
</body>
</html>
