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
    <title>Create Business Account | BILLINGPOC GST Retail & Wholesale ERP</title>

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

    <!-- Instant Bilingual Translation Engine (0ms UI dictionary & Google Translate integration) -->
    <script src="/js/translator.js"></script>

    <!-- Preconnect & Early Load for Google Translate -->
    <link rel="preconnect" href="https://translate.googleapis.com">
    <link rel="preconnect" href="https://translate.google.com">
    <link rel="dns-prefetch" href="https://translate.googleapis.com">
    <link rel="dns-prefetch" href="https://translate.google.com">
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async></script>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .register-container {
            width: 100%;
            max-width: 680px;
        }
        .card-custom {
            border-radius: 14px;
            border: 1px solid #1e293b;
            background: #ffffff;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.5);
        }
        .btn-brand {
            background: #2563eb;
            color: #fff;
            font-weight: 600;
        }
        .btn-brand:hover {
            background: #1d4ed8;
            color: #fff;
        }
        .section-header {
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            color: #2563eb;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 14px;
        }

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

        .lang-btn { font-size: 0.8rem; border-radius: 6px; padding: 3px 10px; font-weight: 600; }
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

    </style>
</head>
<body>
    <!-- Tamil Translation Loading Screen -->
    <div id="tamil-translate-loader" aria-live="polite">
        <div class="spinner-border text-primary mb-3" style="width: 2.2rem; height: 2.2rem;" role="status"></div>
        <div class="font-weight-bold text-white text-base mb-1">தமிழ் மொழிபெயர்ப்பு ஏற்றப்படுகிறது...</div>
        <small class="text-muted">Loading Tamil Translation...</small>
    </div>

    <!-- Hidden Google Translate Element -->
    <div id="google_translate_element" style="display:none;"></div>

<div class="register-container">
    <!-- Language Switcher Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-xs text-white opacity-75">
            <i class="fas fa-globe mr-1"></i> Language / மொழி:
        </span>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-light lang-btn lang-btn-en active" onclick="switchLanguage('en')">
                English
            </button>
            <button type="button" class="btn btn-outline-light lang-btn lang-btn-ta" onclick="switchLanguage('ta')">
                தமிழ்
            </button>
        </div>
    </div>
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle bg-primary text-white mb-2 shadow">
            <i class="fas fa-cubes fa-2x"></i>
        </div>
        <h2 class="text-white font-weight-bold mb-0">BILLINGPOC</h2>
        <span class="text-light opacity-75 text-sm">Create New Multi-Branch GST Retail & Wholesale ERP Account</span>
    </div>

    <div class="card card-custom p-4">
        <div class="card-body p-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="font-weight-bold text-dark mb-0">Register Your Business</h5>
                    <p class="text-muted text-xs mb-0">Set up your business, primary store branch, and administrator account.</p>
                </div>
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary font-weight-bold">
                    <i class="fas fa-sign-in-alt mr-1"></i> Sign In
                </a>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2 text-sm mb-3">
                    <div class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Please resolve the following errors:</div>
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.post') }}" method="POST">
                @csrf

                <!-- Section 1: Business Details -->
                <div class="section-header">
                    <i class="fas fa-store mr-1"></i> 1. Business & Store Information
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Business / Enterprise Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light"><i class="fas fa-building text-muted"></i></span>
                                </div>
                                <input type="text" name="business_name" class="form-control" value="{{ old('business_name') }}" placeholder="e.g. Sri Lakshmi Traders" required autofocus>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Store Type <span class="text-danger">*</span></label>
                            <select name="branch_type" class="form-control" required>
                                <option value="shop" {{ old('branch_type') == 'shop' ? 'selected' : '' }}>Retail / Wholesale Shop</option>
                                <option value="warehouse" {{ old('branch_type') == 'warehouse' ? 'selected' : '' }}>Warehouse / Godown</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Primary Branch Name</label>
                            <input type="text" name="branch_name" class="form-control" value="{{ old('branch_name') }}" placeholder="e.g. Main Road Outlet (Optional)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">GST State (Place of Supply) <span class="text-danger">*</span></label>
                            <select name="state_code" class="form-control" required>
                                @foreach($states as $code => $name)
                                    <option value="{{ $code }}" {{ old('state_code', '33') == $code ? 'selected' : '' }}>
                                        [{{ $code }}] {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">GSTIN (Optional)</label>
                            <input type="text" name="gstin" class="form-control text-uppercase" value="{{ old('gstin') }}" placeholder="15-digit GSTIN (e.g. 33AAAAA0000A1Z5)" maxlength="15">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">City / Town</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city') }}" placeholder="e.g. Chennai, Bangalore, Mumbai">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Owner Account Details -->
                <div class="section-header mt-3">
                    <i class="fas fa-user-shield mr-1"></i> 2. Administrator & Owner Account
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Owner Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                </div>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Ramesh Kumar" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Mobile Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                                </div>
                                <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="e.g. 9840012345" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Email Address (Login Username) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                        </div>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="e.g. owner@mybusiness.com" required autocomplete="email">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                </div>
                                <input type="password" name="password" id="registerPassword" class="form-control" placeholder="Min 6 characters" required autocomplete="new-password">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('registerPassword', 'toggleIcon1')">
                                        <i class="fas fa-eye" id="toggleIcon1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                </div>
                                <input type="password" name="password_confirmation" id="registerPasswordConf" class="form-control" placeholder="Re-enter password" required autocomplete="new-password">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('registerPasswordConf', 'toggleIcon2')">
                                        <i class="fas fa-eye" id="toggleIcon2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <div class="custom-control custom-checkbox text-xs text-muted">
                        <input type="checkbox" class="custom-control-input" id="termsAgree" required checked>
                        <label class="custom-control-label" for="termsAgree">
                            I agree that this business account will be configured with standard Indian GST tax rates, units, and inventory tracking.
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-brand btn-block py-2 mb-3 shadow-sm font-weight-bold">
                    <i class="fas fa-rocket mr-1"></i> Create Business Account & Launch POS
                </button>
            </form>

            <div class="text-center border-top pt-3 mt-3">
                <span class="text-sm text-muted">Already have a registered account?</span>
                <a href="{{ route('login') }}" class="font-weight-bold ml-1 text-primary">Sign In here</a>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(inputId, iconId) {
        const field = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

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
