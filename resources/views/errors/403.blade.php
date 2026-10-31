<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden | Access Denied</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-0 shadow-lg text-center p-4 p-md-5" style="border-radius: 16px;">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle border text-danger" style="width: 84px; height: 84px; font-size: 2.5rem;">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>

                <span class="badge badge-danger px-3 py-1 font-weight-bold mb-2 text-uppercase d-inline-block mx-auto">403 Access Denied</span>
                <h3 class="font-weight-bold text-dark mt-2 mb-2">Permission Restricted</h3>
                
                <p class="text-muted text-sm mb-4">
                    {{ $exception->getMessage() ?: 'You do not have authorization to access this page or module. Your shop owner controls which features your account can access.' }}
                </p>

                @if(auth()->check())
                <div class="alert alert-light border text-left text-xs text-muted mb-4 p-3" style="border-radius: 8px;">
                    <div class="d-flex align-items-center mb-1">
                        <strong class="text-dark mr-2"><i class="fas fa-user-shield mr-1"></i> Account:</strong>
                        <span>{{ auth()->user()->name }} ({{ auth()->user()->role?->name ?? 'Billing Staff' }})</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <strong class="text-dark mr-2"><i class="fas fa-store mr-1"></i> Business:</strong>
                        <span>{{ auth()->user()->business?->name ?? 'Store' }}</span>
                    </div>
                </div>
                @endif

                <div class="d-flex justify-content-center flex-wrap gap-2">
                    @if(auth()->check())
                        @if(auth()->user()->canAccessDashboard())
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary font-weight-bold px-3 py-2 mr-2">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('pos_terminal'))
                            <a href="{{ route('pos.index') }}" class="btn btn-success font-weight-bold px-4 py-2">
                                <i class="fas fa-cash-register mr-1"></i> Open POS Terminal
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary font-weight-bold px-4 py-2">
                            <i class="fas fa-sign-in-alt mr-1"></i> Return to Sign In
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
