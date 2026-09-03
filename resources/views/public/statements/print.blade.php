<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement — Flat {{ $flat->name }} — {{ $selectedMonth->format('M Y') }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 14px;
            color: #111;
            max-width: 720px;
            margin: 0 auto;
            padding: 24px 16px;
            background: #fff;
        }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 16px; margin: 24px 0 8px; }
        .muted { color: #555; }
        .meta { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #ddd; text-align: left; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: #444; }
        .text-end { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #111; border-bottom: none; }
        .toolbar { margin-bottom: 20px; padding: 12px; background: #f5f5f5; border-radius: 6px; }
        .toolbar a, .toolbar button {
            margin-right: 8px;
            padding: 8px 14px;
            font: inherit;
            cursor: pointer;
        }
        .gas-photo {
            margin: 8px 0 4px;
            max-width: 200px;
            max-height: 150px;
            border: 1px solid #ccc;
            border-radius: 3px;
            display: block;
            object-fit: contain;
            background: #fafafa;
        }
        .gas-photo-label { font-size: 12px; color: #555; margin-bottom: 12px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('public.flats.show', ['flat' => $flat, 'month' => $selectedMonth->format('Y-m')]) }}">Back to statement</a>
    </div>

    <header class="meta">
        <h1>Unity Rose Garden</h1>
        <div class="muted">Monthly statement</div>
        <p style="margin: 12px 0 0;">
            <strong>Flat {{ $flat->name }}</strong><br>
            Bill month: {{ $selectedMonth->format('F Y') }}
        </p>
    </header>

    <h2>Charges</h2>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-end">Amount (৳)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statement->lines->where('enabled', true) as $line)
                <tr>
                    <td>
                        {{ $line->label }}
                        @if($line->note)
                            <div class="muted" style="font-size: 12px;">{{ $line->note }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format((float) $line->amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total</td>
                <td class="text-end">{{ number_format((float) $statement->totalAmount(), 2) }}</td>
            </tr>
            <tr>
                <td class="muted">Collected</td>
                <td class="text-end muted">{{ number_format((float) $statement->collectedAmount(), 2) }}</td>
            </tr>
            <tr>
                <td class="muted">Pending</td>
                <td class="text-end muted">{{ number_format((float) $statement->pendingAmount(), 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if($gasLine)
        <h2>Gas details</h2>
        @if(! empty($gasPhotoDataUri))
            <img class="gas-photo" src="{{ $gasPhotoDataUri }}" alt="Gas meter photo for flat {{ $flat->name }}">
            <div class="gas-photo-label">Meter photo</div>
        @endif
        @php $meta = $gasLine->meta ?? []; @endphp
        <table>
            <tbody>
                <tr>
                    <td>Previous (m³)</td>
                    <td class="text-end">{{ number_format((float) ($meta['previous_m3'] ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Current (m³)</td>
                    <td class="text-end">{{ number_format((float) ($meta['current_m3'] ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Consumed (m³ / kg)</td>
                    <td class="text-end">
                        {{ number_format((float) ($meta['consumed_m3'] ?? 0), 2) }} /
                        {{ number_format((float) ($meta['consumed_kg'] ?? $gasLine->quantity ?? 0), 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Rate / kg</td>
                    <td class="text-end">{{ number_format((float) ($meta['rate_per_kg'] ?? $gasLine->rate ?? 0), 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Gas total</td>
                    <td class="text-end">{{ number_format((float) $gasLine->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if($otherLines->isNotEmpty())
        <h2>Other charges</h2>
        <table>
            <thead>
                <tr>
                    <th>Heading</th>
                    <th class="text-end">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($otherLines as $line)
                    <tr>
                        <td>{{ $line->label }}</td>
                        <td class="text-end">{{ number_format((float) $line->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($statement->collections->isNotEmpty())
        <h2>Payments</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Note</th>
                    <th class="text-end">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statement->collections as $collection)
                    <tr>
                        <td>{{ $collection->collected_on?->format('d M Y') }}</td>
                        <td class="muted">{{ $collection->note ?: '—' }}</td>
                        <td class="text-end">{{ number_format((float) $collection->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="muted" style="margin-top: 28px; font-size: 12px;">
        Unity Rose Garden Association · Printed {{ now()->format('d M Y H:i') }}
    </p>
</body>
</html>
