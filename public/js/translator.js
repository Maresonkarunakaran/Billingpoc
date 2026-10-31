/**
 * BILLINGPOC Centralized Bilingual Translation Engine (English & தமிழ்)
 * Provides:
 *  - Single-click instant language switching (0ms in-memory DOM translation)
 *  - 100% persistent state across routes, refreshes, sessions, and browser back/forward
 *  - Absolute ZERO English flash during page navigation
 *  - Comprehensive UI dictionary for POS, Billing, Reports, Inventory, Accounting & Auth
 *  - Dynamic mutation observer for POS cart items, AJAX search results & modals
 *  - Graceful non-blocking Google Translate background synchronization
 */
(function(window, document) {
    'use strict';

    var STORAGE_KEY = 'app_language';
    var LEGACY_KEY = 'billingpoc_lang';
    var COOKIE_NAME = 'app_language';

    // =========================================================================
    // 1. COMPREHENSIVE BILINGUAL DICTIONARY (English -> தமிழ்)
    // =========================================================================
    var DICT_TA = {
        // --- Navigation, Brand & Top Navbar ---
        'Overview': 'முகப்பு',
        'Dashboard': 'டாஷ்போர்டு',
        'Sales': 'விற்பனை',
        'POS Terminal': 'பிஓஎஸ் பில்லிங்',
        'Sales & Invoices': 'விற்பனை & பில்கள்',
        'Purchases & Inventory': 'கொள்முதல் & இருப்பு',
        'Purchases & Receipts': 'கொள்முதல் & ரசீதுகள்',
        'Purchases & POs': 'கொள்முதல் & ஆர்டர்கள்',
        'Purchase Orders': 'கொள்முதல் ஆர்டர்கள்',
        'Goods Receipts': 'பொருட்கள் ரசீதுகள்',
        'Inventory Balances': 'இருப்பு விவரம்',
        'Inventory Valuation': 'இருப்பு மதிப்பீடு',
        'Inventory Management': 'இருப்பு மேலாண்மை',
        'Stock Adjustments': 'இருப்பு சரிசெய்தல்',
        'Stock Adjustment': 'இருப்பு சரிசெய்தல்',
        'Stock Transfers': 'இருப்பு பரிமாற்றம்',
        'Stock Transfer': 'இருப்பு பரிமாற்றம்',
        'Product Catalog': 'பொருட்கள் பட்டியல்',
        'Products & Pricing': 'பொருட்கள் & விலை',
        'Categories': 'பொருள் வகைகள்',
        'Barcode Labels': 'பார்கோடு லேபிள்கள்',
        'Units & Packaging': 'அளவீடுகள் & பேக்கிங்',
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
        'GST (GSTR-1)': 'ஜிஎஸ்டி அறிக்கை (GSTR-1)',
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
        'Branch': 'கிளை',
        'Branch:': 'கிளை:',
        'Active Branch:': 'செயல்படும் கிளை:',
        'Active Branch': 'செயல்படும் கிளை',
        'Multi-Branch GST POS': 'பல கிளை ஜிஎஸ்டி பில்லிங்',
        'All Consolidated Branches': 'அனைத்து கிளைகள் (கூட்டு)',
        '🏢 All Branches (Consolidated)': '🏢 அனைத்து கிளைகள் (கூட்டு)',
        'All Branches (Consolidated)': 'அனைத்து கிளைகள் (கூட்டு)',

        // --- Executive Dashboard ---
        'Executive Business Dashboard': 'முதன்மை வணிக டாஷ்போர்டு',
        'POS Billing': 'பிஓஎஸ் பில்லிங்',
        'Add Product': 'புதிய பொருள் சேர்க்க',
        'P&L Report': 'லாப நஷ்ட அறிக்கை',
        'Timeframe:': 'கால அளவு:',
        'Timeframe': 'கால அளவு',
        '☀️ Today': '☀️ இன்று',
        'Today': 'இன்று',
        'Yesterday': 'நேற்று',
        '📅 This Month': '📅 இந்த மாதம்',
        'This Month': 'இந்த மாதம்',
        '7 Days': '7 நாட்கள்',
        '30 Days': '30 நாட்கள்',
        'Last Month': 'கடந்த மாதம்',
        'Store:': 'கடை / கிளை:',
        'Store': 'கடை / கிளை',
        'How Profit is Calculated': 'லாபம் எவ்வாறு கணக்கிடப்படுகிறது',
        'Net Margin:': 'நிகர லாப விளிம்பு:',
        'Net Margin': 'நிகர லாப விளிம்பு',
        'Full P&L Statement →': 'முழு லாப நஷ்ட அறிக்கை →',
        'Full P&L Statement': 'முழு லாப நஷ்ட அறிக்கை',
        '1. Total Sales': '1. மொத்த விற்பனை',
        'Total Sales': 'மொத்த விற்பனை',
        'Turnover billed': 'பில் செய்யப்பட்ட விற்றுமுதல்',
        '2. Product Cost': '2. பொருள் கொள்முதல் அடக்கம்',
        'Product Cost': 'பொருள் கொள்முதல் அடக்கம்',
        'Inventory cost': 'சரக்கு இருப்பு கொள்முதல் விலை',
        '3. Gross Margin': '3. மொத்த லாப விளிம்பு',
        'Gross Margin': 'மொத்த லாப விளிம்பு',
        'Item markup': 'பொருள் கூடுதல் லாப மதிப்பு',
        '4. Shop Expenses': '4. கடை இயக்கச் செலவுகள்',
        'Shop Expenses': 'கடை இயக்கச் செலவுகள்',
        'Rent, bills, wages': 'வாடகை, மின்கட்டணம், சம்பளம்',
        '5. Net Take-Home': '5. நிகர லாபம்',
        'Net Take-Home': 'நிகர லாபம்',
        'Net Profit': 'நிகர லாபம்',
        'Operating Loss': 'இயக்க நஷ்டம்',
        'Net Sales Revenue': 'நிகர விற்பனை வருவாய்',
        'Estimated Net Profit': 'மதிப்பிடப்பட்ட நிகர லாபம்',
        'Today Live:': 'இன்று நேரலை:',
        'Today Live': 'இன்று நேரலை',
        'invoices billed': 'பில்கள் போடப்பட்டது',
        'bills': 'பில்கள்',
        'Low Stock Alerts': 'குறைந்த இருப்பு எச்சரிக்கைகள்',
        'Stock Balances': 'இருப்பு விவரம்',
        'Recent Sales Transactions': 'சமீபத்திய விற்பனை பரிவர்த்தனைகள்',
        'Recent Purchases': 'சமீபத்திய கொள்முதல்கள்',
        'Top Selling Products': 'அதிகம் விற்பனையான பொருட்கள்',

        // --- POS Terminal ---
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
        'Taxable': 'வரிக்குரியது',
        'GST (CGST + SGST):': 'ஜிஎஸ்டி (CGST + SGST):',
        'GST (CGST + SGST)': 'ஜிஎஸ்டி (CGST + SGST)',
        'Round Off:': 'முழுமைப்படுத்தல்:',
        'Round Off': 'முழுமைப்படுத்தல்',
        'Total Payable:': 'செலுத்த வேண்டிய தொகை:',
        'Total Payable': 'செலுத்த வேண்டிய தொகை',
        'Total Payable Amount': 'செலுத்த வேண்டிய மொத்தத் தொகை',
        'Proceed to Pay (F2)': 'பணம் செலுத்த தொடரவும் (F2)',
        'Proceed to Pay': 'பணம் செலுத்த தொடரவும்',
        'Clear': 'அழிக்கவும்',
        'All Categories': 'அனைத்து வகைகள்',
        'Out of Stock': 'இருப்பு இல்லை',
        'in stock': 'இருப்பில் உள்ளது',
        'Walk-in Customer (Cash / Non-GST)': 'நேரடி வாடிக்கையாளர் (ரொக்கம்)',
        'Finalize & Print Invoice': 'பில் முடித்து அச்சிடவும்',
        'Checkout & Settle Payment': 'பணம் செலுத்தி முடிக்கவும்',
        'Inclusive of GST': 'ஜிஎஸ்டி வரியுடன் சேர்த்து',
        'Discount on Invoice': 'பில் தள்ளுபடி',
        'Order Notes / Ref': 'குறிப்பு / எண்',
        'Select Payment Method': 'பணம் செலுத்தும் முறையைத் தேர்ந்தெடுக்கவும்',
        'Cash': 'ரொக்கம்',
        'UPI / QR': 'யுபிஐ / QR',
        'UPI': 'யுபிஐ',
        'Card': 'கார்டு',
        'Credit': 'கடன்',
        'Amount Tendered (₹)': 'வாங்கிய ரொக்கத் தொகை (₹)',
        'Amount Tendered': 'வாங்கிய ரொக்கத் தொகை',
        'Tendered Amount': 'வாங்கிய ரொக்கத் தொகை',
        'Change to Return': 'மீதி தர வேண்டிய தொகை',
        'Change Due': 'மீதி தர வேண்டிய தொகை',
        'READY': 'தயார்',
        'Exact': 'சரியான தொகை',
        'UPI UTR / Card Auth Code': 'யுபிஐ UTR / கார்டு எண்',
        'Register New Customer': 'புதிய வாடிக்கையாளரை பதிவு செய்க',
        'Customer / Business Name *': 'வாடிக்கையாளர் / வணிகப் பெயர் *',
        'Mobile Phone Number': 'மொபைல் எண்',
        'GSTIN (Optional, for B2B tax invoice)': 'ஜிஎஸ்டி எண் (விருப்பத்தேர்வு, B2B பில்களுக்கு)',
        'State Code': 'மாநில குறியீடு',
        'State Name': 'மாநில பெயர்',
        'Save Customer': 'வாடிக்கையாளரை சேமிக்கவும்',
        'Scan barcodes with your scanner, search above, or select products from the left.': 'பார்கோடு ஸ்கேன் செய்யவும், மேலே தேடவும் அல்லது இடதுபுறம் உள்ள பொருட்களைத் தேர்ந்தெடுக்கவும்.',
        'Loading catalog items...': 'பொருட்கள் பட்டியல் ஏற்றப்படுகிறது...',
        'Add to Bill': 'பில்லில் சேர்க்க',
        'Fixed': 'நிலையானது (₹)',
        'Percent': 'சதவீதம் (%)',
        '₹ (Fixed)': '₹ (நிலையானது)',
        '% (Percent)': '% (சதவீதம்)',

        // --- Sales Margin Report & Performance Analytics ---
        'Sales Margin Report': 'விற்பனை லாப விளிம்பு அறிக்கை',
        'Purchase vs. Sales Margin Analysis': 'விற்பனை லாப விளிம்பு அறிக்கை',
        'Low Stock Reorder Items': 'குறைந்த இருப்பு மறுஆர்டர் பொருட்கள்',
        'Low Quantity Stock/Reorder items': 'குறைந்த இருப்பு / மறுஆர்டர் பொருட்கள் பட்டியல்',
        'Top to Low Qty Sold Items': 'அதிகம் முதல் குறைவாக விற்ற பொருட்கள்',
        'Top to low qty sold items': 'அதிகம் முதல் குறைவாக விற்ற பொருட்கள்',
        'Product Catalog & Performance Reports': 'பொருட்கள் பட்டியல் & செயல்திறன் அறிக்கைகள்',
        'Real purchase-to-sales profit margin audit, low stock reorder alerts, and sales volume rankings.': 'கொள்முதல்-விற்பனை லாப விளிம்பு தணிக்கை, குறைந்த இருப்பு மறுஆர்டர் எச்சரிக்கைகள் மற்றும் விற்பனை அளவு தரவரிசை.',
        'Print Report': 'அறிக்கை அச்சிடுக',
        'Daily Sales Margin Timeline': 'தினசரி விற்பனை லாப விளிம்பு காலவரிசை',
        'Daily moving weighted-average profit breakdown': 'தினசரி நகரும் சராசரி கொள்முதல் செலவு மற்றும் லாப பகுப்பாய்வு',
        'Sold Qty': 'விற்ற அளவு',
        'COGS': 'விற்ற பொருட்களின் அடக்கம் (COGS)',
        'Gross P/L': 'மொத்த லாபம் / நஷ்டம்',
        'Margin %': 'லாப விளிம்பு %',
        'Average Cost': 'சராசரி அடக்க விலை',
        'Total Gross Profit': 'மொத்த லாபம்',
        'Total gross profit': 'மொத்த லாபம்',
        'Overall Gross Margin': 'ஒட்டுமொத்த லாப விளிம்பு',
        'Overall gross margin': 'ஒட்டுமொத்த லாப விளிம்பு',
        'Category-Wise Sales Margin Breakdown': 'வகை வாரியான விற்பனை லாப விளிம்பு விவரம்',
        'Toggle Category Breakdown': 'வகை வாரியான அட்டவணையை காட்ட/மறைக்க',
        'Moving Weighted Average Cost': 'நகரும் சராசரி கொள்முதல் அடக்க விலை',
        'Costing Method': 'சரக்கு மதிப்பீட்டு முறை',
        'Costing Method:': 'சரக்கு மதிப்பீட்டு முறை:',
        'Moving Average': 'நகரும் சராசரி (Moving Average)',
        'FIFO': 'முதலில் வந்தது முதலில் (FIFO)',
        'LIFO': 'கடைசியில் வந்தது முதலில் (LIFO)',
        'FIFO (First In, First Out)': 'முதலில் வந்தது முதலில் வெளியேறும் (FIFO)',
        'LIFO (Last In, First Out)': 'கடைசியில் வந்தது முதலில் வெளியேறும் (LIFO)',
        'First In, First Out': 'முதலில் வந்தது முதலில் வெளியேறும்',
        'Last In, First Out': 'கடைசியில் வந்தது முதலில் வெளியேறும்',
        'View Formulas': 'சூத்திரங்களை பார்க்க',
        'Inventory Valuation & Costing Formulas': 'சரக்கு மதிப்பீடு & செலவு கணக்கீட்டு சூத்திரங்கள்',
        'Cost of Goods Sold (Moving Avg)': 'விற்ற பொருட்களின் அடக்கம் (நகரும் சராசரி)',
        'Cost of Goods Sold (FIFO)': 'விற்ற பொருட்களின் அடக்கம் (FIFO)',
        'Cost of Goods Sold (LIFO)': 'விற்ற பொருட்களின் அடக்கம் (LIFO)',
        'Active Dates': 'செயல்பட்ட நாட்கள்',
        'Reorder Alert (< 5 Qty)': 'மறுஆர்டர் எச்சரிக்கை (< 5 இருப்பு)',
        'Alert': 'எச்சரிக்கை',
        'Landed Cost': 'கொள்முதல் அடக்க விலை',
        'Selling Price': 'விற்பனை விலை',
        'Total Sold Qty': 'மொத்த விற்பனை அளவு',
        'Stock in Hand': 'தற்போதைய இருப்பு',

        // --- Sales & Invoices ---
        'Sales & GST Invoices': 'விற்பனை & ஜிஎஸ்டி பில்கள்',
        'View sales history, print tax invoices and issue authorized credit notes / returns.': 'விற்பனை வரலாறு, ஜிஎஸ்டி வரி பில்கள் மற்றும் பொருட்கள் திருப்பிப் பெறுதல்.',
        'New POS Sale': 'புதிய பிஓஎஸ் பில்',
        'All Invoices': 'அனைத்து பில்கள்',
        'Total Invoiced:': 'மொத்த பில் தொகை:',
        'Total Invoiced': 'மொத்த பில் தொகை',
        'All Payment Statuses': 'அனைத்து பணம் செலுத்தும் நிலைகள்',
        'Invoice Number': 'பில் எண்',
        'Invoice #': 'பில் எண் #',
        'Date & Time': 'தேதி & நேரம்',
        'Customer': 'வாடிக்கையாளர்',
        'Type': 'வகை',
        'Final Total': 'இறுதி மொத்தம்',
        'Status': 'நிலை',
        'Actions': 'செயல்கள்',
        'Action': 'செயல்',
        'RETURNED': 'திரும்பப் பெறப்பட்டது',
        'Returned': 'திரும்பப் பெறப்பட்டது',
        'View Tax Invoice': 'வரி பில் காண்க',
        'Issue Return / Credit Note': 'பொருளைத் திருப்பிப் பெறுக / கிரெடிட் நோட்',
        'Thermal Print': 'தெர்மல் பிரிண்ட்',
        'Print': 'அச்சிடுக',

        // --- Inventory, Balances, Transfers & Valuation ---
        'Branch Inventory Balances': 'கிளை இருப்பு விவரம்',
        'Real-time on-hand stock and weighted average valuation scoped by branch.': 'நிகழ்நேர இருப்பு அளவு மற்றும் சராசரி கொள்முதல் விலை மதிப்பீடு.',
        'Total On-Hand Quantity': 'மொத்த இருப்பு அளவு',
        'Total Inventory Value at Cost': 'மொத்த இருப்பு மதிப்பு (அடக்க விலையில்)',
        'Across all catalog products in active branch': 'செயல்படும் கிளையின் அனைத்து பொருட்களின் கூட்டுத்தொகை',
        'Valued at weighted average purchase cost': 'சராசரி கொள்முதல் அடக்க விலையில் மதிப்பிடப்பட்டது',
        'Show Low-Stock Only': 'குறைந்த இருப்பு உள்ளவற்றை மட்டும் காட்டுக',
        'Product Details': 'பொருள் விவரங்கள்',
        'SKU / Barcode': 'SKU / பார்கோடு',
        'Category': 'வகை',
        'On-Hand Stock': 'தற்போதைய இருப்பு',
        'Avg Unit Cost': 'சராசரி அலகு விலை',
        'Total Valuation': 'மொத்த மதிப்பு',
        'Stock Status': 'இருப்பு நிலை',

        // --- Purchases & POs ---
        'Record Goods Receipt': 'பொருட்கள் ரசீது பதிவு',
        'Supplier': 'சப்ளையர்',
        'PO Number': 'கொள்முதல் எண்',
        'PO #': 'கொள்முதல் எண் #',
        'Ordered': 'ஆர்டர் செய்யப்பட்டது',
        'ORDERED': 'ஆர்டர் செய்யப்பட்டது',
        'Received': 'பெறப்பட்டது',
        'RECEIVED': 'பெறப்பட்டது',

        // --- Customers & Accounts Receivable ---
        'Customers & Credit Balances': 'வாடிக்கையாளர்கள் & கடன் நிலுவை',
        'Manage retail shoppers, B2B wholesale merchants, credit limits and outstanding receivables.': 'வாடிக்கையாளர்கள், B2B மொத்த வியாபாரிகள், கடன் வரம்பு மற்றும் வசூலிக்க வேண்டிய நிலுவை.',
        'Register Customer': 'வாடிக்கையாளரை பதிவு செய்க',
        'Total Customer Outstanding Dues (Accounts Receivable)': 'வாடிக்கையாளர்கள் செலுத்த வேண்டிய மொத்த நிலுவை',
        'Total unpaid credit sales across all customers': 'அனைத்து வாடிக்கையாளர்களின் பெறவேண்டிய மொத்த கடன் விற்பனை',
        'Customer Name': 'வாடிக்கையாளர் பெயர்',
        'Contact Phone': 'தொடர்பு எண்',
        'GSTIN (Tax ID)': 'ஜிஎஸ்டி எண்',
        'State': 'மாநிலம்',
        'Credit Limit (₹)': 'கடன் வரம்பு (₹)',
        'Outstanding Dues (₹)': 'வசூலிக்க வேண்டிய நிலுவை (₹)',

        // --- Suppliers & Payables ---
        'Suppliers & Payables Ledger': 'சப்ளையர்கள் & தரவேண்டிய நிலுவை',
        'Track procurement vendors, GST billing details, and accounts payable settlements.': 'சப்ளையர்கள், ஜிஎஸ்டி கொள்முதல் விவரங்கள் மற்றும் கொடுக்க வேண்டிய தொகைகள்.',
        'Register Supplier': 'சப்ளையரை பதிவு செய்க',
        'Total Supplier Payables (Accounts Payable)': 'சப்ளையர்களுக்கு செலுத்த வேண்டிய மொத்த நிலுவை',
        'Amount currently owed to suppliers against received purchase orders': 'கொள்முதல் ஆர்டர்களுக்கு சப்ளையர்களுக்கு தர வேண்டிய தொகை',
        'Supplier Name': 'சப்ளையர் பெயர்',
        'Company Name': 'நிறுவனத்தின் பெயர்',
        'Payables Due (₹)': 'தரவேண்டிய நிலுவை (₹)',
        'Statement': 'கணக்கு விவரம்',
        'Unregistered': 'பதிவு செய்யப்படாதவர்',

        // --- Expenses & Cash Shifts ---
        'Record shop rent, electricity, salaries, transport, and other branch overhead costs.': 'கடை வாடகை, மின் கட்டணம், சம்பளம், போக்குவரத்து மற்றும் பிற கிளை செலவுகள்.',
        'Add Category': 'செலவு வகை சேர்க்க',
        'Record Expense': 'செலவை பதிவு செய்க',
        'Total Operating Expenses (Active Scope)': 'மொத்த இயக்கச் செலவுகள் (செயலில் உள்ள கிளை)',
        'Direct branch operating overheads': 'நேரடி கிளை இயக்கச் செலவுகள்',
        'Total Filtered:': 'வடிகட்டப்பட்ட மொத்தத் தொகை:',
        'Total Filtered': 'வடிகட்டப்பட்ட மொத்தத் தொகை',
        'Expense #': 'செலவு எண் #',
        'Expense Category': 'செலவு வகை',
        'Payment Mode': 'பணம் செலுத்தும் முறை',
        'Bank Transfer': 'வங்கி பரிமாற்றம்',
        'Cash Shift & Drawer Management': 'பணப் பதிவு ஷிப்ட் & காசாளர் மேலாண்மை',
        'Control cashier cash drawer floats, cash sales tally, expenses and end-of-shift reconciliation.': 'காசாளர் தொடக்க ரொக்கம், விற்பனை ரொக்கம், செலவுகள் மற்றும் ஷிப்ட் கணக்கு சரிபார்த்தல்.',
        'Open New Cash Shift': 'புதிய பணப் பதிவு ஷிப்ட் திறக்கவும்',
        'ACTIVE SHIFT OPEN': 'பணப் பதிவு ஷிப்ட் இயங்குகிறது',
        'Cashier:': 'காசாளர்:',
        'Cashier': 'காசாளர்',
        'Reconcile & Close Shift': 'சரிபார்த்து ஷிப்ட் முடிக்கவும்',
        'Opening Float': 'தொடக்க ரொக்கம்',
        'Cash Sales (+)': 'ரொக்க விற்பனை (+)',
        'Collections (+)': 'வசூல் ரொக்கம் (+)',
        'Expenses (-)': 'செலவுகள் (-)',
        'Expected Cash in Drawer (System Calculated)': 'டிராயரில் இருக்க வேண்டிய ரொக்கம் (கணினி கணக்கீடு)',
        'Open Shift Now': 'ஷிப்ட் திறக்கவும்',
        'Daily Cash Shift Register': 'தினசரி பணப் பதிவு',

        // --- Reports (P&L, GST, Daily) ---
        'Profit and Loss (P&L) Statement': 'லாப நஷ்ட அறிக்கை (P&L)',
        'Income, Cost of Goods Sold (COGS at weighted average cost), operating overheads and estimated net profit.': 'வருமானம், விற்ற பொருட்களின் கொள்முதல் அடக்கம், இயக்கச் செலவுகள் மற்றும் நிகர லாபம்.',
        'Print P&L Report': 'லாப நஷ்ட அறிக்கையை அச்சிடுக',
        'Compute P&L': 'லாப நஷ்டத்தை கணக்கிடுக',
        'Current Month': 'நடப்பு மாதம்',
        'PROFIT AND LOSS ESTIMATE STATEMENT': 'மதிப்பிடப்பட்ட லாப நஷ்ட அறிக்கை',
        'Particulars / Accounting Head': 'விவரங்கள் / கணக்கு தலைப்பு',
        'Amount (INR ₹)': 'தொகை (ரூபாய் ₹)',

        // --- Settings, Audit & Master Setup ---
        'Business Settings & Multi-Branch Configuration': 'வணிக அமைப்புகள் & பல கிளை கட்டமைப்பு',
        'Manage business GSTIN, branch network (shops & warehouses), and tax configurations.': 'ஜிஎஸ்டி எண், கிளை நெட்வொர்க் (கடைகள் & கிடங்குகள்) மற்றும் வரி அமைப்புகள்.',
        'Legal Entity & Business Profile': 'பதிவு செய்யப்பட்ட நிறுவனம் & வணிக விவரம்',
        'Trade / Brand Name *': 'வணிக / பிராண்ட் பெயர் *',
        'Legal Registered Entity Name': 'சட்டப்பூர்வ பதிவு செய்யப்பட்ட நிறுவனப் பெயர்',
        'GSTIN *': 'ஜிஎஸ்டி எண் *',
        'PAN Number': 'பான் எண்',
        'State Code *': 'மாநில குறியீடு *',
        'State Name *': 'மாநில பெயர் *',
        'Headquarters Address': 'தலைமை அலுவலக முகவரி',
        'System Security & Audit Trail': 'கணினி பாதுகாப்பு & தணிக்கைப் பதிவு',
        'Immutable audit log recording user logins, checkout transactions, inventory movements and master edits.': 'பயனர் உள்நுழைவுகள், பில்லிங், இருப்பு மாற்றங்கள் மற்றும் தணிக்கைப் பதிவு.',
        'Timestamp': 'நேரம் & தேதி',
        'User': 'பயனர்',
        'Event Name': 'நிகழ்வு பெயர்',
        'Model / Resource': 'ஆதாரம்',
        'IP Address': 'ஐபி முகவரி',
        'Payload Details': 'விவரங்கள்',

        // --- Authentication (Login & Register) ---
        'Sign In': 'உள்நுழைக',
        'Sign in': 'உள்நுழைக',
        'Sign in to your account': 'உங்கள் கணக்கில் உள்நுழைக',
        'Email Address': 'மின்னஞ்சல் முகவரி',
        'Password': 'கடவுச்சொல்',
        'Remember Me': 'என்னை நினைவில் கொள்',
        'Forgot Password?': 'கடவுச்சொல் மறந்துவிட்டதா?',
        'Register': 'பதிவு செய்க',
        'Register a new membership': 'புதிய கணக்கை பதிவு செய்க',
        'Create Business Account': 'வணிகக் கணக்கை உருவாக்குக',
        'Full Name': 'முழு பெயர்',
        'Confirm Password': 'கடவுச்சொல்லை உறுதிப்படுத்துக',
        'I agree to the terms': 'விதிமுறைகளை ஒப்புக்கொள்கிறேன்',
        'Already have an account?': 'ஏற்கனவே கணக்கு உள்ளதா?',
        'Default Admin Credentials': 'இயல்புநிலை நிர்வாகி விவரங்கள்',
        'Click credentials to autofill': 'தானாக நிரப்ப கிளிக் செய்யவும்',
        'Please correct the errors below:': 'கீழே உள்ள பிழைகளை சரிசெய்யவும்:',

        // --- Common Actions, Badges & Table Headers ---
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
        'Active': 'செயலில் உள்ளது',
        'Inactive': 'செயலற்றது',
        'Date': 'தேதி',
        'Total': 'மொத்தம்',
        'Amount': 'தொகை',
        'Subtotal': 'கூட்டல் தொகை',
        'Tax': 'வரி',
        'GST': 'ஜிஎஸ்டி',
        'THERE IS NO RECORD IN THIS DATE': 'இந்த தேதியில் எந்த பதிவும் இல்லை',
        'No audit logs recorded yet.': 'இதுவரை எந்த தணிக்கைப் பதிவும் இல்லை.',
        'No records found': 'எந்த பதிவும் காணப்படவில்லை',
        'All': 'அனைத்தும்',
        'All Suppliers': 'அனைத்து சப்ளையர்கள்',
        'All Expenses': 'அனைத்து செலவுகள்',
        'Units': 'அளவுகள்'
    };

    // =========================================================================
    // 2. INPUT PLACEHOLDERS DICTIONARY
    // =========================================================================
    var DICT_PLACEHOLDERS = {
        'Search product name...': 'பொருளின் பெயரைத் தேடுங்கள்...',
        'Scan Barcode / SKU and hit Enter...': 'பார்கோடு / SKU ஸ்கேன் செய்யவும்...',
        'Type to search and add a product...': 'பொருளைத் தேடி பில்லில் சேர்க்கவும்...',
        'Search customer by name, phone, or GSTIN...': 'வாடிக்கையாளர் பெயர், எண், GSTIN தேடவும்...',
        'From Date': 'தொடக்கத் தேதி',
        'To Date': 'முடிவுத் தேதி',
        'Search Invoice #, Customer, Phone...': 'பில் எண், வாடிக்கையாளர் தேடவும்...',
        'Search PO #, Invoice #...': 'ஆர்டர் எண், பில் எண் தேடவும்...',
        'Search Expense #, Ref...': 'செலவு எண், குறிப்பு தேடவும்...',
        'Search by Product Name, SKU or Barcode...': 'பொருளின் பெயர், SKU அல்லது பார்கோடு மூலம் தேடவும்...',
        'Search by Customer Name, Phone, or GSTIN...': 'வாடிக்கையாளர் பெயர், எண் அல்லது GSTIN மூலம் தேடவும்...',
        'Full Name or Store Name': 'முழு பெயர் அல்லது கடையின் பெயர்',
        '10-digit mobile number': '10 இலக்க மொபைல் எண்',
        '15-character GSTIN': '15 இலக்க ஜிஎஸ்டி எண்',
        'Optional sale notes...': 'விற்பனை குறிப்புகள்...',
        'E.g. 123456789012': 'எ.கா. 123456789012',
        'Email Address': 'மின்னஞ்சல் முகவரி',
        'Password': 'கடவுச்சொல்',
        'Full Name': 'முழு பெயர்'
    };

    // =========================================================================
    // 3. PERSISTENT STATE MANAGEMENT (localStorage & 1-Year Cookie)
    // =========================================================================
    function getSavedLanguage() {
        try {
            var local = localStorage.getItem(STORAGE_KEY) || localStorage.getItem(LEGACY_KEY);
            if (local === 'ta' || local === 'en') return local;

            var match = document.cookie.match(/(?:^|;)\s*app_language=([^;]+)/) ||
                        document.cookie.match(/(?:^|;)\s*billingpoc_lang=([^;]+)/) ||
                        document.cookie.match(/(?:^|;)\s*googtrans=([^;]+)/);
            if (match && match[1]) {
                if (match[1].indexOf('ta') !== -1) return 'ta';
                if (match[1].indexOf('en') !== -1) return 'en';
            }
        } catch (e) {
            console.warn('[Translate] Storage read error:', e);
        }
        return 'en';
    }

    function persistLanguage(lang) {
        try {
            localStorage.setItem(STORAGE_KEY, lang);
            localStorage.setItem(LEGACY_KEY, lang);
        } catch (e) {}

        var host = window.location.hostname;
        var expires = "; max-age=31536000; path=/; SameSite=Lax";
        document.cookie = COOKIE_NAME + "=" + lang + expires;
        document.cookie = LEGACY_KEY + "=" + lang + expires;

        // Keep Google Translate cookie in sync if Google is used
        if (lang === 'ta') {
            document.cookie = "googtrans=/en/ta" + expires;
            document.cookie = "googtrans=/en/ta; path=/; domain=" + host + "; SameSite=Lax; max-age=31536000";
        } else {
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + host + ";";
        }
    }

    // =========================================================================
    // 4. INSTANT REVERSIBLE DOM TRANSLATION WALKER (0ms in-memory execution)
    // =========================================================================
    function translateTextNode(node, lang) {
        if (!node || node.nodeType !== Node.TEXT_NODE) return;

        // Cache original English text on first encounter for instant zero-loss revert
        if (node.__enText === undefined) {
            node.__enText = node.nodeValue;
        }

        if (lang === 'en') {
            if (node.nodeValue !== node.__enText) {
                node.nodeValue = node.__enText;
            }
            return;
        }

        // Translate to Tamil ('ta')
        var raw = node.__enText;
        var trimmed = raw.trim();
        if (!trimmed) return;

        // Direct exact match
        if (DICT_TA[trimmed]) {
            var leading = raw.match(/^\s*/)[0];
            var trailing = raw.match(/\s*$/)[0];
            node.nodeValue = leading + DICT_TA[trimmed] + trailing;
            return;
        }

        // Exact match with colon suffix (e.g. "Branch:" -> "கிளை:")
        if (trimmed.endsWith(':') && DICT_TA[trimmed.slice(0, -1)]) {
            var leading = raw.match(/^\s*/)[0];
            var trailing = raw.match(/\s*$/)[0];
            node.nodeValue = leading + DICT_TA[trimmed.slice(0, -1)] + ':' + trailing;
            return;
        }

        // Exact match with bullet prefix (e.g. "• All Consolidated Branches")
        if (trimmed.startsWith('• ') && DICT_TA[trimmed.slice(2)]) {
            var leading = raw.match(/^\s*/)[0];
            var trailing = raw.match(/\s*$/)[0];
            node.nodeValue = leading + '• ' + DICT_TA[trimmed.slice(2)] + trailing;
            return;
        }
    }

    function translateElementAttributes(el, lang) {
        if (!el || el.nodeType !== Node.ELEMENT_NODE) return;

        var tag = el.tagName ? el.tagName.toLowerCase() : '';
        if (tag === 'script' || tag === 'style' || tag === 'code' || tag === 'pre' || tag === 'kbd') {
            return;
        }

        // Placeholders
        if (el.hasAttribute('placeholder')) {
            if (el.__enPlaceholder === undefined) {
                el.__enPlaceholder = el.getAttribute('placeholder');
            }
            if (lang === 'en') {
                el.setAttribute('placeholder', el.__enPlaceholder);
            } else {
                var ph = el.__enPlaceholder.trim();
                if (DICT_PLACEHOLDERS[ph]) {
                    el.setAttribute('placeholder', DICT_PLACEHOLDERS[ph]);
                } else if (DICT_TA[ph]) {
                    el.setAttribute('placeholder', DICT_TA[ph]);
                }
            }
        }

        // Titles & Tooltips
        if (el.hasAttribute('title')) {
            if (el.__enTitle === undefined) {
                el.__enTitle = el.getAttribute('title');
            }
            if (lang === 'en') {
                el.setAttribute('title', el.__enTitle);
            } else {
                var title = el.__enTitle.trim();
                if (DICT_TA[title]) {
                    el.setAttribute('title', DICT_TA[title]);
                }
            }
        }

        // Aria-labels
        if (el.hasAttribute('aria-label')) {
            if (el.__enAriaLabel === undefined) {
                el.__enAriaLabel = el.getAttribute('aria-label');
            }
            if (lang === 'en') {
                el.setAttribute('aria-label', el.__enAriaLabel);
            } else {
                var al = el.__enAriaLabel.trim();
                if (DICT_TA[al]) {
                    el.setAttribute('aria-label', DICT_TA[al]);
                }
            }
        }

        // Inputs (type=submit / type=button)
        if ((tag === 'input' && (el.type === 'submit' || el.type === 'button')) && el.value) {
            if (el.__enValue === undefined) {
                el.__enValue = el.value;
            }
            if (lang === 'en') {
                el.value = el.__enValue;
            } else {
                var val = el.__enValue.trim();
                if (DICT_TA[val]) {
                    el.value = DICT_TA[val];
                }
            }
        }
    }

    function translateSubtree(root, lang) {
        if (!root) return;

        if (root.nodeType === Node.ELEMENT_NODE) {
            var tag = root.tagName ? root.tagName.toLowerCase() : '';
            if (tag === 'script' || tag === 'style' || tag === 'code' || tag === 'pre' || tag === 'kbd') {
                return;
            }
            if (root.classList && (root.classList.contains('notranslate') || root.classList.contains('skiptranslate'))) {
                return;
            }
            translateElementAttributes(root, lang);
        } else if (root.nodeType === Node.TEXT_NODE) {
            translateTextNode(root, lang);
            return;
        }

        var child = root.firstChild;
        while (child) {
            translateSubtree(child, lang);
            child = child.nextSibling;
        }
    }

    // =========================================================================
    // 5. UI BUTTON STATE SYNCHRONIZATION
    // =========================================================================
    function updateUiButtons(lang) {
        var isEn = (lang === 'en');
        var isTa = (lang === 'ta');

        document.querySelectorAll('.lang-btn-en').forEach(function(el) {
            if (isEn) el.classList.add('active');
            else el.classList.remove('active');
        });

        document.querySelectorAll('.lang-btn-ta').forEach(function(el) {
            if (isTa) el.classList.add('active');
            else el.classList.remove('active');
        });

        var mobileLabel = document.getElementById('mobileLangLabel');
        if (mobileLabel) {
            mobileLabel.innerText = isTa ? 'English' : 'தமிழ்';
        }
    }

    // =========================================================================
    // 6. NON-BLOCKING GOOGLE TRANSLATE BACKGROUND HELPER
    // =========================================================================
    function syncGoogleTranslate(lang) {
        var targetValue = (lang === 'ta') ? 'ta' : '';
        var combo = document.querySelector('.goog-te-combo');
        if (combo) {
            if (combo.value !== targetValue) {
                combo.value = targetValue;
                combo.dispatchEvent(new Event('change', { bubbles: true }));
                if (typeof combo.onchange === 'function') {
                    combo.onchange();
                }
            }
        }
    }

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
            } catch (e) {}
        }

        var saved = getSavedLanguage();
        if (saved === 'ta') {
            syncGoogleTranslate('ta');
        }
    };

    // =========================================================================
    // 7. PUBLIC LANGUAGE SWITCHING API (One-click instant switch)
    // =========================================================================
    window.switchLanguage = function(lang) {
        if (lang !== 'ta' && lang !== 'en') lang = 'en';

        // 1. Persist state globally immediately
        persistLanguage(lang);

        // 2. Set root HTML attributes immediately
        if (lang === 'ta') {
            document.documentElement.classList.add('lang-tamil', 'translating-tamil', 'lang-ready', 'translation-ready');
            document.documentElement.setAttribute('lang', 'ta');
        } else {
            document.documentElement.classList.remove('lang-tamil', 'translating-tamil');
            document.documentElement.setAttribute('lang', 'en');
            document.documentElement.classList.add('lang-ready', 'translation-ready');
        }

        // 3. Update all switch buttons in UI immediately
        updateUiButtons(lang);

        // 4. Translate all DOM nodes in-memory (0ms delay!)
        translateSubtree(document.body || document.documentElement, lang);

        // 5. Trigger Google Translate non-blockingly in background
        syncGoogleTranslate(lang);
    };

    window.toggleMobileLanguage = function() {
        var current = getSavedLanguage();
        var next = (current === 'ta') ? 'en' : 'ta';
        window.switchLanguage(next);
    };

    window.AppLanguage = {
        getLanguage: getSavedLanguage,
        translateSubtree: translateSubtree,
        switchLanguage: window.switchLanguage
    };

    // =========================================================================
    // 8. EARLY INITIALIZATION IN HEAD (Synchronous 0ms execution)
    // =========================================================================
    var initialLang = getSavedLanguage();
    if (initialLang === 'ta') {
        document.documentElement.classList.add('lang-tamil', 'translating-tamil');
        document.documentElement.setAttribute('lang', 'ta');
    } else {
        document.documentElement.classList.remove('lang-tamil', 'translating-tamil');
        document.documentElement.setAttribute('lang', 'en');
    }

    // =========================================================================
    // 9. DOM READY BOOTSTRAP (Ensures Zero Flash & Real-Time Dynamic Observer)
    // =========================================================================
    function bootLanguageEngine() {
        var active = getSavedLanguage();
        updateUiButtons(active);

        if (active === 'ta') {
            document.documentElement.classList.add('lang-tamil', 'translating-tamil');
            document.documentElement.setAttribute('lang', 'ta');
            // Synchronously translate entire DOM in memory (<5ms)
            translateSubtree(document.body, 'ta');
        }

        // Instantly mark translation ready: body becomes visible directly in Tamil!
        document.documentElement.classList.add('lang-ready', 'translation-ready');

        // Permanently suppress and eradicate any Google Translate top banner or frame displacement
        var suppressGoogleBanner = function() {
            if (document.body && document.body.style && document.body.style.top && document.body.style.top !== '0px') {
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
        suppressGoogleBanner();
        setInterval(suppressGoogleBanner, 300);

        // Observe dynamically added content (e.g. POS cart items, AJAX search results, dynamic modals)
        if (window.MutationObserver && document.body) {
            var dynamicObserver = new MutationObserver(function(mutations) {
                if (getSavedLanguage() !== 'ta') return;
                for (var i = 0; i < mutations.length; i++) {
                    var m = mutations[i];
                    if (m.type === 'childList') {
                        for (var j = 0; j < m.addedNodes.length; j++) {
                            var n = m.addedNodes[j];
                            if (n.nodeType === Node.ELEMENT_NODE) {
                                if (n.classList && (n.classList.contains('goog-te-spinner-pos') ||
                                    n.classList.contains('skiptranslate') ||
                                    (n.id && n.id.indexOf('goog') !== -1))) {
                                    continue;
                                }
                            }
                            translateSubtree(n, 'ta');
                        }
                    }
                }
            });
            dynamicObserver.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootLanguageEngine);
    } else {
        bootLanguageEngine();
    }

    // Handle BFCache Back / Forward browser navigation
    window.addEventListener('pageshow', function(event) {
        var active = getSavedLanguage();
        updateUiButtons(active);
        if (active === 'ta') {
            document.documentElement.classList.add('lang-tamil', 'translating-tamil', 'lang-ready', 'translation-ready');
            document.documentElement.setAttribute('lang', 'ta');
            translateSubtree(document.body, 'ta');
        } else {
            document.documentElement.classList.remove('lang-tamil', 'translating-tamil');
            document.documentElement.setAttribute('lang', 'en');
            document.documentElement.classList.add('lang-ready', 'translation-ready');
            translateSubtree(document.body, 'en');
        }
    });

})(window, document);
