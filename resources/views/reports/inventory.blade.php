@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-calculator text-warning mr-2"></i>Inventory Valuation Report</h4>
        <span class="text-muted text-sm">Detailed item-by-item asset valuation at weighted average purchase cost.</span>
    </div>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print mr-1"></i> Print Valuation</button>
</div>

<!-- Total Valuation Banner -->
<div class="card kpi-card bg-white p-3 border shadow-sm mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-xs text-muted font-weight-bold text-uppercase">Total Inventory Asset Valuation at Cost</span>
            <h3 class="font-weight-bold text-success mb-0 mt-1">₹ {{ number_format($totalValuation, 2) }}</h3>
            <span class="text-xs text-muted">Valuation method: Moving Weighted Average Cost (Indian GAAP / Ind AS 2)</span>
        </div>
        <div class="p-3 bg-light rounded-circle"><i class="fas fa-boxes text-success fa-2x"></i></div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Product Details</th>
                        <th>SKU</th>
                        <th class="text-center">On-Hand Quantity</th>
                        <th class="text-right">Weighted Avg Cost (₹)</th>
                        <th class="text-right">Total Valuation at Cost (₹)</th>
                        <th class="text-center">Reorder Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        @php
                            $isLow = $r->quantity <= $r->alert_quantity;
                        @endphp
                        <tr>
                            <td><span class="badge badge-light border font-weight-bold">{{ $r->branch_name }}</span></td>
                            <td class="font-weight-bold text-dark">{{ $r->product_name }}</td>
                            <td><code>{{ $r->sku }}</code></td>
                            <td class="text-center font-weight-bold {{ $isLow ? 'text-warning' : 'text-dark' }}">
                                {{ number_format($r->quantity, 2) }}
                            </td>
                            <td class="text-right text-muted">₹ {{ number_format($r->avg_cost, 2) }}</td>
                            <td class="text-right font-weight-bold text-success" style="font-size: 1rem;">
                                ₹ {{ number_format($r->total_value, 2) }}
                            </td>
                            <td class="text-center">
                                @if($r->quantity <= 0)
                                    <span class="badge badge-danger px-2 py-1">OUT OF STOCK</span>
                                @elseif($isLow)
                                    <span class="badge badge-warning px-2 py-1">REORDER (Alert: {{ $r->alert_quantity }})</span>
                                @else
                                    <span class="badge badge-success px-2 py-1">SUFFICIENT</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No inventory records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($rows->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endsection
