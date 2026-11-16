<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tax Invoice #{{ $sale->invoice_number }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #212529;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .invoice-box {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 28px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .header-table, .details-table, .items-table, .totals-table, .hsn-table {
            width: 100%;
            border-collapse: collapse;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .text-muted { color: #6c757d; }
        .text-primary { color: #0056b3; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
        .text-xs { font-size: 10.5px; }
        .text-sm { font-size: 11.5px; }

        .items-table th, .items-table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
        }
        .items-table th {
            background-color: #f1f3f5;
            font-weight: 700;
            text-align: center;
            font-size: 11px;
            text-transform: uppercase;
        }
        .hsn-table th, .hsn-table td {
            border: 1px solid #dee2e6;
            padding: 4px 6px;
            font-size: 10.5px;
        }
        .hsn-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 3px;
        }
        .badge-b2b { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-wholesale { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .top-action-bar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-primary:hover { background: #0069d9; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-outline { background: #fff; border-color: #ced4da; color: #495057; }
        .btn-outline:hover { background: #e9ecef; }

        @media print {
            body { background: #fff; padding: 0; }
            .top-action-bar { display: none !important; }
            .invoice-box {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="top-action-bar">
    <a href="{{ route('pos.index') }}" class="btn-action btn-outline">
        <i class="fas fa-arrow-left mr-1" style="margin-right: 5px;"></i> Back to POS
    </a>
    <div>
        <a href="{{ route('pos.receipt', $sale->id) }}" class="btn-action btn-secondary" style="margin-right: 8px;">
            <i class="fas fa-receipt mr-1" style="margin-right: 5px;"></i> Thermal Receipt
        </a>
        <button onclick="window.print()" class="btn-action btn-primary">
            <i class="fas fa-print mr-1" style="margin-right: 5px;"></i> Print Tax Invoice (A4)
        </button>
    </div>
</div>

<div class="invoice-box">
    <!-- Header -->
    <table class="header-table" style="border-bottom: 2px solid #0056b3; padding-bottom: 12px; margin-bottom: 14px;">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <h2 style="margin: 0 0 3px 0; color: #0056b3; font-size: 20px; font-weight: 800;">
                    {{ $sale->branch->business->legal_name ?? $sale->branch->business->name }}
                </h2>
                <div class="font-semibold text-sm">{{ $sale->branch->name }}</div>
                <div class="text-xs text-muted">
                    {{ $sale->branch->address_line1 }}, {{ $sale->branch->city }} - {{ $sale->branch->pincode }}
                </div>
                <div class="text-xs text-muted">
                    Phone: {{ $sale->branch->phone }} | Email: {{ $sale->branch->email }}
                </div>
                <div class="text-xs font-bold" style="margin-top: 4px;">
                    GSTIN: <span style="font-family: monospace; font-size: 12px;">{{ $sale->branch->gstin ?: $sale->branch->business->gstin }}</span>
                    &nbsp;|&nbsp; State: {{ $sale->branch->state_code }} - {{ $sale->branch->state_name }}
                </div>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
                <div style="font-size: 18px; font-weight: 800; color: #212529; letter-spacing: 0.5px;">TAX INVOICE</div>
                <div class="text-xs text-muted font-semibold" style="margin-bottom: 4px;">(Original for Recipient)</div>
                <table style="width: 100%; font-size: 11px; margin-top: 6px;">
                    <tr>
                        <td class="text-right font-bold" style="width: 50%;">Invoice No:</td>
                        <td class="text-right" style="font-family: monospace; font-weight: 700;">{{ $sale->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-right font-bold">Invoice Date:</td>
                        <td class="text-right">{{ $sale->invoice_date->format('d-m-Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-right font-bold">Place of Supply:</td>
                        <td class="text-right">{{ $sale->place_of_supply }}</td>
                    </tr>
                    <tr>
                        <td class="text-right font-bold">Reverse Charge:</td>
                        <td class="text-right">No</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Buyer / Biller Details -->
    <table class="details-table" style="margin-bottom: 16px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 10px;">
                    <div class="text-xs font-bold text-muted" style="text-transform: uppercase; margin-bottom: 4px;">
                        Billed To (Buyer / Recipient Details)
                    </div>
                    @if($sale->customer)
                        <div class="font-bold text-sm" style="color: #212529;">
                            {{ $sale->customer->name }}
                            @if($sale->customer->is_distributor)
                                <span class="badge badge-wholesale">Distributor</span>
                            @endif
                        </div>
                        @if($sale->customer->address)
                            <div class="text-xs text-muted">{{ $sale->customer->address }}</div>
                        @endif
                        <div class="text-xs text-muted">Phone: {{ $sale->customer->phone ?: '---' }}</div>
                        @if($sale->customer->gstin)
                            <div class="text-xs font-bold text-primary" style="margin-top: 2px;">
                                GSTIN: <span style="font-family: monospace;">{{ $sale->customer->gstin }}</span>
                            </div>
                        @endif
                        <div class="text-xs text-muted">
                            State: {{ $sale->customer->state_code }} - {{ $sale->customer->state_name }}
                        </div>
                    @else
                        <div class="font-bold text-sm">Cash Walk-in Customer</div>
                        <div class="text-xs text-muted">Counter Retail Sale (Unregistered)</div>
                    @endif
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 10px;">
                    <div class="text-xs font-bold text-muted" style="text-transform: uppercase; margin-bottom: 4px;">
                        Billing & Payment Details
                    </div>
                    <table style="width: 100%; font-size: 11px;">
                        <tr>
                            <td class="text-muted">Pricing Mode:</td>
                            <td class="text-right font-bold" style="text-transform: uppercase;">
                                {{ $sale->pricing_type }} Rate
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Supply Type:</td>
                            <td class="text-right font-semibold">
                                {{ $sale->is_interstate ? 'Inter-state (IGST)' : 'Intra-state (CGST + SGST)' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Payment Status:</td>
                            <td class="text-right font-bold {{ $sale->payment_status === 'paid' ? 'text-success' : 'text-danger' }}">
                                {{ strtoupper($sale->payment_status) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Billed By (Cashier):</td>
                            <td class="text-right">{{ $sale->user->name }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table" style="margin-bottom: 16px;">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th class="text-left">Product / Item Description</th>
                <th style="width: 60px;">HSN</th>
                <th style="width: 48px;">Qty</th>
                <th style="width: 44px;">Unit</th>
                <th style="width: 68px;" class="text-right">Rate (₹)</th>
                <th style="width: 52px;" class="text-right">Disc.</th>
                <th style="width: 72px;" class="text-right">Taxable (₹)</th>
                @if($sale->is_interstate)
                    <th style="width: 76px;" class="text-right">IGST (₹)</th>
                @else
                    <th style="width: 68px;" class="text-right">CGST (₹)</th>
                    <th style="width: 68px;" class="text-right">SGST (₹)</th>
                @endif
                <th style="width: 80px;" class="text-right">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-semibold text-left">
                        {{ $item->product->name }}
                        @if($item->product->sku)
                            <div class="text-xs text-muted" style="font-size: 10px;">SKU: {{ $item->product->sku }}</div>
                        @endif
                    </td>
                    <td class="text-center" style="font-family: monospace; font-size: 10.5px;">{{ $item->hsn_code ?? '---' }}</td>
                    <td class="text-center font-bold">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-center text-muted text-xs">{{ $item->unit->code }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->discount_amount, 2) }}</td>
                    <td class="text-right font-semibold">{{ number_format($item->taxable_amount, 2) }}</td>
                    @if($sale->is_interstate)
                        <td class="text-right text-xs">
                            <div>{{ number_format($item->igst_amount, 2) }}</div>
                            <span class="text-muted">({{ $item->igst_rate }}%)</span>
                        </td>
                    @else
                        <td class="text-right text-xs">
                            <div>{{ number_format($item->cgst_amount, 2) }}</div>
                            <span class="text-muted">({{ $item->cgst_rate }}%)</span>
                        </td>
                        <td class="text-right text-xs">
                            <div>{{ number_format($item->sgst_amount, 2) }}</div>
                            <span class="text-muted">({{ $item->sgst_rate }}%)</span>
                        </td>
                    @endif
                    <td class="text-right font-bold">{{ number_format($item->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals and HSN Summary -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 54%; vertical-align: top; padding-right: 12px;">
                <!-- HSN Tax Summary -->
                <div class="text-xs font-bold text-muted" style="text-transform: uppercase; margin-bottom: 4px;">
                    HSN / SAC Tax Analysis
                </div>
                <table class="hsn-table">
                    <thead>
                        <tr>
                            <th>HSN/SAC</th>
                            <th class="text-right">Taxable (₹)</th>
                            @if($sale->is_interstate)
                                <th class="text-right">IGST (₹)</th>
                            @else
                                <th class="text-right">CGST (₹)</th>
                                <th class="text-right">SGST (₹)</th>
                            @endif
                            <th class="text-right">Total Tax (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $hsnGroups = $sale->items->groupBy('hsn_code');
                        @endphp
                        @foreach($hsnGroups as $hsn => $group)
                            <tr>
                                <td style="font-family: monospace;">{{ $hsn ?: 'N/A' }}</td>
                                <td class="text-right">{{ number_format($group->sum('taxable_amount'), 2) }}</td>
                                @if($sale->is_interstate)
                                    <td class="text-right">{{ number_format($group->sum('igst_amount'), 2) }}</td>
                                @else
                                    <td class="text-right">{{ number_format($group->sum('cgst_amount'), 2) }}</td>
                                    <td class="text-right">{{ number_format($group->sum('sgst_amount'), 2) }}</td>
                                @endif
                                <td class="text-right font-bold">
                                    {{ number_format($group->sum('cgst_amount') + $group->sum('sgst_amount') + $group->sum('igst_amount'), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($sale->notes)
                    <div style="margin-top: 10px; padding: 6px 8px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px;" class="text-xs">
                        <strong>Notes:</strong> {{ $sale->notes }}
                    </div>
                @endif
            </td>
            <td style="width: 46%; vertical-align: top;">
                <table class="totals-table" style="font-size: 11.5px;">
                    <tr>
                        <td style="padding: 3px 0;" class="text-muted">Subtotal (Gross Value):</td>
                        <td style="padding: 3px 0;" class="text-right font-semibold">₹ {{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    @if($sale->discount_amount > 0)
                        <tr>
                            <td style="padding: 3px 0;" class="text-danger">Invoice Discount:</td>
                            <td style="padding: 3px 0;" class="text-right text-danger font-semibold">- ₹ {{ number_format($sale->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding: 3px 0;" class="text-muted">Total Taxable Value:</td>
                        <td style="padding: 3px 0;" class="text-right font-semibold">₹ {{ number_format($sale->taxable_amount, 2) }}</td>
                    </tr>
                    @if($sale->is_interstate)
                        <tr>
                            <td style="padding: 3px 0;" class="text-muted">Integrated GST (IGST):</td>
                            <td style="padding: 3px 0;" class="text-right font-semibold text-primary">₹ {{ number_format($sale->igst_amount, 2) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td style="padding: 3px 0;" class="text-muted">Central GST (CGST):</td>
                            <td style="padding: 3px 0;" class="text-right font-semibold text-primary">₹ {{ number_format($sale->cgst_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;" class="text-muted">State GST (SGST):</td>
                            <td style="padding: 3px 0;" class="text-right font-semibold text-primary">₹ {{ number_format($sale->sgst_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding: 3px 0;" class="text-muted">Round Off:</td>
                        <td style="padding: 3px 0;" class="text-right">{{ $sale->round_off >= 0 ? '+ ' : '- ' }}₹ {{ number_format(abs($sale->round_off), 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #212529; border-bottom: 2px solid #212529;">
                        <th style="padding: 6px 0; font-size: 14px;" class="font-bold">Grand Total:</th>
                        <th style="padding: 6px 0; font-size: 14px;" class="text-right font-bold text-success">
                            ₹ {{ number_format($sale->final_amount, 2) }}
                        </th>
                    </tr>
                    <tr>
                        <td style="padding: 3px 0;">Paid Amount:</td>
                        <td style="padding: 3px 0;" class="text-right font-bold">₹ {{ number_format($sale->paid_amount, 2) }}</td>
                    </tr>
                    @if($sale->balance_amount > 0)
                        <tr>
                            <td style="padding: 3px 0;" class="text-danger font-bold">Balance Dues:</td>
                            <td style="padding: 3px 0;" class="text-right text-danger font-bold">₹ {{ number_format($sale->balance_amount, 2) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Declaration and Signatures -->
    <table style="width: 100%; margin-top: 28px; border-top: 1px solid #dee2e6; padding-top: 14px;">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <div class="text-xs font-bold text-muted" style="text-transform: uppercase; margin-bottom: 2px;">Declaration</div>
                <div class="text-xs text-muted" style="line-height: 1.3;">
                    {{ $sale->branch->business->getSetting('gst_declaration', 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.') }}
                </div>
            </td>
            <td style="width: 35%; vertical-align: top; text-align: right;">
                <div class="text-xs font-bold text-muted" style="text-transform: uppercase;">
                    For {{ $sale->branch->business->name }}
                </div>
                <div style="height: 48px;"></div>
                <div class="text-xs font-bold" style="border-top: 1px solid #212529; display: inline-block; padding-top: 2px; min-width: 140px; text-align: center;">
                    Authorised Signatory
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
