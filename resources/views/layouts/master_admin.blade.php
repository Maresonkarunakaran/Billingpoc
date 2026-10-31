<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Master Admin Panel') | BILLINGPOC Platform Owner</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; }
        .main-sidebar-master { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%) !important; }
        .brand-link-master { background-color: #0b1120 !important; border-bottom: 1px solid #334155 !important; }
        .nav-sidebar-master .nav-link { border-radius: 8px; margin: 3px 10px; color: #94a3b8; font-weight: 500; font-size: 0.88rem; }
        .nav-sidebar-master .nav-link:hover { color: #f8fafc; background: rgba(255,255,255,0.06); }
        .nav-sidebar-master .nav-link.active { background: #f59e0b !important; color: #0f172a !important; font-weight: 700; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35); }
        .card-master { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .badge-master { background-color: #fef3c7; color: #92400e; font-weight: 600; }
        .table-custom th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 0.74rem; letter-spacing: 0.5px; border-top: none; }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom shadow-xs">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="navbar-text font-weight-bold text-dark ml-2">
                    <i class="fas fa-shield-alt text-warning mr-1"></i> Software Master Administration Panel
                </span>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item mr-2">
                <a href="{{ route('master-admin.clients.create') }}" class="btn btn-warning btn-sm font-weight-bold text-dark shadow-xs">
                    <i class="fas fa-plus-circle mr-1"></i> Provision Client
                </a>
            </li>
            <li class="nav-item mr-3">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm" title="View POS Terminal">
                    <i class="fas fa-cash-register mr-1"></i> <span class="d-none d-md-inline">POS Terminal</span>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-dark font-weight-medium" data-toggle="dropdown" href="#">
                    <i class="fas fa-user-shield text-warning mr-1"></i> Master Admin
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow border-0">
                    <div class="dropdown-header text-muted text-xs">Platform Software Owner</div>
                    <a href="{{ route('master-admin.dashboard') }}" class="dropdown-item">
                        <i class="fas fa-tachometer-alt mr-2 text-primary"></i> Master Dashboard
                    </a>
                    <a href="{{ route('master-admin.clients.index') }}" class="dropdown-item">
                        <i class="fas fa-users-cog mr-2 text-success"></i> Client Accounts
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt mr-2"></i> Master Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar main-sidebar-master sidebar-dark-warning elevation-2">
        <a href="{{ route('master-admin.dashboard') }}" class="brand-link brand-link-master text-decoration-none px-3">
            <span class="d-flex align-items-center">
                <span class="rounded-circle p-2 mr-2 d-inline-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.2); width: 34px; height: 34px;">
                    <i class="fas fa-shield-alt text-warning"></i>
                </span>
                <div>
                    <span class="brand-text font-weight-bold text-white" style="font-size: 1rem;">BILLINGPOC</span>
                    <small class="d-block text-warning font-weight-bold text-xs" style="letter-spacing: 0.5px;">MASTER ADMIN</small>
                </div>
            </span>
        </a>

        <div class="sidebar px-0">
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar nav-sidebar-master flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('master-admin.dashboard') }}" class="nav-link {{ request()->routeIs('master-admin.dashboard') || request()->routeIs('master-admin.home') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard Overview</p>
                        </a>
                    </li>

                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold px-3 mt-2">Client Management</li>

                    <li class="nav-item">
                        <a href="{{ route('master-admin.clients.index') }}" class="nav-link {{ request()->routeIs('master-admin.clients.index') || request()->routeIs('master-admin.clients.show') || request()->routeIs('master-admin.clients.edit') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users-cog"></i>
                            <p>All Client Accounts</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('master-admin.clients.create') }}" class="nav-link {{ request()->routeIs('master-admin.clients.create') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-plus text-warning"></i>
                            <p class="font-weight-bold">Provision New Client</p>
                        </a>
                    </li>

                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold px-3 mt-2">Security & Activity</li>

                    <li class="nav-item">
                        <a href="{{ route('master-admin.audit-logs') }}" class="nav-link {{ request()->routeIs('master-admin.audit-logs') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Audit Activity Logs</p>
                        </a>
                    </li>

                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold px-3 mt-2">Quick Navigation</li>

                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link text-white-50">
                            <i class="nav-icon fas fa-desktop"></i>
                            <p>Open POS Billing App</p>
                        </a>
                    </li>

                    <li class="nav-item mt-4">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-block btn-sm mx-3" style="width: calc(100% - 24px);">
                                <i class="fas fa-sign-out-alt mr-1"></i> Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        @yield('content')
    </div>

    <!-- Main Footer -->
    <footer class="main-footer text-xs text-muted py-2 bg-white border-top">
        <div class="float-right d-none d-sm-inline">
            Platform Master Architecture &bull; Dual-Password Protected
        </div>
        <strong>BILLINGPOC Master Administration Panel</strong> &copy; {{ date('Y') }} All rights reserved.
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: "4000"
    };

    @if(session('success'))
        toastr.success("{{ session('success') }}");
    @endif

    @if(session('error'))
        toastr.error("{{ session('error') }}");
    @endif
</script>
@stack('scripts')
</body>
</html>
