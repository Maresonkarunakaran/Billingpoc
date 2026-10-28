@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-wallet text-warning mr-2"></i>Cash Shift & Drawer Management</h4>
        <span class="text-muted text-sm">Control cashier cash drawer floats, cash sales tally, expenses and end-of-shift reconciliation.</span>
    </div>
    @if(! $activeShift)
        <button class="btn btn-warning btn-sm font-weight-bold px-3" data-toggle="modal" data-target="#openShiftModal">
            <i class="fas fa-unlock mr-1"></i> Open New Cash Shift
        </button>
    @endif
</div>

<!-- CURRENT ACTIVE SHIFT STATUS -->
@if($activeShift)
    <div class="card card-outline card-success shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <span class="badge badge-success px-2 py-1 mr-2"><i class="fas fa-circle text-xs mr-1"></i> ACTIVE SHIFT OPEN</span>
                <span class="font-weight-bold text-dark">Cashier: {{ $activeShift->user->name }}</span>
                <span class="text-muted text-sm ml-2">&bull; Opened at {{ $activeShift->opened_at->format('d M Y, h:i A') }} (Branch: {{ $activeShift->branch->name }})</span>
            </div>
            <button class="btn btn-danger btn-sm font-weight-bold" data-toggle="modal" data-target="#closeShiftModal">
                <i class="fas fa-lock mr-1"></i> Reconcile & Close Shift
            </button>
        </div>
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-2 mb-2">
                    <div class="p-3 bg-light rounded text-center">
                        <span class="text-xs text-muted font-weight-bold text-uppercase d-block">Opening Float</span>
                        <h5 class="font-weight-bold text-dark mb-0 mt-1">₹ {{ number_format($activeShift->opening_cash, 2) }}</h5>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <div class="p-3 bg-light rounded text-center">
                        <span class="text-xs text-muted font-weight-bold text-uppercase d-block">Cash Sales (+)</span>
                        <h5 class="font-weight-bold text-success mb-0 mt-1">₹ {{ number_format($activeShift->cash_sales, 2) }}</h5>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <div class="p-3 bg-light rounded text-center">
                        <span class="text-xs text-muted font-weight-bold text-uppercase d-block">Collections (+)</span>
                        <h5 class="font-weight-bold text-info mb-0 mt-1">₹ {{ number_format($activeShift->cash_collections, 2) }}</h5>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <div class="p-3 bg-light rounded text-center">
                        <span class="text-xs text-muted font-weight-bold text-uppercase d-block">Expenses (-)</span>
                        <h5 class="font-weight-bold text-danger mb-0 mt-1">₹ {{ number_format($activeShift->cash_expenses, 2) }}</h5>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="p-3 bg-success-light border border-success rounded text-center" style="background-color: #ecfdf5;">
                        <span class="text-xs text-success font-weight-bold text-uppercase d-block">Expected Cash in Drawer (System Calculated)</span>
                        <h3 class="font-weight-bold text-success mb-0 mt-1">₹ {{ number_format($activeShift->calculateCurrentSystemCash(), 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- SHIFT HISTORY TABLE -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="font-weight-bold text-dark mb-0">Shift History Log</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Opened At</th>
                        <th>Closed At</th>
                        <th>Cashier</th>
                        <th>Branch</th>
                        <th class="text-right">Opening Float (₹)</th>
                        <th class="text-right">System Cash (₹)</th>
                        <th class="text-right">Physical Count (₹)</th>
                        <th class="text-center">Discrepancy</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $s)
                        <tr>
                            <td class="font-weight-medium">{{ $s->opened_at->format('d M Y, h:i A') }}</td>
                            <td class="text-muted">{{ $s->closed_at ? $s->closed_at->format('d M Y, h:i A') : 'Still Open' }}</td>
                            <td class="font-weight-bold text-dark">{{ $s->user->name }}</td>
                            <td><span class="badge badge-light border">{{ $s->branch->name }}</span></td>
                            <td class="text-right">₹ {{ number_format($s->opening_cash, 2) }}</td>
                            <td class="text-right font-weight-medium">₹ {{ number_format($s->closing_cash_system, 2) }}</td>
                            <td class="text-right font-weight-bold">
                                {{ $s->closing_cash_physical !== null ? '₹ ' . number_format($s->closing_cash_physical, 2) : '---' }}
                            </td>
                            <td class="text-center">
                                @if($s->status === 'closed')
                                    @if(abs($s->discrepancy) < 0.01)
                                        <span class="badge badge-success px-2 py-1">BALANCED (₹ 0.00)</span>
                                    @elseif($s->discrepancy > 0)
                                        <span class="badge badge-info px-2 py-1">+ ₹ {{ number_format($s->discrepancy, 2) }} (Surplus)</span>
                                    @else
                                        <span class="badge badge-danger px-2 py-1">- ₹ {{ number_format(abs($s->discrepancy), 2) }} (Shortage)</span>
                                    @endif
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $s->status === 'open' ? 'badge-success' : 'badge-secondary' }} px-2 py-1">{{ strtoupper($s->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">No shifts logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($shifts->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $shifts->links() }}
        </div>
    @endif
</div>

<!-- CLOSE SHIFT MODAL -->
@if($activeShift)
<div class="modal fade" id="closeShiftModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('shifts.close', $activeShift->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white py-2">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-lock mr-1"></i> Reconcile & Close Cash Shift</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded text-center mb-3">
                        <span class="text-xs text-muted font-weight-bold text-uppercase">Expected Cash in Drawer (System Calculated)</span>
                        <h3 class="font-weight-bold text-dark mb-0 mt-1">₹ {{ number_format($activeShift->calculateCurrentSystemCash(), 2) }}</h3>
                        <small class="text-muted">Opening Float + Cash Sales + Collections - Cash Expenses</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Physical Cash Counted in Drawer (₹) *</label>
                        <input type="number" name="closing_cash_physical" class="form-control form-control-lg font-weight-bold text-primary" required min="0" step="0.5" placeholder="0.00">
                        <small class="text-muted">Count physical currency notes & coins and input exact total.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Handover / Shift Closing Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="E.g. Denomination breakdown, handover to evening cashier..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-danger font-weight-bold" onclick="return confirm('Confirm closing shift drawer? This will freeze transaction tallies for this shift.')">
                        <i class="fas fa-lock mr-1"></i> Finalize & Lock Shift
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- OPEN SHIFT MODAL -->
<div class="modal fade" id="openShiftModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('shifts.open') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-warning py-2">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-wallet mr-1"></i> Open Cash Shift</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Opening Cash Float (₹) *</label>
                        <input type="number" name="opening_cash" class="form-control form-control-lg font-weight-bold" value="2000.00" required min="0" step="10">
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-sm">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="E.g. Morning drawer float">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-unlock mr-1"></i> Start Shift</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
