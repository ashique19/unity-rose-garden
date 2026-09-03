<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Building bills — {{ $selectedMonth->format('M Y') }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 13px;
            color: #111;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 16px;
            background: #fff;
        }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 {
            font-size: 15px;
            margin: 0 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #ccc;
        }
        .muted { color: #555; }
        .meta { margin-bottom: 18px; }
        .toolbar {
            margin-bottom: 18px;
            padding: 12px;
            background: #f5f5f5;
            border-radius: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .toolbar a, .toolbar button, .toolbar select {
            padding: 8px 14px;
            font: inherit;
            cursor: pointer;
        }
        .flat-block {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 12px 14px;
            margin-bottom: 12px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .flat-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 8px 20px;
        }
        @media (max-width: 640px) {
            .flat-grid { grid-template-columns: 1fr; }
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 0; text-align: left; vertical-align: top; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #444; }
        .text-end { text-align: right; }
        .gas-eq { font-variant-numeric: tabular-nums; }
        .total-line {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 2px solid #111;
            font-weight: bold;
            font-size: 26px;
            line-height: 1.2;
            display: flex;
            justify-content: space-between;
        }
        .empty { padding: 32px 0; text-align: center; color: #666; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; max-width: none; }
            .flat-block { border-color: #bbb; }
        }
    </style>
</head>
<body>
        <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('public.statements.print-building-pos', ['month' => $selectedMonth->format('Y-m')]) }}">POS print</a>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('home') }}">Back to home</a>
        @if($availableMonths->isNotEmpty())
            <form method="GET" action="{{ route('public.statements.print-building') }}" style="display:inline-flex; gap:8px; align-items:center; margin:0;">
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

    <header class="meta">
        <h1>Unity Rose Garden</h1>
        <div class="muted">Building-wide monthly bills</div>
        <p style="margin: 10px 0 0;">
            Bill month: <strong>{{ $selectedMonth->format('F Y') }}</strong>
        </p>
    </header>

    @forelse($rows as $row)
        <section class="flat-block">
            <h2>Flat {{ $row['flat_name'] }}</h2>
            <div class="flat-grid">
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th colspan="2">Gas bill</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($row['gas_amount'] !== null)
                                <tr>
                                    <td class="muted">Reading (new − old)</td>
                                    <td class="text-end gas-eq">
                                        {{ number_format($row['current_m3'], 2) }} − {{ number_format($row['previous_m3'], 2) }} m³
                                    </td>
                                </tr>
                                <tr>
                                    <td class="muted">Used kg × rate/kg</td>
                                    <td class="text-end gas-eq">
                                        {{ number_format($row['consumed_kg'], 2) }}
                                        ×
                                        {{ number_format($row['rate_per_kg'], 2) }}
                                        =
                                        <strong>৳{{ number_format($row['gas_amount'], 2) }}</strong>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="2" class="muted">No gas charge this month</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th>Other charges</th>
                                <th class="text-end">Amount (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($row['other_lines'] as $line)
                                <tr>
                                    <td>{{ $line->label }}</td>
                                    <td class="text-end">{{ number_format((float) $line->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="muted">None</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="total-line">
                <span>Total</span>
                <span>৳{{ number_format($row['total'], 2) }}</span>
            </div>
        </section>
    @empty
        <div class="empty">No statements found for {{ $selectedMonth->format('F Y') }}.</div>
    @endforelse

    <p class="muted" style="margin-top: 24px; font-size: 11px;">
        Unity Rose Garden Association · Printed {{ now()->format('d M Y H:i') }}
    </p>
</body>
</html>
