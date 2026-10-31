@php
    $cookieHeader = request()->header('cookie', '');
    $serverLang = request()->cookie('app_language') 
        ?? request()->cookie('billingpoc_lang')
        ?? ($_COOKIE['app_language'] ?? ($_COOKIE['billingpoc_lang'] ?? (preg_match('/(?:^|;\s*)(?:app_language|billingpoc_lang)=([^;]+)/', $cookieHeader, $m) ? $m[1] : 'en')));
    $isTamil = ($serverLang === 'ta');
@endphp
<!DOCTYPE html>
<html lang="{{ $isTamil ? 'ta' : 'en' }}" class="{{ $isTamil ? 'lang-tamil translating-tamil' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} | {{ config('app.name', 'BILLINGPOC') }}</title>

    <!-- Synchronous Anti-Flash Language State Detection (0ms before body render) -->
    <script>
        (function() {
            var l = localStorage.getItem('app_language') || localStorage.getItem('billingpoc_lang');
            if (!l) {
                var m = document.cookie.match(/(?:^|;)\s*app_language=([^;]+)/) || document.cookie.match(/(?:^|;)\s*billingpoc_lang=([^;]+)/);
                if (m) l = m[1];
            }
            if (l === 'ta') {
                document.documentElement.classList.add('lang-tamil', 'translating-tamil');
                document.documentElement.setAttribute('lang', 'ta');
            } else if (l === 'en') {
                document.documentElement.classList.remove('lang-tamil', 'translating-tamil');
                document.documentElement.setAttribute('lang', 'en');
            }
        })();
    </script>

    <!-- Instant Bilingual Translation Engine (0ms UI dictionary & Google Translate integration) -->
    <script src="/js/translator.js"></script>

    <!-- PWA Desktop & Mobile App Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BILLINGPOC">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <!-- Preconnect & Early Load for Google Translate -->
    <link rel="preconnect" href="https://translate.googleapis.com">
    <link rel="preconnect" href="https://translate.google.com">
    <link rel="dns-prefetch" href="https://translate.googleapis.com">
    <link rel="dns-prefetch" href="https://translate.google.com">
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async></script>

    <!-- Google Font: Source Sans Pro & Inter -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Theme style (AdminLTE 3.2) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; font-size: 0.92rem; }
        .main-sidebar { background-color: #1a222f !important; }
        .layout-navbar-fixed .main-sidebar .brand-link { height: 57px; padding-top: 4px; padding-bottom: 4px; }
        .brand-link { background-color: #111827 !important; border-bottom: 1px solid #374151 !important; }
        .brand-text { font-weight: 700; letter-spacing: -0.5px; }
        .nav-sidebar .nav-link { border-radius: 6px; margin: 2px 8px; font-weight: 500; }
        .nav-sidebar .nav-link.active { background-color: #2563eb !important; color: #fff !important; }
        .kpi-card { border-radius: 10px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: transform 0.15s ease-in-out; }
        .kpi-card:hover { transform: translateY(-2px); }
        .badge-gst { background-color: #e0e7ff; color: #3730a3; font-weight: 600; font-size: 0.78rem; }
        .btn-pos-launch { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white !important; font-weight: 600; border-radius: 8px; box-shadow: 0 2px 6px rgba(16,185,129,0.3); }
        .btn-pos-launch:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .table-custom th { background-color: #f8fafc; color: #475569; font-weight: 600; border-top: none; text-transform: uppercase; font-size: 0.76rem; letter-spacing: 0.5px; }
        .rupee::before { content: '₹ '; }

        /* Completely Hide Google Translate Top Banner Bar & Injected Frames */
        .goog-te-banner-frame,
        .goog-te-banner-frame.skiptranslate,
        iframe.goog-te-banner-frame,
        iframe.skiptranslate,
        .VIpgJd-ZVi9od-ORHb-OEVmcd,
        .VIpgJd-ZVi9od-ORHb-OEVmcd-ti6hGc,
        .VIpgJd-ZVi9od-aZ2wEe-wOHMyf,
        .VIpgJd-ZVi9od-aZ2wEe-OiiCO,
        .VIpgJd-yAWdhb-hvhGLS-mrxup,
        #goog-gt-tt,
        .goog-te-balloon-frame,
        .goog-tooltip,
        div[id*=":1.container"],
        div[id*=":2.container"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0px !important;
            width: 0px !important;
            max-height: 0px !important;
            pointer-events: none !important;
            position: absolute !important;
            left: -9999px !important;
            top: -9999px !important;
            z-index: -9999 !important;
        }

        body {
            top: 0px !important;
            position: static !important;
        }

        .goog-text-highlight {
            background: none !important;
            box-shadow: none !important;
        }

        .lang-btn { font-size: 0.78rem; border-radius: 6px; padding: 2px 8px; font-weight: 700; transition: all 0.15s ease; }
        .lang-btn.active { background: #2563eb !important; color: #fff !important; border-color: #2563eb !important; }

        /* Smooth anti-flash: when user has chosen Tamil, hide untranslated English text until translated */
        html.lang-tamil:not(.lang-ready) body,
        html.translating-tamil:not(.translation-ready) body {
            visibility: hidden !important;
            opacity: 0 !important;
        }
        html.lang-tamil.lang-ready body,
        html.translating-tamil.translation-ready body {
            visibility: visible !important;
            opacity: 1 !important;
            transition: opacity 0.1s ease-in;
        }

        #tamil-translate-loader {
            display: none !important;
        }

        @media (max-width: 767.98px) {
            body { padding-bottom: 60px; }
            .mobile-bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 1040;
                background: #ffffff;
            }
            .mobile-bottom-nav .nav-item {
                flex: 1;
                color: #64748b;
                text-decoration: none;
                font-size: 0.72rem;
            }
            .mobile-bottom-nav .nav-item i {
                font-size: 1.15rem;
                margin-bottom: 2px;
            }
            .mobile-bottom-nav .nav-item.active {
                color: #2563eb;
                font-weight: 700;
            }
            .main-footer {
                margin-bottom: 50px;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <!-- Tamil Translation Loading Screen (Smooth anti-flash on page change) -->
    <div id="tamil-translate-loader" aria-live="polite">
        <div class="spinner-border text-primary mb-3" style="width: 2.2rem; height: 2.2rem;" role="status"></div>
        <div class="font-weight-bold text-dark text-base mb-1">தமிழ் மொழிபெயர்ப்பு ஏற்றப்படுகிறது...</div>
        <small class="text-muted">Loading Tamil Translation...</small>
    </div>

    <!-- Hidden Google Translate Element -->
    <div id="google_translate_element" style="display:none;"></div>

<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('dashboard') }}" class="nav-link font-weight-medium">Overview</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto align-items-center">
            <!-- Active Branch Switcher -->
            @php
                $currentBranch = \App\Models\Branch::find(session('active_branch_id'));
                $userBranches = auth()->user()->role?->slug === 'super-admin'
                    ? \App\Models\Branch::where('business_id', auth()->user()->business_id)->where('is_active', true)->get()
                    : auth()->user()->branches;
            @endphp
            <li class="nav-item mr-2">
                <form action="{{ route('branch.switch') }}" method="POST" class="d-inline" id="branchSwitchForm">
                    @csrf
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light border-right-0 text-muted d-none d-sm-inline-block">
                                <i class="fas fa-store mr-1 text-primary"></i> Branch:
                            </span>
                        </div>
                        <select name="branch_id" class="form-control form-control-sm font-weight-bold" onchange="document.getElementById('branchSwitchForm').submit()">
                            @foreach($userBranches as $b)
                                <option value="{{ $b->id }}" {{ ($currentBranch && $currentBranch->id == $b->id) ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </li>

            <!-- Prominent POS Terminal Launch Button -->
            <li class="nav-item mr-2 mr-sm-3">
                <a href="{{ route('pos.index') }}" class="btn btn-pos-launch btn-sm px-2 px-sm-3 py-1 font-weight-bold">
                    <i class="fas fa-cash-register mr-1"></i> <span class="d-none d-sm-inline">Open POS Terminal</span><span class="d-inline d-sm-none">POS</span>
                </a>
            </li>

            <!-- Language Switcher: English / தமிழ் -->
            <li class="nav-item mr-2">
                <div class="btn-group btn-group-sm" role="group" aria-label="Language Switcher">
                    <button type="button" class="btn btn-outline-secondary lang-btn lang-btn-en active" onclick="switchLanguage('en')" title="Switch to English">
                        EN
                    </button>
                    <button type="button" class="btn btn-outline-secondary lang-btn lang-btn-ta" onclick="switchLanguage('ta')" title="தமிழுக்கு மாறவும்">
                        தமிழ்
                    </button>
                </div>
            </li>

            <!-- Install Desktop App Button (PWA) -->
            <li class="nav-item mr-2" id="pwa-install-nav" style="display: none;">
                <button type="button" class="btn btn-outline-primary btn-sm px-2 font-weight-bold" id="btn-pwa-install" title="Install BILLINGPOC as standalone Desktop Software">
                    <i class="fas fa-desktop mr-1"></i> <span class="d-none d-md-inline">Install App</span>
                </button>
            </li>

            <!-- Master Admin Button -->
            @if(auth()->user()->is_master_admin)
            <li class="nav-item mr-2">
                <a href="{{ route('master-admin.dashboard') }}" class="btn btn-warning btn-sm px-2 font-weight-bold text-dark shadow-sm" title="Software Master Admin Portal">
                    <i class="fas fa-shield-alt mr-1"></i> <span class="d-none d-md-inline">Master Admin</span>
                </a>
            </li>
            @endif

            <!-- Staff Management Button -->
            @if(auth()->user()->hasPermission('staff_management'))
            <li class="nav-item mr-2">
                <a href="{{ route('users.index') }}" class="btn btn-outline-info btn-sm px-2 font-weight-bold" title="Staff Management: Manage Staff Logins & Permissions">
                    <i class="fas fa-users-cog mr-1"></i> <span class="d-none d-md-inline">Staff Management</span>
                </a>
            </li>
            @endif

            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user-circle fa-lg text-secondary mr-1"></i>
                    <span class="d-none d-md-inline font-weight-medium">{{ auth()->user()->name }}</span>
                    <span class="badge badge-secondary ml-1">{{ auth()->user()->role?->name ?? 'User' }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right shadow-sm border-0">
                    <div class="dropdown-header bg-light">
                        <strong>{{ auth()->user()->business?->name }}</strong><br>
                        <small class="text-muted">{{ auth()->user()->email }}</small>
                    </div>
                    @if(auth()->user()->is_master_admin)
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('master-admin.dashboard') }}" class="dropdown-item text-warning font-weight-bold">
                            <i class="fas fa-shield-alt mr-2 text-warning"></i> Software Master Admin Portal
                        </a>
                    @endif
                    @if(auth()->user()->hasPermission('staff_management'))
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('users.index') }}" class="dropdown-item">
                            <i class="fas fa-users-cog mr-2 text-info"></i> Staff Management
                        </a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-2">
        <!-- Brand Logo -->
        <a href="{{ auth()->user()?->canAccessDashboard() ? route('dashboard') : (auth()->user()?->hasPermission('pos_terminal') ? route('pos.index') : '#') }}" class="brand-link d-flex align-items-center px-3">
            <i class="fas fa-cubes text-info fa-2x mr-2"></i>
            <div>
                <span class="brand-text text-white font-weight-bold d-block" style="line-height:1.2;">BILLINGPOC</span>
                <small class="text-muted" style="font-size:0.7rem;">Multi-Branch GST POS</small>
            </div>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu">
                    @if(auth()->user()?->canAccessDashboard())
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('pos_terminal') || auth()->user()->hasPermission('sales_history'))
                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold mt-2">Sales</li>
                    @if(auth()->user()->hasPermission('pos_terminal'))
                    <li class="nav-item">
                        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->is('pos*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cash-register text-success"></i>
                            <p>POS Terminal</p>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->hasPermission('sales_history'))
                    <li class="nav-item">
                        <a href="{{ route('sales.index') }}" class="nav-link {{ request()->is('sales*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice text-primary"></i>
                            <p>Sales & Invoices</p>
                        </a>
                    </li>
                    @endif
                    @endif

                    @if(auth()->user()->hasPermission('purchase_management') || auth()->user()->hasPermission('inventory_balance'))
                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold mt-2">Purchases & Inventory</li>
                    @if(auth()->user()->hasPermission('purchase_management'))
                    <li class="nav-item">
                        <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->is('purchases*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart text-info"></i>
                            <p>Purchases & Receipts</p>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->hasPermission('inventory_balance'))
                    <li class="nav-item">
                        <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->is('inventory') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-warehouse text-warning"></i>
                            <p>Inventory Balances</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('reports.inventory') }}" class="nav-link {{ request()->is('reports/inventory*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calculator text-warning"></i>
                            <p>Inventory Valuation</p>
                        </a>
                    </li>
                    @endif
                    @endif

                    @if(auth()->user()->hasPermission('products_pricing') || auth()->user()->hasPermission('reports_analytics') || auth()->user()->hasPermission('expenses') || auth()->user()->hasPermission('opening_closing_cash'))
                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold mt-2">Catalog & Accounts</li>
                    @if(auth()->user()->hasPermission('products_pricing'))
                    <li class="nav-item">
                        <a href="{{ route('products.index') }}" class="nav-link {{ (request()->is('products') || request()->is('products/create') || (request()->is('products/*') && !request()->is('products/reports*'))) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-boxes text-danger"></i>
                            <p>Products & Pricing</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('categories.index') }}" class="nav-link {{ request()->is('categories*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tags"></i>
                            <p>Categories</p>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->hasPermission('reports_analytics'))
                    <li class="nav-item">
                        <a href="{{ route('products.reports') }}" class="nav-link {{ request()->is('products/reports*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line text-warning"></i>
                            <p>Report</p>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->hasPermission('expenses'))
                    <li class="nav-item">
                        <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->is('expenses*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-receipt text-danger"></i>
                            <p>Expenses</p>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->hasPermission('opening_closing_cash'))
                    <li class="nav-item mb-1">
                        <a href="{{ route('cash-register.index') }}" class="nav-link {{ (request()->is('opening-closing-cash*') || request()->is('cash-register*')) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-coins text-success"></i>
                            <p>Opening & Closing Cash</p>
                        </a>
                    </li>
                    @endif
                    @endif

                    @if(auth()->user()->is_master_admin)
                    <li class="nav-header text-uppercase text-xs text-warning font-weight-bold mt-2">Platform Master</li>
                    <li class="nav-item mb-4">
                        <a href="{{ route('master-admin.dashboard') }}" class="nav-link {{ request()->is('master-admin*') ? 'active' : '' }}" style="background: rgba(245, 158, 11, 0.12); border-left: 3px solid #f59e0b;">
                            <i class="nav-icon fas fa-shield-alt text-warning"></i>
                            <p class="font-weight-bold text-warning">Master Admin Panel</p>
                        </a>
                    </li>
                    @endif
                </ul>
            </nav>
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper bg-light pt-3 pb-5">
        <div class="content">
            <div class="container-fluid">
                <!-- Flash messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <strong>Please correct the errors below:</strong>
                        <ul class="mb-0 mt-1 pl-3">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer text-sm border-top py-2">
        <div class="float-right d-none d-sm-inline">
            <span class="badge badge-success"><i class="fas fa-circle text-xs mr-1"></i> Active Branch: {{ $currentBranch?->name ?? 'None' }}</span>
        </div>
        <strong>{{ auth()->user()->business?->legal_name ?? 'BILLINGPOC Enterprises' }}</strong> &bull; GSTIN: <code>{{ auth()->user()->business?->gstin }}</code>
    </footer>
</div>
<!-- ./wrapper -->

<!-- Scripts: jQuery, Bootstrap 4, AdminLTE 3.2, Chart.js, SweetAlert2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Global helper for formatting Indian Rupee
    function formatINR(val) {
        return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(val);
    }
</script>
<!-- Mobile Bottom Navigation Bar (Visible on mobile phones) -->
<nav class="mobile-bottom-nav d-block d-md-none border-top shadow-lg">
    <div class="d-flex justify-content-around py-2">
        @if(auth()->user()?->canAccessDashboard())
        <a href="{{ route('dashboard') }}" class="nav-item text-center {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}">
            <i class="fas fa-chart-pie d-block"></i>
            <span>Dashboard</span>
        </a>
        @endif
        @if(auth()->user()->hasPermission('pos_terminal'))
        <a href="{{ route('pos.index') }}" class="nav-item text-center {{ request()->is('pos*') ? 'active' : '' }}">
            <i class="fas fa-cash-register d-block"></i>
            <span>POS Bill</span>
        </a>
        @endif
        @if(auth()->user()->hasPermission('products_pricing'))
        <a href="{{ route('products.index') }}" class="nav-item text-center {{ request()->is('products*') ? 'active' : '' }}">
            <i class="fas fa-boxes d-block"></i>
            <span>Items</span>
        </a>
        @endif
        @if(auth()->user()->hasPermission('expenses'))
        <a href="{{ route('expenses.index') }}" class="nav-item text-center {{ request()->is('expenses*') ? 'active' : '' }}">
            <i class="fas fa-receipt d-block"></i>
            <span>Expenses</span>
        </a>
        @endif
        @if(auth()->user()->hasPermission('sales_history'))
        <a href="{{ route('sales.index') }}" class="nav-item text-center {{ request()->is('sales*') ? 'active' : '' }}">
            <i class="fas fa-file-invoice d-block"></i>
            <span>Sales</span>
        </a>
        @endif
        <a href="javascript:void(0)" onclick="toggleMobileLanguage()" class="nav-item text-center">
            <i class="fas fa-language d-block"></i>
            <span id="mobileLangLabel">தமிழ்</span>
        </a>
    </div>
</nav>

<script>
    // PWA Service Worker Registration & Installation Prompt
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(reg) {
                console.log('[PWA] ServiceWorker registered with scope:', reg.scope);
            }).catch(function(err) {
                console.warn('[PWA] ServiceWorker registration failed:', err);
            });
        });
    }

    let deferredPrompt = null;
    const pwaInstallNav = document.getElementById('pwa-install-nav');
    const btnPwaInstall = document.getElementById('btn-pwa-install');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (pwaInstallNav) {
            pwaInstallNav.style.display = 'block';
        }
    });

    if (btnPwaInstall) {
        btnPwaInstall.addEventListener('click', async () => {
            if (!deferredPrompt) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Install Desktop App',
                        html: 'To install <strong>BILLINGPOC</strong> to your desktop:<br><br>Click the <strong>Install icon <i class="fas fa-download"></i></strong> in your Chrome or Edge address bar (top-right), or select <strong>Menu &rarr; Install BILLINGPOC</strong>.',
                        confirmButtonText: 'Got it'
                    });
                } else {
                    alert('To install BILLINGPOC, click the Install App icon in your browser address bar.');
                }
                return;
            }
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log('[PWA] User choice:', outcome);
            deferredPrompt = null;
            if (pwaInstallNav) {
                pwaInstallNav.style.display = 'none';
            }
        });
    }

    window.addEventListener('appinstalled', () => {
        console.log('[PWA] Installed successfully!');
        if (pwaInstallNav) {
            pwaInstallNav.style.display = 'none';
        }
    });

    @if(!empty($shouldShowOpeningCashModal))
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined') {
            setTimeout(function() {
                $('#autoOpeningCashModal').modal('show');
            }, 300);
        }
    });
    @endif
