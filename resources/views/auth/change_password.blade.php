<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password | BILLINGPOC</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .box { width: 100%; max-width: 440px; }
        .card-custom { border-radius: 14px; border: 1px solid #1e293b; background: #ffffff; box-shadow: 0 20px 30px -10px rgba(0,0,0,0.5); }
    </style>
</head>
<body>
<div class="box">
    <div class="card card-custom">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <div class="d-inline-flex p-3 rounded-circle bg-light border text-primary mb-2 shadow-xs">
                    <i class="fas fa-key fa-2x"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-1">Set Your Permanent Password</h4>
                <p class="text-xs text-muted mb-0">
                    Your software administrator has required a password update before accessing your POS terminal.
                </p>
            </div>

            @if (session('warning'))
                <div class="alert alert-warning py-2 px-3 text-xs mb-3">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 text-xs mb-3">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.change.update') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label class="text-xs font-weight-bold text-uppercase text-muted mb-1">Current / Temporary Password</label>
                    <input type="password" name="current_password" class="form-control" required placeholder="Enter current temporary password">
                </div>

                <div class="form-group mb-3">
                    <label class="text-xs font-weight-bold text-uppercase text-muted mb-1">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6" placeholder="At least 6 characters">
                </div>

                <div class="form-group mb-3">
                    <label class="text-xs font-weight-bold text-uppercase text-muted mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="6" placeholder="Re-enter new password">
                </div>

                <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm">
                    <i class="fas fa-check-circle mr-1"></i> Save Password & Continue to POS
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
