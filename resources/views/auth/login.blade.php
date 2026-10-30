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

    <!-- Preconnect to Google Translate for fast translation initialization -->
    <link rel="preconnect" href="https://translate.googleapis.com">
    <link rel="preconnect" href="https://translate.google.com">
    <link rel="dns-prefetch" href="https://translate.googleapis.com">
    <link rel="dns-prefetch" href="https://translate.google.com">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-box { width: 100%; max-width: 440px; }
        .card-custom { border-radius: 14px; border: 1px solid #1e293b; background: #ffffff; box-shadow: 0 20px 30px -10px rgba(0,0,0,0.5); }
        .btn-brand { background: #2563eb; color: #fff; font-weight: 600; }
        .btn-brand:hover { background: #1d4ed8; color: #fff; }

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

    </style>
</head>
<body>
<div class="login-box">
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
                        <input type="email" name="email" id="emailInput" class="form-control" value="{{ old('email') }}" placeholder="name@store.com" required autofocus autocomplete="email">
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

            <div class="text-center my-2">
                <span class="text-xs text-muted">Need a new business POS setup?</span>
                <a href="{{ route('register') }}" class="text-xs font-weight-bold ml-1 text-primary">
                    <i class="fas fa-user-plus mr-1"></i> Register New Business
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Google Translate Element -->
<div id="google_translate_element" style="display:none;"></div>

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

    // Robust Cookie Helper for Google Translate across host / domain
    function setGoogtransCookie(val) {
        var host = window.location.hostname;
        var isIpOrLocal = (host === 'localhost' || host === '127.0.0.1' || /^(\d{1,3}\.){3}\d{1,3}$/.test(host));
        if (!val) {
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
            window.location.reload();
            return;
        }

        // Switching to Tamil ('ta')
        setGoogtransCookie('/en/ta');
        document.documentElement.classList.add('translating-tamil');
        updateLangButtons('ta');

        var select = document.querySelector('.goog-te-combo');
        if (select) {
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === 'ta') {
                    select.selectedIndex = i;
                    break;
                }
            }
            select.value = 'ta';

            select.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
            select.dispatchEvent(new Event('input', { bubbles: true, cancelable: true }));
            if (window.jQuery) {
                window.jQuery(select).val('ta').trigger('change');
            }
            if (typeof select.onchange === 'function') {
                select.onchange();
            }

            setTimeout(function() {
                if (!document.documentElement.classList.contains('translated-ltr')) {
                    window.location.reload();
                }
            }, 200);
        } else {
            window.location.reload();
        }
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
    }

    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,ta',
            autoDisplay: false
        }, 'google_translate_element');

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

    // PWA Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').catch(function(err) {
                console.warn('[PWA] ServiceWorker fail:', err);
            });
        });
    }
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
