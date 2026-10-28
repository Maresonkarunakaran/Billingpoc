<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->invoice_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 78mm;
            margin: 0 auto;
            padding: 8px 6px;
            font-size: 11px;
            color: #000;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #000; padding-top: 4px; }
        .border-bottom { border-bottom: 1px dashed #000; padding-bottom: 4px; }
        .double-border { border-top: 1px double #000; border-bottom: 1px double #000; padding: 4px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { padding: 2px 0; }
        .text-xs { font-size: 9.5px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print" style="margin-bottom: 10px; text-align: center;">
    <button onclick="window.print()" style="padding: 6px 16px; font-weight: bold; cursor: pointer;">Print Receipt</button>
</div>

<div class="text-center">
    <div class="font-bold" style="font-size: 14px;">{{ $sale->branch->business->legal_name ?? $sale->branch->business->name }}</div>
    <div>{{ $sale->branch->name }}</div>
    <div class="text-xs">{{ $sale->branch->address_line1 }}, {{ $sale->branch->city }} - {{ $sale->branch->pincode }}</div>
    <div class="text-xs">Ph: {{ $sale->branch->phone }}</div>
    <div class="font-bold text-xs">GSTIN: {{ $sale->branch->gstin ?: $sale->branch->business->gstin }}</div>
    <div class="font-bold border-top border-bottom" style="margin-top: 4px;">TAX INVOICE</div>
</div>

<div style="margin: 4px 0;" class="text-xs">
    <div><strong>Inv No:</strong> {{ $sale->invoice_number }}</div>
    <div><strong>Date:</strong> {{ $sale->invoice_date->format('d/m/Y') }} {{ $sale->created_at->format('h:i A') }}</div>
    <div><strong>Cashier:</strong> {{ $sale->user->name }}</div>
    <div><strong>Type:</strong> {{ strtoupper($sale->pricing_type) }}</div>
    @if($sale->customer)
        <div><strong>Customer:</strong> {{ $sale->customer->name }}</div>
        @if($sale->customer->gstin)
            <div><strong>Cust GSTIN:</strong> {{ $sale->customer->gstin }}</div>
        @endif
    @else
        <div><strong>Customer:</strong> Cash Walk-in</div>
    @endif
    <div><strong>Place of Supply:</strong> {{ $sale->place_of_supply }}</div>
</div>

<div class="double-border">
    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Item [HSN]</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td colspan="4" class="font-bold">{{ $item->product->name }}</td>
                </tr>
                <tr>
                    <td class="text-xs">[{{ $item->hsn_code ?? '---' }}]</td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($item->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 4px;">
    <table>
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">₹ {{ number_format($sale->subtotal, 2) }}</td>
        </tr>
        @if($sale->discount_amount > 0)
        <tr>
            <td>Discount:</td>
            <td class="text-right">- ₹ {{ number_format($sale->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td>Taxable Value:</td>
            <td class="text-right">₹ {{ number_format($sale->taxable_amount, 2) }}</td>
        </tr>
        @if($sale->is_interstate)
            <tr>
                <td>IGST:</td>
                <td class="text-right">₹ {{ number_format($sale->igst_amount, 2) }}</td>
            </tr>
        @else
            <tr>
                <td>CGST:</td>
                <td class="text-right">₹ {{ number_format($sale->cgst_amount, 2) }}</td>
            </tr>
            <tr>
                <td>SGST:</td>
                <td class="text-right">₹ {{ number_format($sale->sgst_amount, 2) }}</td>
            </tr>
        @endif
        @if($sale->round_off != 0)
        <tr>
            <td>Round Off:</td>
            <td class="text-right">{{ $sale->round_off >= 0 ? '+ ' : '- ' }}₹ {{ number_format(abs($sale->round_off), 2) }}</td>
        </tr>
        @endif
        <tr class="font-bold border-top border-bottom" style="font-size: 13px;">
            <td>NET PAYABLE:</td>
            <td class="text-right">₹ {{ number_format($sale->final_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Paid Amount:</td>
            <td class="text-right">₹ {{ number_format($sale->paid_amount, 2) }}</td>
        </tr>
        @if($sale->balance_amount > 0)
        <tr class="font-bold">
            <td>Balance Due:</td>
            <td class="text-right">₹ {{ number_format($sale->balance_amount, 2) }}</td>
        </tr>
        @endif
    </table>
</div>

<div class="border-top text-center text-xs" style="margin-top: 8px; padding-top: 6px;">
    <div>{{ $sale->branch->business->getSetting('receipt_footer', 'Thank you for your purchase! Goods once sold cannot be returned without invoice.') }}</div>
    <div style="margin-top: 4px;">*** HAVE A NICE DAY ***</div>
</div>

</body>
</html>
