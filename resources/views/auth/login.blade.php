<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | BILLINGPOC GST Retail & Wholesale Hub</title>

    <!-- PWA Desktop & Mobile App Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BILLINGPOC">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-box { width: 100%; max-width: 440px; }
        .card-custom { border-radius: 14px; border: 1px solid #1e293b; background: #ffffff; box-shadow: 0 20px 30px -10px rgba(0,0,0,0.5); }
        .btn-brand { background: #2563eb; color: #fff; font-weight: 600; }
        .btn-brand:hover { background: #1d4ed8; color: #fff; }
    </style>
</head>
<body>

<div class="login-box">
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle bg-primary text-white mb-2 shadow">
            <i class="fas fa-cubes fa-2x"></i>
        </div>
        <h2 class="text-white font-weight-bold mb-0 notranslate">BILLINGPOC</h2>
        <span class="text-light opacity-75 text-sm">Multi-Branch GST Retail & Wholesale ERP</span>
    </div>

    <div class="card card-custom p-4">
        <div class="card-body p-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="font-weight-bold text-dark mb-0">Sign In to POS</h5>
                    <p class="text-muted text-xs mb-0">Access your branch sales and ERP terminal.</p>
                </div>
            </div>

            @if(session('info'))
                <div class="alert alert-info py-2 text-sm mb-3">
                    <i class="fas fa-info-circle mr-1"></i> {{ session('info') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success py-2 text-sm mb-3">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger py-2 text-sm mb-3">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $errors->first() }}
                </div>
            @endif

            <form id="loginForm" action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">User ID / Client ID / Email</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                        </div>
                        <input type="text" name="email" id="emailInput" class="form-control" value="{{ old('email') }}" placeholder="e.g. CLT0001, STF0001, admin@pos.com" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="text-xs font-weight-bold text-uppercase text-muted mb-1 d-block">Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                        </div>
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
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

            <div class="text-center mt-3 pt-2 border-top">
                <span class="text-xs text-muted">
                    <i class="fas fa-lock text-secondary mr-1"></i> Authorized Client Access Only &bull; User IDs & passwords are created & assigned by the Software Administrator.
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Floating Master Admin Portal Button (Bottom-Right Corner) -->
<div style="position: fixed; bottom: 20px; right: 20px; z-index: 1050;">
    <button type="button" 
            class="btn shadow-lg d-flex align-items-center justify-content-center"
            data-toggle="modal" 
            data-target="#masterAdminModal"
            title="Software Master Admin Portal (Owner Access)"
            id="masterAdminBtn"
            style="width: 46px; height: 46px; border-radius: 50%; background: #0f172a; border: 2px solid #f59e0b; color: #f59e0b; box-shadow: 0 4px 15px rgba(0,0,0,0.5); cursor: pointer; transition: all 0.2s ease;">
        <i class="fas fa-user-shield fa-lg"></i>
    </button>
</div>

<!-- Master Admin Login Modal -->
<div class="modal fade" id="masterAdminModal" tabindex="-1" role="dialog" aria-labelledby="masterAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; background: #0f172a; color: #f8fafc;">
            <div class="modal-header border-0 pb-1" style="background: #1e293b;">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 mr-2 d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.15); width: 40px; height: 40px; border: 1px solid #f59e0b;">
                        <i class="fas fa-shield-alt text-warning"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold text-white mb-0" id="masterAdminModalLabel" style="font-size: 1.05rem;">
                            Software Master Admin Portal
                        </h5>
                        <span class="badge badge-warning text-dark font-weight-bold px-2 py-0.5" style="font-size: 0.65rem;">
                            Software Owner Only
                        </span>
                    </div>
                </div>
                <button type="button" class="close text-white opacity-75" data-dismiss="modal" aria-label="Close" style="outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('master-admin.login') }}" method="POST">
                @csrf
                <div class="modal-body pt-3 pb-3 px-4">
                    <p class="text-xs mb-3" style="color: #94a3b8 !important;">
                        Master software administration portal. Manage all clients, view all user accounts, and provision custom Client User IDs & Passwords.
                    </p>

                    @if ($errors->has('master_id'))
                        <div class="alert alert-danger py-2 px-3 text-xs mb-3">
                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $errors->first('master_id') }}
                        </div>
                    @endif

                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-light mb-1">Master Admin ID</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-dark border-secondary text-warning"><i class="fas fa-user-shield"></i></span>
                            </div>
                            <input type="text" name="master_id" id="masterAdminId" class="form-control bg-dark border-secondary text-white" 
                                   value="{{ old('master_id', 'master@admin.com') }}" required autocomplete="username" placeholder="master@admin.com">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-light mb-1">Master Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-dark border-secondary text-warning"><i class="fas fa-key"></i></span>
                            </div>
                            <input type="password" name="master_password" id="masterAdminPassword" class="form-control bg-dark border-secondary text-white" 
                                   required autocomplete="current-password" placeholder="Enter Master Password">
                            <div class="input-group-append">
                                <button class="btn btn-dark border-secondary text-light" type="button" onclick="toggleMasterPassword()">
                                    <i class="fas fa-eye" id="toggleMasterIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Dual Password Quick-Select Card -->
                    <div class="p-2 mb-3 rounded" style="background: rgba(30, 41, 59, 0.8); border: 1px dashed #475569;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-xs font-weight-bold text-warning">
                                <i class="fas fa-lock mr-1"></i> Two Master Passwords Available:
                            </span>
                            <span class="text-xs text-muted" style="color: #94a3b8 !important;">Either is accepted</span>
                        </div>
                        <div class="row no-gutters">
                            <div class="col-6 pr-1">
                                <button type="button" class="btn btn-sm btn-outline-warning btn-block text-xs py-1" onclick="useMasterPass(1)">
                                    <i class="fas fa-key mr-1"></i> Password 1
                                    <br><small class="text-white opacity-75">MasterAdmin#2026</small>
                                </button>
                            </div>
                            <div class="col-6 pl-1">
                                <button type="button" class="btn btn-sm btn-outline-info btn-block text-xs py-1" onclick="useMasterPass(2)">
                                    <i class="fas fa-shield-alt mr-1"></i> Password 2
                                    <br><small class="text-white opacity-75">SoftwareDev@2026</small>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0 form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="rememberMaster" checked>
                        <label class="form-check-label text-xs" for="rememberMaster" style="color: #94a3b8 !important;">Remember Master Admin session</label>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex justify-content-between align-items-center">
                    <a href="{{ route('master-admin.login') }}" class="text-xs text-warning" title="Open Master Admin in Full Page">
                        <i class="fas fa-external-link-alt mr-1"></i> Dedicated Full Page
                    </a>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary text-light mr-1" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning font-weight-bold px-3 text-dark shadow-sm">
                            <i class="fas fa-unlock-alt mr-1"></i> Access Master Panel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

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

    function toggleMasterPassword() {
        const pwd = document.getElementById('masterAdminPassword');
        const icon = document.getElementById('toggleMasterIcon');
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

    function useMasterPass(index) {
        document.getElementById('masterAdminId').value = 'master@admin.com';
        if (index === 1) {
            document.getElementById('masterAdminPassword').value = 'MasterAdmin#2026';
        } else {
            document.getElementById('masterAdminPassword').value = 'SoftwareDev@2026';
        }
    }

    @if ($errors->has('master_id') || session('open_master_modal'))
        $(document).ready(function() {
            $('#masterAdminModal').modal('show');
        });
    @endif

    // PWA Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').catch(function(err) {
                console.warn('[PWA] ServiceWorker fail:', err);
            });
        });
    }
</script>
</body>
</html>
