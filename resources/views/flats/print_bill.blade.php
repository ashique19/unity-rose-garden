<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Bill - Flat {{ $flat->name }}</title>
    <style>
        /* 1. Define standard 80mm POS Thermal Paper limits */
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace; /* Clean, fixed-width receipt style */
            font-size: 12px;
            line-height: 1.4;
            width: 72mm; /* Printable area safety limit */
            margin: 0 auto;
            padding: 5mm 0;
            color: #000;
            background: #fff;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        
        .header {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        
        .title {
            font-size: 16px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        
        .subtitle {
            font-size: 11px;
            margin: 0;
        }

        .meta-table, .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table th, .data-table td {
            padding: 4px 0;
        }

        .border-top { border-top: 1px dashed #000; }
        .border-bottom { border-bottom: 1px dashed #000; }
        .double-border-bottom { border-bottom: 3px double #000; }

        .footer {
            margin-top: 15px;
            padding-top: 5px;
            font-size: 10px;
        }

        /* 2. Print-only triggers to keep it perfectly clean */
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="background:#f8f9fa; padding:10px; text-align:center; border-bottom:1px solid #ddd; margin-bottom:15px;">
        <button onclick="window.print();" style="padding:5px 15px; font-weight:bold; cursor:pointer;">Confirm Print</button>
        <a href="{{ route('flats.show', $flat->id) }}" style="margin-left:10px; color:#555;">Back to History</a>
    </div>

    <div class="header text-center">
        <h2 class="title">MyGas</h2>
        <div class="bold">UNITY ROSE GARDEN</div>
        <div class="subtitle">{{ \Carbon\Carbon::parse($detail->bill_for_month)->format('F Y') }}</div>
    </div>

    <div class="bold text-center" style="font-size: 13px; margin-bottom: 10px;">
        FLAT - {{ $flat->name }}, METER : {{ $flat->id }}
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Mygas:</strong> +8081958262001</td>
            <td class="text-right" style="font-size: 9px; width: 50%;">Khilgao, Dhaka</td>
        </tr>
    </table>

    <table class="data-table border-top">
        <tr>
            <td>Current Reading (CM):</td>
            <td class="text-right">{{ number_format($detail->current_reading, 2) }}</td>
        </tr>
        <tr>
            <td>Previous Reading (CM):</td>
            <td class="text-right">{{ number_format($detail->previous_reading, 2) }}</td>
        </tr>
        <tr class="bold">
            <td>Used Unit (CM):</td>
            <td class="text-right">{{ number_format($detail->used_m3, 2) }} m³</td>
        </tr>
        <tr class="bold">
            <td>Used Unit (KG):</td>
            <td class="text-right">{{ number_format($detail->used_kg, 2) }} kg</td>
        </tr>
        <tr class="border-top">
            <td>Unit Price (Cubic):</td>
            <td class="text-right">{{ number_format($mainBill->price_per_m3, 2) }} Tk</td>
        </tr>
        <tr>
            <td>Unit Price (KG):</td>
            <td class="text-right">{{ number_format($mainBill->price_per_kg, 2) }} Tk</td>
        </tr>
        <tr class="border-top bold">
            <td>Total Amount:</td>
            <td class="text-right">{{ number_format($detail->amount_due, 2) }} Tk</td>
        </tr>
        <tr>
            <td>Service Charge:</td>
            <td class="text-right">0.00 Tk</td>
        </tr>
        <tr class="border-top double-border-bottom bold" style="font-size: 13px;">
            <td style="padding: 6px 0;">GRAND TOTAL:</td>
            <td class="text-right" style="padding: 6px 0;">{{ number_format($detail->amount_due, 2) }} Tk</td>
        </tr>
    </table>

    <div class="text-center footer">
        <p style="margin: 0 0 5px 0;">Please pay your bill promptly.</p>
        <div class="bold">Thank You</div>
        
        <div style="margin-top: 25px; border-top: 1px dotted #000; width: 40%; margin-left: auto; padding-top: 3px;" class="text-center">
            Signature
        </div>
    </div>

    <script>
        window.onload = function() {
            // Uncomment the line below once you verify the layout on screen to automate prints
            // window.print();
        };
    </script>
</body>
</html>