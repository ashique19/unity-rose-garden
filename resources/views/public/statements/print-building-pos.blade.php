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
        .sep {
            border: none;
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .sep-double {
            border: none;
            border-top: 2px solid #000;
            margin: 6px 0;
        }
        .flat-slip {
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-after: always;
            break-after: page;
            margin-bottom: 14px;
            padding-bottom: 4px;
        }
        .slip-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 4px;
        }
        .slip-header .left {
            flex: 1;
            min-width: 0;
            text-align: left;
        }
        .slip-header .right {
            flex-shrink: 0;
            text-align: right;
            text-transform: uppercase;
            align-self: center;
        }
        .slip-header .brand {
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .slip-header .month {
            font-size: 12px;
            margin-top: 2px;
        }
        .slip-header .flat-name {
            font-weight: bold;
            font-size: 28px;
            line-height: 1;
        }
        .section {
            font-weight: bold;
            margin: 6px 0 2px;
            text-transform: uppercase;
            font-size: 11px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: baseline;
        }
        .row .label {
            flex: 1;
            min-width: 0;
            word-break: break-word;
        }
        .row .value {
            flex-shrink: 0;
            text-align: right;
            white-space: nowrap;
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

    @forelse($rows as $row)
        <section class="flat-slip">
            <div class="slip-header">
                <div class="left">
                    <div class="brand">Unity Rose Garden</div>
                    <div class="month">{{ $selectedMonth->format('F Y') }}</div>
                </div>
                <div class="right">
                    <div class="flat-name">{{ $row['flat_name'] }}</div>
                </div>
            </div>
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
                <span class="value">{{ number_format($row['total'], 0) }}</span>
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
