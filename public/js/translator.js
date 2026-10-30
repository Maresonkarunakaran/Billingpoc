/**
 * BILLINGPOC Instant Bilingual Translation Engine (English & தமிழ்)
 * Provides instant 0ms in-memory UI translation + Google Translate integration
 */
(function(window, document) {
    'use strict';

    // 1. Comprehensive UI Translation Dictionary (English -> Tamil)
    const DICT_TA = {
        // Navigation & Menu
        'Overview': 'முகப்பு',
        'Dashboard': 'டாஷ்போர்டு',
        'POS Terminal': 'பிஓஎஸ் பில்லிங்',
        'Product Catalog': 'பொருட்கள் பட்டியல்',
        'Products & Pricing': 'பொருட்கள் & விலை',
        'Categories': 'பொருள் வகைகள்',
        'Barcode Labels': 'பார்கோடு லேபிள்கள்',
        'Units & Packaging': 'அளவீடுகள் & பேக்கிங்',
        'Inventory Management': 'இருப்பு மேலாண்மை',
        'Inventory Balances': 'இருப்பு விவரம்',
        'Stock Adjustments': 'இருப்பு சரிசெய்தல்',
        'Stock Transfers': 'இருப்பு பரிமாற்றம்',
        'Sales & Invoices': 'விற்பனை & பில்கள்',
        'Purchases & POs': 'கொள்முதல் & ஆர்டர்கள்',
        'Purchase Orders': 'கொள்முதல் ஆர்டர்கள்',
        'Goods Receipts': 'பொருட்கள் ரசீது',
        'Suppliers & Vendors': 'சப்ளையர்கள்',
        'Suppliers': 'சப்ளையர்கள்',
        'Customer Accounts': 'வாடிக்கையாளர்கள்',
        'Customers': 'வாடிக்கையாளர்கள்',
        'Operating Expenses': 'இயக்கச் செலவுகள்',
        'Expenses': 'செலவுகள்',
        'Cash Shifts': 'பணப் பதிவு ஷிப்ட்',
        'Reports & Analytics': 'அறிக்கைகள் & ஆய்வு',
        'Reports': 'அறிக்கைகள்',
        'Report': 'அறிக்கை',
        'Daily Sales': 'தினசரி விற்பனை',
        'GST (GSTR-1)': 'ஜிஎஸ்டி அறிக்கை',
        'Profit & Loss': 'லாப நஷ்ட அறிக்கை',
        'Business Settings': 'வணிக அமைப்புகள்',
        'Settings': 'அமைப்புகள்',
        'Audit Logs': 'தணிக்கை பதிவு',
        'Logout': 'வெளியேறு',
        'Sign Out': 'வெளியேறு',
        'Open POS Terminal': 'பிஓஎஸ் பில்லிங்',
        'POS': 'பிஓஎஸ்',
        'POS Bill': 'பிஓஎஸ் பில்',
        'Items': 'பொருட்கள்',
        'Install App': 'ஆப் நிறுவவும்',

        // POS Terminal
        'Current Order Billing': 'தற்போதைய பில்லிங்',
        'Current Order is Empty': 'பில்லிங் பட்டியல் காலியாக உள்ளது',
        'Retail Rate': 'சில்லறை விலை',
        'Wholesale Rate': 'மொத்த விலை',
        'Item Description': 'பொருள் விவரம்',
        'Quantity': 'அளவு',
        'Qty': 'அளவு',
        'Unit Price': 'அலகு விலை',
        'Line Total': 'மொத்த தொகை',
        'Subtotal (Before Tax):': 'கூட்டல் தொகை (வரிக்கு முன்):',
        'Subtotal (Before Tax)': 'கூட்டல் தொகை (வரிக்கு முன்)',
        'Discount:': 'தள்ளுபடி:',
        'Discount': 'தள்ளுபடி',
        'Taxable Amount:': 'வரிக்குரிய தொகை:',
        'Taxable Amount': 'வரிக்குரிய தொகை',
        'Total Payable:': 'செலுத்த வேண்டிய தொகை:',
        'Total Payable': 'செலுத்த வேண்டிய தொகை',
        'Proceed to Pay (F2)': 'பணம் செலுத்த தொடரவும் (F2)',
        'Proceed to Pay': 'பணம் செலுத்த தொடரவும்',
        'Clear': 'அழிக்கவும்',
        'All Categories': 'அனைத்து வகைகள்',
        'Out of Stock': 'இருப்பு இல்லை',
        'in stock': 'இருப்பில் உள்ளது',
        'Walk-in Customer (Cash / Non-GST)': 'நேரடி வாடிக்கையாளர் (ரொக்கம்)',
        'Finalize & Print Invoice': 'பில் முடித்து அச்சிடவும்',
        'Checkout & Settle Payment': 'பணம் செலுத்தி முடிக்கவும்',
        'Open Shift Now': 'ஷிப்ட் திறக்கவும்',
        'Daily Cash Shift Register': 'தினசரி பணப் பதிவு',
        'Add to Bill': 'பில்லில் சேர்க்க',
        'Tendered Amount': 'வாங்கிய ரொக்கத் தொகை',
        'Change Due': 'மீதி தர வேண்டிய தொகை',

        // Reports
        'Purchase vs. Sales Margin Analysis': 'கொள்முதல் மற்றும் விற்பனை லாப விளிம்பு பகுப்பாய்வு',
        'Low Quantity Stock/Reorder items': 'குறைந்த இருப்பு / மறுஆர்டர் பொருட்கள் பட்டியல்',
        'Top to low qty sold items': 'அதிகம் முதல் குறைவாக விற்ற பொருட்கள்',
        'Reorder Alert (< 5 Qty)': 'மறுஆர்டர் எச்சரிக்கை (< 5 இருப்பு)',
        'Margin %': 'லாப விளிம்பு %',
        'Landed Cost': 'கொள்முதல் அடக்க விலை',
        'Selling Price': 'விற்பனை விலை',
        'Total Sold Qty': 'மொத்த விற்பனை அளவு',
        'Stock in Hand': 'தற்போதைய இருப்பு',

        // Common Actions & Badges
        'Add': 'சேர்க்க',
        'Add New': 'புதியது சேர்க்க',
        'Edit': 'திருத்து',
        'Delete': 'நீக்கு',
        'Save': 'சேமி',
        'Save Changes': 'மாற்றங்களை சேமிக்கவும்',
        'Cancel': 'ரத்து',
        'Filter': 'வடிகட்டுக',
        'Reset': 'மீட்டமை',
        'Search': 'தேடு',
        'Close': 'மூடுக',
        'Submit': 'சமர்ப்பி',
        'Confirm': 'உறுதி செய்',
        'Back': 'பின்செல்',
        'Paid': 'செலுத்தப்பட்டது',
        'PAID': 'செலுத்தப்பட்டது',
        'Unpaid': 'செலுத்தப்படவில்லை',
        'UNPAID': 'செலுத்தப்படவில்லை',
        'Partial': 'பகுதி செலுத்தியது',
        'PARTIAL': 'பகுதி செலுத்தியது',
        'Received': 'பெறப்பட்டது',
        'RECEIVED': 'பெறப்பட்டது',
        'Ordered': 'ஆர்டர் செய்யப்பட்டது',
        'ORDERED': 'ஆர்டர் செய்யப்பட்டது',
        'Active': 'செயலில் உள்ளது',
        'Inactive': 'செயலற்றது',
        'Today': 'இன்று',
        '☀️ Today': '☀️ இன்று',
        'Yesterday': 'நேற்று',
        'This Month': 'இந்த மாதம்',
        '📅 This Month': '📅 இந்த மாதம்',
        'All Invoices': 'அனைத்து பில்கள்',
        'All Expenses': 'அனைத்து செலவுகள்',
        'All Suppliers': 'அனைத்து சப்ளையர்கள்',
        'All Branches': 'அனைத்து கிளைகள்',
        'THERE IS NO RECORD IN THIS DATE': 'இந்த தேதியில் எந்த பதிவும் இல்லை',
        'Actions': 'செயல்கள்',
        'Action': 'செயல்',
        'Status': 'நிலை',
        'Date': 'தேதி',
        'Date & Time': 'தேதி & நேரம்',
        'Branch': 'கிளை',
        'Customer': 'வாடிக்கையாளர்',
        'Supplier': 'சப்ளையர்',
        'Total': 'மொத்தம்',
        'Amount': 'தொகை',
        'Subtotal': 'கூட்டல் தொகை',
        'Tax': 'வரி',
        'GST': 'ஜிஎஸ்டி',
        'Final Total': 'இறுதி மொத்தம்',
        'Invoice Number': 'பில் எண்',
        'Invoice #': 'பில் எண் #',
        'PO Number': 'கொள்முதல் எண்',
        'PO #': 'கொள்முதல் எண் #',
        'Expense #': 'செலவு எண் #',
        'Expense Category': 'செலவு வகை',
        'Payment Mode': 'பணம் செலுத்தும் முறை',
        'Cash': 'ரொக்கம்',
        'UPI': 'யுபிஐ',
        'UPI / QR': 'யுபிஐ / QR',
        'Card': 'கார்டு',
        'Bank Transfer': 'வங்கி பரிமாற்றம்',
        'Print': 'அச்சிடுக',
        'Thermal Print': 'தெர்மல் பிரிண்ட்',
        'View Tax Invoice': 'வரி பில் காண்க',
        'Issue Return / Credit Note': 'பொருளைத் திருப்பிப் பெறுக / கிரெடிட் நோட்',

        // Auth Pages
        'Sign In': 'உள்நுழைக',
        'Sign in': 'உள்நுழைக',
        'Sign in to your account': 'உங்கள் கணக்கில் உள்நுழைக',
        'Email Address': 'மின்னஞ்சல் முகவரி',
        'Password': 'கடவுச்சொல்',
        'Remember Me': 'என்னை நினைவில் கொள்',
        'Forgot Password?': 'கடவுச்சொல் மறந்துவிட்டதா?',
        'Register': 'பதிவு செய்க',
        'Register a new membership': 'புதிய கணக்கை பதிவு செய்க',
        'Full Name': 'முழு பெயர்',
        'Confirm Password': 'கடவுச்சொல்லை உறுதிப்படுத்துக',
        'I agree to the terms': 'விதிமுறைகளை ஒப்புக்கொள்கிறேன்',
        'Already have an account?': 'ஏற்கனவே கணக்கு உள்ளதா?',
        'Default Admin Credentials': 'இயல்புநிலை நிர்வாகி விவரங்கள்',
        'Click credentials to autofill': 'தானாக நிரப்ப கிளிக் செய்யவும்'
    };

    // Placeholder translations
    const DICT_PLACEHOLDERS = {
        'Search product name...': 'பொருளின் பெயரைத் தேடுங்கள்...',
        'Scan Barcode / SKU and hit Enter...': 'பார்கோடு / SKU ஸ்கேன் செய்யவும்...',
        'Type to search and add a product...': 'பொருளைத் தேடி பில்லில் சேர்க்கவும்...',
        'Search customer by name, phone, or GSTIN...': 'வாடிக்கையாளர் பெயர், எண், GSTIN தேடவும்...',
        'From Date': 'தொடக்கத் தேதி',
        'To Date': 'முடிவுத் தேதி',
        'Search Invoice #, Customer, Phone...': 'பில் எண், வாடிக்கையாளர் தேடவும்...',
        'Search PO #, Invoice #...': 'ஆர்டர் எண், பில் எண் தேடவும்...',
        'Search Expense #, Ref...': 'செலவு எண், குறிப்பு தேடவும்...',
        'Email Address': 'மின்னஞ்சல் முகவரி',
        'Password': 'கடவுச்சொல்',
        'Full Name': 'முழு பெயர்'
    };

    // 2. Cookie Management Helper
    function setGoogtransCookie(val) {
        var host = window.location.hostname;
        document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + host + ";";
        document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + host + ";";

        if (val) {
            document.cookie = "googtrans=" + val + "; path=/; SameSite=Lax; max-age=31536000";
        }
    }

    function isTamilActive() {
        try {
            var saved = localStorage.getItem('billingpoc_lang');
            var match = document.cookie.match(/(?:^|;)\s*googtrans=([^;]+)/);
            return (saved === 'ta') || (match && match[1].indexOf('/ta') !== -1);
        } catch (e) {
            return false;
        }
    }

    // 3. Instant In-Memory DOM Translation Walker
    function translateDomNode(node) {
        if (!node) return;

        // Skip non-translatable containers
        if (node.nodeType === Node.ELEMENT_NODE) {
            var el = node;
            var tag = el.tagName ? el.tagName.toLowerCase() : '';
            if (tag === 'script' || tag === 'style' || tag === 'code' || tag === 'pre' || el.classList.contains('notranslate')) {
                return;
            }

            // Translate placeholders
            if (el.hasAttribute('placeholder')) {
                var ph = el.getAttribute('placeholder');
                if (DICT_PLACEHOLDERS[ph]) {
                    el.setAttribute('placeholder', DICT_PLACEHOLDERS[ph]);
                }
            }

            // Translate titles / tooltips
            if (el.hasAttribute('title')) {
                var title = el.getAttribute('title');
                if (DICT_TA[title]) {
                    el.setAttribute('title', DICT_TA[title]);
                }
            }
        }

        // Translate text nodes
        if (node.nodeType === Node.TEXT_NODE) {
            var text = node.nodeValue ? node.nodeValue.trim() : '';
            if (text && DICT_TA[text]) {
                node.nodeValue = node.nodeValue.replace(text, DICT_TA[text]);
            }
            return;
        }

        // Recurse child nodes
        var child = node.firstChild;
        while (child) {
            translateDomNode(child);
            child = child.nextSibling;
        }
    }

    function applyInstantTamil() {
        if (!isTamilActive()) return;
        document.documentElement.setAttribute('lang', 'ta');
        document.documentElement.classList.add('translating-tamil');
        translateDomNode(document.body || document.documentElement);
        // Instantly mark translation ready so UI is displayed immediately without waiting
        document.documentElement.classList.add('translation-ready');
    }

    // 4. Google Translate Integration (For dynamic product descriptions & general body)
    window.googleTranslateElementInit = function() {
        var el = document.getElementById('google_translate_element');
        if (!el) {
            el = document.createElement('div');
            el.id = 'google_translate_element';
            el.style.display = 'none';
            (document.body || document.documentElement).appendChild(el);
        }

        if (window.google && window.google.translate && window.google.translate.TranslateElement) {
            try {
                new window.google.translate.TranslateElement({
                    pageLanguage: 'en',
                    includedLanguages: 'en,ta',
                    autoDisplay: false
                }, 'google_translate_element');
            } catch (e) {
                console.warn('[Translate] Init warning:', e);
            }
        }

        if (isTamilActive()) {
            triggerGoogleCombo('ta');
        }
    };

    function triggerGoogleCombo(targetLang) {
        var count = 0;
        var interval = setInterval(function() {
            count++;
            var combo = document.querySelector('.goog-te-combo');
            if (combo) {
                clearInterval(interval);
                if (combo.value !== targetLang) {
                    combo.value = targetLang;
                    combo.dispatchEvent(new Event('change', { bubbles: true }));
                    if (typeof combo.onchange === 'function') {
                        combo.onchange();
                    }
                    if (window.jQuery) {
                        window.jQuery(combo).val(targetLang).trigger('change');
                    }
                }
                document.documentElement.classList.add('translation-ready');
            } else if (count > 40) {
                clearInterval(interval);
                document.documentElement.classList.add('translation-ready');
            }
        }, 50);
    }

    // 5. Public Language Switcher Function (Used by Navbar & Mobile Menu buttons)
    window.switchLanguage = function(lang) {
        localStorage.setItem('billingpoc_lang', lang);

        if (lang === 'en') {
            setGoogtransCookie(null);
            document.documentElement.classList.remove('translating-tamil');
            document.documentElement.setAttribute('lang', 'en');
            updateLangButtons('en');

            var combo = document.querySelector('.goog-te-combo');
            if (combo) {
                combo.value = '';
                combo.dispatchEvent(new Event('change', { bubbles: true }));
            }
            // Clean reload to restore unmodified English DOM
            window.location.reload();
            return;
        }

        // Switching to Tamil ('ta')
        setGoogtransCookie('/en/ta');
        document.documentElement.classList.add('translating-tamil');
        document.documentElement.setAttribute('lang', 'ta');
        updateLangButtons('ta');

        // 1) Apply instant in-memory translations immediately (0ms delay!)
        applyInstantTamil();

        // 2) Trigger Google Translate in-place for any dynamic body content
        triggerGoogleCombo('ta');
    };

    window.toggleMobileLanguage = function() {
        var current = localStorage.getItem('billingpoc_lang') || 'en';
        var next = (current === 'ta') ? 'en' : 'ta';
        window.switchLanguage(next);
    };

    function updateLangButtons(lang) {
        document.querySelectorAll('.lang-btn-en').forEach(function(el) {
            if (lang === 'en') el.classList.add('active');
            else el.classList.remove('active');
        });
        document.querySelectorAll('.lang-btn-ta').forEach(function(el) {
            if (lang === 'ta') el.classList.add('active');
            else el.classList.remove('active');
        });
        var mobileLabel = document.getElementById('mobileLangLabel');
        if (mobileLabel) {
            mobileLabel.innerText = (lang === 'ta' ? 'English' : 'தமிழ்');
        }
    }

    // 6. Early Immediate Boot Check (In Head)
    if (isTamilActive()) {
        document.documentElement.classList.add('translating-tamil');
        document.documentElement.setAttribute('lang', 'ta');
    }

    // 7. On DOM Ready: Translate instantly + Suppress Banners + Observe mutations
    document.addEventListener('DOMContentLoaded', function() {
        var activeLang = isTamilActive() ? 'ta' : 'en';
        updateLangButtons(activeLang);

        if (activeLang === 'ta') {
            applyInstantTamil();
        }

        // Safety timeout: ensure page is visible
        setTimeout(function() {
            document.documentElement.classList.add('translation-ready');
        }, 300);

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
        var bannerObserver = new MutationObserver(removeGoogleBanner);
        bannerObserver.observe(document.documentElement, { attributes: true, childList: true, subtree: true });
        setInterval(removeGoogleBanner, 300);

        // Observe dynamically inserted content (e.g. POS cart items, AJAX search results)
        if (window.MutationObserver && document.body) {
            var dynamicContentObserver = new MutationObserver(function(mutations) {
                if (!isTamilActive()) return;
                for (var i = 0; i < mutations.length; i++) {
                    var m = mutations[i];
                    if (m.type === 'childList') {
                        for (var j = 0; j < m.addedNodes.length; j++) {
                            var n = m.addedNodes[j];
                            if (n.nodeType === Node.ELEMENT_NODE) {
                                if (n.classList.contains('goog-te-spinner-pos') ||
                                    n.classList.contains('skiptranslate') ||
                                    (n.id && n.id.indexOf('goog') !== -1)) {
                                    continue;
                                }
                            }
                            translateDomNode(n);
                        }
                    }
                }
            });
            dynamicContentObserver.observe(document.body, { childList: true, subtree: true });
        }
    });

})(window, document);
