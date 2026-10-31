<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Master Admin Sign In | BILLINGPOC Platform Owner</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0b1120; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .master-card { width: 100%; max-width: 460px; border-radius: 14px; background: #0f172a; border: 1px solid #1e293b; box-shadow: 0 25px 45px -10px rgba(0,0,0,0.7); }
        .form-control-dark { background-color: #1e293b !important; border: 1px solid #334155 !important; color: #f8fafc !important; }
        .form-control-dark:focus { border-color: #f59e0b !important; box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25) !important; }
    </style>
</head>
<body>
<div class="master-card p-4 text-white">
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle mb-2" style="background: rgba(245, 158, 11, 0.15); border: 2px solid #f59e0b;">
            <i class="fas fa-shield-alt text-warning fa-2x"></i>
        </div>
        <h4 class="font-weight-bold mb-1 text-white">Software Master Admin Portal</h4>
        <div class="d-flex justify-content-center align-items-center">
            <span class="badge badge-warning text-dark font-weight-bold px-2 py-1 text-xs">
                <i class="fas fa-lock mr-1"></i> PLATFORM OWNER ONLY
            </span>
        </div>
        <p class="text-xs text-muted mt-2 mb-0" style="color: #94a3b8 !important;">
            Restricted software administration panel to provision client accounts, issue credentials, and manage platform subscribers.
        </p>
    </div>

    @if ($errors->has('master_id'))
        <div class="alert alert-danger py-2 px-3 text-xs mb-3">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $errors->first('master_id') }}
        </div>
    @endif

    <form action="{{ route('master-admin.login.post') }}" method="POST">
        @csrf
        <div class="form-group mb-3">
            <label class="text-xs font-weight-bold text-uppercase text-light mb-1">Master Admin ID</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-dark border-secondary text-warning"><i class="fas fa-user-shield"></i></span>
                </div>
                <input type="text" name="master_id" id="masterAdminId" class="form-control form-control-dark" 
                       value="{{ old('master_id', 'master@admin.com') }}" required autocomplete="username" placeholder="master@admin.com">
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="text-xs font-weight-bold text-uppercase text-light mb-1">Master Password</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-dark border-secondary text-warning"><i class="fas fa-key"></i></span>
                </div>
                <input type="password" name="master_password" id="masterAdminPassword" class="form-control form-control-dark" 
                       required autocomplete="current-password" placeholder="Enter Master Password">
                <div class="input-group-append">
                    <button class="btn btn-dark border-secondary text-light" type="button" onclick="toggleMasterPassword()">
                        <i class="fas fa-eye" id="toggleMasterIcon"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Dual Password Quick-Select Card -->
        <div class="p-3 mb-3 rounded" style="background: rgba(30, 41, 59, 0.7); border: 1px dashed #475569;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-xs font-weight-bold text-warning">
                    <i class="fas fa-shield-alt mr-1"></i> Two Master Passwords Configured:
                </span>
                <span class="text-xs text-muted" style="color: #94a3b8 !important;">Both Accepted</span>
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
                        <i class="fas fa-lock mr-1"></i> Password 2
                        <br><small class="text-white opacity-75">SoftwareDev@2026</small>
                    </button>
                </div>
            </div>
        </div>

        <div class="form-group mb-3 form-check">
            <input type="checkbox" name="remember" class="form-check-input" id="rememberMaster" checked>
            <label class="form-check-label text-xs" for="rememberMaster" style="color: #94a3b8 !important;">Remember Master Admin session</label>
        </div>

        <button type="submit" class="btn btn-warning btn-block py-2 font-weight-bold text-dark shadow-sm">
            <i class="fas fa-unlock-alt mr-1"></i> Sign In to Master Control
        </button>
    </form>

    <div class="text-center mt-3 pt-2 border-top border-secondary">
        <a href="{{ route('login') }}" class="text-xs text-muted" style="color: #94a3b8 !important;">
            <i class="fas fa-arrow-left mr-1"></i> Return to Client POS Sign In
        </a>
    </div>
</div>

<script>
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
</script>
</body>
</html>
