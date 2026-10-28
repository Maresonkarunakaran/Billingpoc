@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-shield-alt text-warning mr-2"></i>System Security & Audit Trail</h4>
        <span class="text-muted text-sm">Immutable audit log recording user logins, checkout transactions, inventory movements and master edits.</span>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Branch</th>
                        <th>Event Name</th>
                        <th>Model / Resource</th>
                        <th>IP Address</th>
                        <th>Payload Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted text-sm font-weight-medium">{{ $log->created_at->format('d M Y, h:i:s A') }}</td>
                            <td class="font-weight-bold text-dark">{{ $log->user?->name ?? 'System' }}</td>
                            <td><span class="badge badge-light border">{{ $log->branch?->name ?? 'Global' }}</span></td>
                            <td>
                                <span class="badge badge-primary px-2 py-1 font-weight-bold">{{ strtoupper($log->event) }}</span>
                            </td>
                            <td><small class="text-muted font-weight-bold">{{ class_basename($log->auditable_type ?? 'N/A') }} #{{ $log->auditable_id }}</small></td>
                            <td><code>{{ $log->ip_address ?: '127.0.0.1' }}</code></td>
                            <td>
                                @if($log->new_values)
                                    <pre class="bg-light p-1 rounded mb-0 text-xs" style="max-height: 60px; overflow: auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No audit logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
