<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} | {{ config('app.name', 'BILLINGPOC') }}</title>

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

    <!-- Preconnect to Google Translate for fast translation initialization -->
    <link rel="preconnect" href="https://translate.googleapis.com">
    <link rel="preconnect" href="https://translate.google.com">
    <link rel="dns-prefetch" href="https://translate.googleapis.com">
    <link rel="dns-prefetch" href="https://translate.google.com">

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
        <a href="{{ route('dashboard') }}" class="brand-link d-flex align-items-center px-3">
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
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold mt-2">Sales</li>
                    <li class="nav-item">
                        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->is('pos*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cash-register text-success"></i>
                            <p>POS Terminal</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('sales.index') }}" class="nav-link {{ request()->is('sales*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice text-primary"></i>
                            <p>Sales & Invoices</p>
                        </a>
                    </li>

                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold mt-2">Purchases & Inventory</li>
                    <li class="nav-item">
                        <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->is('purchases*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart text-info"></i>
                            <p>Purchases & Receipts</p>
                        </a>
                    </li>
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

                    <li class="nav-header text-uppercase text-xs text-muted font-weight-bold mt-2">Product Catalog</li>
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
                    <li class="nav-item mb-4">
                        <a href="{{ route('products.reports') }}" class="nav-link {{ request()->is('products/reports*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line text-warning"></i>
                            <p>Report</p>
                        </a>
                    </li>
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
        <a href="{{ route('dashboard') }}" class="nav-item text-center {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}">
            <i class="fas fa-chart-pie d-block"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('pos.index') }}" class="nav-item text-center {{ request()->is('pos*') ? 'active' : '' }}">
            <i class="fas fa-cash-register d-block"></i>
            <span>POS Bill</span>
        </a>
        <a href="{{ route('products.index') }}" class="nav-item text-center {{ request()->is('products*') ? 'active' : '' }}">
            <i class="fas fa-boxes d-block"></i>
            <span>Items</span>
        </a>
        <a href="{{ route('expenses.index') }}" class="nav-item text-center {{ request()->is('expenses*') ? 'active' : '' }}">
            <i class="fas fa-receipt d-block"></i>
            <span>Expenses</span>
        </a>
        <a href="{{ route('sales.index') }}" class="nav-item text-center {{ request()->is('sales*') ? 'active' : '' }}">
            <i class="fas fa-file-invoice d-block"></i>
            <span>Sales</span>
        </a>
        <a href="javascript:void(0)" onclick="toggleMobileLanguage()" class="nav-item text-center">
            <i class="fas fa-language d-block"></i>
            <span id="mobileLangLabel">தமிழ்</span>
        </a>
    </div>
</nav>

<!-- Hidden Google Translate Element -->
<div id="google_translate_element" style="display:none;"></div>

<script>
    // Robust Cookie Helper for Google Translate across host / domain
    function setGoogtransCookie(val) {
        var host = window.location.hostname;
        var isIpOrLocal = (host === 'localhost' || host === '127.0.0.1' || /^(\d{1,3}\.){3}\d{1,3}$/.test(host));
        if (!val) {
            // Delete cookie completely so Google Translate reverts cleanly to original English
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            if (!isIpOrLocal) {
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=" + host + "; path=/;";
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=." + host + "; path=/;";
            }
        } else {
            document.cookie = "googtrans=" + val + "; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/;";
            if (!isIpOrLocal) {
                document.cookie = "googtrans=" + val + "; expires=Thu, 31 Dec 2099 23:59:59 UTC; domain=" + host + "; path=/;";
                document.cookie = "googtrans=" + val + "; expires=Thu, 31 Dec 2099 23:59:59 UTC; domain=." + host + "; path=/;";
            }
        }
    }

    // Language switcher logic - Immediate on first click & zero English flash
    function switchLanguage(lang) {
        localStorage.setItem('billingpoc_lang', lang);

        if (lang === 'en') {
            setGoogtransCookie(null);
            document.documentElement.classList.remove('translating-tamil');
            updateLangButtons('en');

            var select = document.querySelector('.goog-te-combo');
            if (select) {
                select.selectedIndex = 0;
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
            }
            // Clean reload to restore unmodified English DOM
            window.location.reload();
            return;
        }

        // Switching to Tamil ('ta')
        setGoogtransCookie('/en/ta');
        document.documentElement.classList.add('translating-tamil');
        updateLangButtons('ta');

        var select = document.querySelector('.goog-te-combo');
        if (select) {
            var found = false;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === 'ta') {
                    select.selectedIndex = i;
                    found = true;
                    break;
                }
            }
            select.value = 'ta';

            // Trigger all listeners: native change, input, jQuery change, and onchange
            select.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
            select.dispatchEvent(new Event('input', { bubbles: true, cancelable: true }));
            if (window.jQuery) {
                window.jQuery(select).val('ta').trigger('change');
            }
            if (typeof select.onchange === 'function') {
                select.onchange();
            }

            // If translation hasn't taken effect immediately, reload so it boots directly into Tamil
            setTimeout(function() {
                if (!document.documentElement.classList.contains('translated-ltr')) {
                    window.location.reload();
                }
            }, 200);
        } else {
            // Widget element not ready yet; reload so cookie translates on boot
            window.location.reload();
        }
    }

    function toggleMobileLanguage() {
        var current = localStorage.getItem('billingpoc_lang') || 'en';
        var next = (current === 'ta') ? 'en' : 'ta';
        switchLanguage(next);
    }

    function updateLangButtons(lang) {
        document.querySelectorAll('.lang-btn-en').forEach(function(el) {
            if (lang === 'en') {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });
        document.querySelectorAll('.lang-btn-ta').forEach(function(el) {
            if (lang === 'ta') {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });
        var mobileLabel = document.getElementById('mobileLangLabel');
        if (mobileLabel) {
            mobileLabel.innerText = (lang === 'ta' ? 'English' : 'தமிழ்');
        }
    }

    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,ta',
            autoDisplay: false
        }, 'google_translate_element');

        // If Tamil is active, ensure the combo triggers if not already translated
        var saved = localStorage.getItem('billingpoc_lang') || 'en';
        if (saved === 'ta') {
            var checkTimer = setInterval(function() {
                var select = document.querySelector('.goog-te-combo');
                if (select) {
                    clearInterval(checkTimer);
                    if (select.value !== 'ta') {
                        select.value = 'ta';
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }, 40);
            setTimeout(function() { clearInterval(checkTimer); }, 1500);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var match = document.cookie.match(/(?:^|;)\s*googtrans=\/en\/([a-z]{2})/);
        var saved = match ? match[1] : (localStorage.getItem('billingpoc_lang') || 'en');
        updateLangButtons(saved);

        // Permanently suppress and eradicate any Google Translate top banner or body shifting
        var removeGoogleBanner = function() {
            if (document.body && document.body.style.top && document.body.style.top !== '0px') {
                document.body.style.top = '0px';
            }
            var banners = document.querySelectorAll('iframe.skiptranslate, .VIpgJd-ZVi9od-ORHb-OEVmcd, .goog-te-banner-frame');
            banners.forEach(function(b) {
                b.style.setProperty('display', 'none', 'important');
                b.style.setProperty('visibility', 'hidden', 'important');
                b.style.setProperty('height', '0px', 'important');
                b.style.setProperty('opacity', '0', 'important');
                b.style.setProperty('pointer-events', 'none', 'important');
            });
        };

        var observer = new MutationObserver(removeGoogleBanner);
        observer.observe(document.documentElement, { attributes: true, childList: true, subtree: true });
        setInterval(removeGoogleBanner, 300);
    });

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
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

@stack('scripts')
</body>
</html>
