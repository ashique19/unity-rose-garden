<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense register</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 13px;
            color: #111;
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 16px;
            background: #fff;
        }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #ddd; text-align: left; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #444; }
        .text-end { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #111; }
        .toolbar { margin-bottom: 20px; padding: 12px; background: #f5f5f5; border-radius: 6px; }
        .toolbar a, .toolbar button { margin-right: 8px; padding: 8px 14px; font: inherit; cursor: pointer; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('admin.expenses.index', request()->query()) }}">Back to expenses</a>
    </div>

    <header>
        <h1>{{ $building?->name ?? 'Unity Rose Garden' }}</h1>
        <div class="muted">Expense / purchase register</div>
        <p style="margin: 12px 0 0;">
            @if($from || $to)
                Period:
                {{ $from ? \Carbon\Carbon::parse($from)->format('d M Y') : '…' }}
                –
                {{ $to ? \Carbon\Carbon::parse($to)->format('d M Y') : '…' }}
                <br>
            @endif
            @if($head)
                Head: {{ $head->label }}
            @else
                Head: All
            @endif
        </p>
    </header>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Head</th>
                <th>Payee</th>
                <th>Note</th>
                <th class="text-end">Before</th>
                <th class="text-end">After</th>
                <th class="text-end">Amount (৳)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->entry_date?->format('d M Y') }}</td>
                    <td>{{ $expense->expenseHead?->label ?? '—' }}</td>
                    <td>{{ $expense->payee ?: '—' }}</td>
                    <td>{{ $expense->note ?: '—' }}</td>
                    <td class="text-end">
                        {{ $expense->balance_before !== null ? number_format((float) $expense->balance_before, 2) : '—' }}
                    </td>
                    <td class="text-end">
                        {{ $expense->balance_after !== null ? number_format((float) $expense->balance_after, 2) : '—' }}
                    </td>
                    <td class="text-end">{{ number_format((float) $expense->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">No expenses in this filter.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="6">Total</td>
                <td class="text-end">{{ number_format((float) $total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top: 24px;">
        Printed {{ now()->format('d M Y H:i') }}
        @if($printedBy) by {{ $printedBy }} @endif
        · {{ $expenses->count() }} entr{{ $expenses->count() === 1 ? 'y' : 'ies' }}
    </p>
</body>
</html>