</script>

@if(!empty($shouldShowOpeningCashModal))
<!-- Automatic Opening Cash Popup Modal (Prompted when opening app before first bill print) -->
<div class="modal fade" id="autoOpeningCashModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-success text-white py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title font-weight-bold mb-0">
                    <i class="fas fa-coins text-warning mr-2"></i>Daily Store Opening — Add Opening Cash
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.85;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('cash-register.store') }}" method="POST">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $activeBranchId ?? session('active_branch_id', auth()->user()?->default_branch_id) }}">
                <input type="hidden" name="register_date" value="{{ now()->toDateString() }}">
                <input type="hidden" name="status" value="open">

                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-3 text-sm">
                        <i class="fas fa-info-circle text-primary mr-1"></i>
                        Welcome! No bill has been printed yet today. Please enter today's opening cash float before printing your first bill.
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark text-sm">
                            Opening Cash Amount (₹) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light font-weight-bold">₹</span>
                            </div>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="opening_cash" 
                                   class="form-control font-weight-bold text-primary" 
                                   value="{{ !empty($suggestedOpeningCash) && $suggestedOpeningCash > 0 ? number_format($suggestedOpeningCash, 2, '.', '') : '0.00' }}" 
                                   required 
                                   autofocus 
                                   placeholder="0.00">
                        </div>
                        <small class="form-text text-muted">Initial cash drawer float for {{ now()->format('d M Y') }}.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark text-sm">Notes / Shift Remark <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Morning counter cash float">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Later / Skip</button>
                    <button type="submit" class="btn btn-success font-weight-bold px-3">
                        <i class="fas fa-check-circle mr-1"></i> Save Opening Cash
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@stack('scripts')
</body>
</html>
