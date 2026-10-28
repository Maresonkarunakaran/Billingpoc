<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | BILLINGPOC GST Retail & Wholesale Hub</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-box { width: 100%; max-width: 460px; }
        .card-custom { border-radius: 14px; border: 1px solid #1e293b; background: #ffffff; box-shadow: 0 20px 30px -10px rgba(0,0,0,0.5); }
        .btn-brand { background: #2563eb; color: #fff; font-weight: 600; }
        .btn-brand:hover { background: #1d4ed8; color: #fff; }
        .quick-login-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-align: left;
            width: 100%;
        }
        .quick-login-card:hover {
            background: #eff6ff;
            border-color: #3b82f6;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
<div class="login-box">
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle bg-primary text-white mb-2 shadow">
            <i class="fas fa-cubes fa-2x"></i>
        </div>
        <h2 class="text-white font-weight-bold mb-0">BILLINGPOC</h2>
        <span class="text-light opacity-75 text-sm">Multi-Branch GST Retail & Wholesale ERP</span>
    </div>

    <div class="card card-custom p-4">
        <div class="card-body p-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="font-weight-bold text-dark mb-0">Sign In to POS</h5>
                    <p class="text-muted text-xs mb-0">Access your branch sales and ERP terminal.</p>
                </div>
                <a href="{{ route('register') }}" class="btn btn-sm btn-outline-primary font-weight-bold">
                    <i class="fas fa-store mr-1"></i> Sign Up
                </a>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2 text-sm mb-3">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $errors->first() }}
                </div>
            @endif

            <form id="loginForm" action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Email Address</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                        </div>
                        <input type="email" name="email" id="emailInput" class="form-control" value="{{ old('email', 'admin@pos.com') }}" required autofocus autocomplete="email">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="text-xs font-weight-bold text-uppercase text-muted mb-0">Password</label>
                        <span class="text-xs text-muted">Default: <code>password</code></span>
                    </div>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                        </div>
                        <input type="password" name="password" id="passwordInput" class="form-control" value="password" required autocomplete="current-password">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility()" title="Show/Hide Password">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3 form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="rememberMe" checked>
                    <label class="form-check-label text-sm text-muted" for="rememberMe">Remember me</label>
                </div>

                <button type="submit" class="btn btn-brand btn-block py-2 mb-3 shadow-sm font-weight-bold">
                    <i class="fas fa-sign-in-alt mr-1"></i> Sign In to Terminal
                </button>
            </form>

            <div class="text-center my-3">
                <span class="text-xs text-muted">Need a new business POS setup?</span>
                <a href="{{ route('register') }}" class="text-xs font-weight-bold ml-1 text-primary">
                    <i class="fas fa-user-plus mr-1"></i> Register New Business
                </a>
            </div>

            <div class="border-top pt-3 mt-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs font-weight-bold text-uppercase text-muted">1-Click Instant Demo Login:</span>
                    <span class="badge badge-success text-xs">Click to Sign In</span>
                </div>
                
                <div class="d-flex flex-column" style="gap: 8px;">
                    <button type="button" class="quick-login-card d-flex align-items-center justify-content-between" onclick="quickLogin('admin@pos.com')">
                        <div>
                            <span class="font-weight-bold text-dark d-block text-sm"><i class="fas fa-user-shield text-primary mr-1"></i> Super Admin (Owner)</span>
                            <small class="text-muted">admin@pos.com (Full multi-branch access)</small>
                        </div>
                        <i class="fas fa-arrow-right text-muted"></i>
                    </button>

                    <button type="button" class="quick-login-card d-flex align-items-center justify-content-between" onclick="quickLogin('cashier@pos.com')">
                        <div>
                            <span class="font-weight-bold text-dark d-block text-sm"><i class="fas fa-cash-register text-success mr-1"></i> POS Cashier</span>
                            <small class="text-muted">cashier@pos.com (Barcode POS billing & shift drawer)</small>
                        </div>
                        <i class="fas fa-arrow-right text-muted"></i>
                    </button>

                    <button type="button" class="quick-login-card d-flex align-items-center justify-content-between" onclick="quickLogin('manager@pos.com')">
                        <div>
                            <span class="font-weight-bold text-dark d-block text-sm"><i class="fas fa-store text-info mr-1"></i> Branch Store Manager</span>
                            <small class="text-muted">manager@pos.com (Sales, returns, expenses, reports)</small>
                        </div>
                        <i class="fas fa-arrow-right text-muted"></i>
                    </button>

                    <button type="button" class="quick-login-card d-flex align-items-center justify-content-between" onclick="quickLogin('inventory@pos.com')">
                        <div>
                            <span class="font-weight-bold text-dark d-block text-sm"><i class="fas fa-warehouse text-warning mr-1"></i> Warehouse & Stock Head</span>
                            <small class="text-muted">inventory@pos.com (Purchase receipts & stock transfers)</small>
                        </div>
                        <i class="fas fa-arrow-right text-muted"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility() {
        const pwd = document.getElementById('passwordInput');
        const icon = document.getElementById('toggleIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function quickLogin(email) {
        document.getElementById('emailInput').value = email;
        document.getElementById('passwordInput').value = 'password';
        document.getElementById('loginForm').submit();
    }
</script>
</body>
</html>
