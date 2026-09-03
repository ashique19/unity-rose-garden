<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS bills — {{ $selectedMonth->format('M Y') }}</title>
    <style>
        /* ~80mm thermal receipt width */
        @page {
            size: 80mm auto;
            margin: 2mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            font-weight: bold;
            line-height: 1.35;
            color: #000;
            width: 72mm;
            max-width: 72mm;
            margin: 0 auto;
            padding: 8px 4px 16px;
            background: #fff;
        }
        .toolbar {
            width: min(420px, 92vw);
            margin: 0 auto 12px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            font-family: system-ui, sans-serif;
            font-size: 13px;
            font-weight: normal;
        }
        .toolbar a, .toolbar button, .toolbar select {
            padding: 8px 12px;
            font: inherit;
            cursor: pointer;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .receipt-header .brand {
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .receipt-header .sub {
            font-size: 11px;
        }
        .sep {
            border: 0;
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .sep-double {
            border: 0;
            border-top: 2px solid #000;
            margin: 8px 0;
        }
        .flat-slip {
            break-inside: avoid;
            page-break-inside: avoid;
            page-break-after: always;
            margin-bottom: 10px;
            padding-bottom: 6px;
        }
        .flat-slip:last-of-type {
            page-break-after: auto;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: flex-start;
        }
        .row .label { flex: 1 1 auto; word-break: break-word; }
        .row .value {
            flex: 0 0 auto;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
        .flat-title {
            font-weight: bold;
            font-size: 13px;
            text-align: center;
            margin: 0 0 4px;
        }
        .section {
            font-weight: bold;
            margin: 6px 0 2px;
            text-transform: uppercase;
            font-size: 11px;
        }
        .muted { opacity: 0.75; }
        .total-row {
            font-weight: bold;
            font-size: 24px;
            line-height: 1.2;
            margin-top: 6px;
        }
        .empty { text-align: center; padding: 24px 0; }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 8px;
        }
        @media print {
            .no-print { display: none !important; }
            body {
                width: 72mm;
                max-width: 72mm;
                margin: 0;
                padding: 0;
            }
        }
        @media screen {
            body {
                border-left: 1px dashed #ccc;
                border-right: 1px dashed #ccc;
                min-height: 100vh;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print POS</button>
        <a href="{{ route('public.statements.print-building', ['month' => $selectedMonth->format('Y-m')]) }}">A4 print</a>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('home') }}">Home</a>
        @if($availableMonths->isNotEmpty())
            <form method="GET" action="{{ route('public.statements.print-building-pos') }}" style="display:inline-flex; gap:8px; align-items:center; margin:0;">
                <label for="month" class="muted">Month</label>
                <select id="month" name="month" onchange="this.form.submit()">
                    @foreach($availableMonths as $ym)
                        <option value="{{ $ym }}" @selected($ym === $selectedMonth->format('Y-m'))>
                            {{ \App\Support\BillMonth::label($ym) }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    <div class="receipt-header">
        <div class="brand">Unity Rose Garden</div>
        <div class="sub">Monthly bills (POS)</div>
        <div><strong>{{ $selectedMonth->format('F Y') }}</strong></div>
    </div>
    <hr class="sep">

    @forelse($rows as $row)
        <section class="flat-slip">
            <div class="flat-title">FLAT {{ $row['flat_name'] }}</div>
            <hr class="sep">

            <div class="section">Gas</div>
            @if($row['gas_amount'] !== null)
                <div class="row muted">
                    <span class="label">Reading</span>
                    <span class="value">{{ number_format($row['current_m3'], 2) }}-{{ number_format($row['previous_m3'], 2) }}</span>
                </div>
                <div class="row muted">
                    <span class="label">kg x rate</span>
                    <span class="value">{{ number_format($row['consumed_kg'], 2) }}x{{ number_format($row['rate_per_kg'], 2) }}</span>
                </div>
                <div class="row">
                    <span class="label">Gas</span>
                    <span class="value">{{ number_format($row['gas_amount'], 2) }}</span>
                </div>
            @else
                <div class="muted">No gas charge</div>
            @endif

            <div class="section">Other</div>
            @forelse($row['other_lines'] as $line)
                <div class="row">
                    <span class="label">{{ $line->label }}</span>
                    <span class="value">{{ number_format((float) $line->amount, 2) }}</span>
                </div>
            @empty
                <div class="muted">None</div>
            @endforelse

            <hr class="sep-double">
            <div class="row total-row">
                <span class="label">TOTAL</span>
                <span class="value">{{ number_format($row['total'], 2) }}</span>
            </div>
            <hr class="sep">
        </section>
    @empty
        <div class="empty">No statements for {{ $selectedMonth->format('F Y') }}.</div>
    @endforelse

    <p class="footer muted">
        Printed {{ now()->format('d M Y H:i') }}
    </p>
</body>
</html>
