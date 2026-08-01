<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense voucher — {{ $expense->expenseHead?->label ?? 'Expense' }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 14px;
            color: #111;
            max-width: 640px;
            margin: 0 auto;
            padding: 24px 16px;
            background: #fff;
        }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #555; }
        .box { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-top: 20px; }
        .row { display: flex; justify-content: space-between; gap: 16px; margin: 8px 0; }
        .label { color: #555; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; }
        .amount { font-size: 28px; font-weight: bold; margin: 16px 0; }
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
        <a href="{{ route('admin.expenses.index') }}">Back to expenses</a>
    </div>

    <header>
        <h1>{{ $building?->name ?? 'Unity Rose Garden' }}</h1>
        <div class="muted">Expense / purchase voucher</div>
    </header>

    <div class="box">
        <div class="row">
            <div>
                <div class="label">Date</div>
                <div>{{ $expense->entry_date?->format('d F Y') }}</div>
            </div>
            <div>
                <div class="label">Voucher #</div>
                <div>{{ $expense->id }}</div>
            </div>
        </div>
        <div class="row">
            <div>
                <div class="label">Expense head</div>
                <div>{{ $expense->expenseHead?->label ?? '—' }}</div>
            </div>
            <div>
                <div class="label">Payee</div>
                <div>{{ $expense->payeeName() ?: '—' }}</div>
            </div>
        </div>
        <div class="amount">৳{{ number_format((float) $expense->amount, 2) }}</div>
        @if($expense->balance_before !== null || $expense->balance_after !== null)
            <div class="row">
                <div>
                    <div class="label">Balance before</div>
                    <div>৳{{ number_format((float) $expense->balance_before, 2) }}</div>
                </div>
                <div>
                    <div class="label">Balance after</div>
                    <div>৳{{ number_format((float) $expense->balance_after, 2) }}</div>
                </div>
            </div>
        @endif
        <div>
            <div class="label">Note</div>
            <div>{{ $expense->note ?: '—' }}</div>
        </div>
        @if(! empty($mediaLinks))
            <div style="margin-top: 12px;">
                <div class="label">Media</div>
                <ul style="margin: 6px 0 0; padding-left: 18px;">
                    @foreach($mediaLinks as $media)
                        <li><a href="{{ $media['url'] }}">{{ $media['title'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <p class="muted" style="margin-top: 24px;">
        Printed {{ now()->format('d M Y H:i') }}
        @if($printedBy) by {{ $printedBy }} @endif
    </p>
</body>
</html>
