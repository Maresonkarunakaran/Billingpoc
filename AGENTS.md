<laravel-boost-guidelines>
# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.3. Always use the APIs that match the installed major version of each package — do not assume a version.

## Architecture & Code Conventions
- Conventional Laravel MVC application with Blade templates.
- Transaction logic in Service classes (`app/Services/...`).
- Request validation in Form Requests (`app/Http/Requests/...`).
- Authorization in Policies (`app/Policies/...`).
- Reporting logic in dedicated services (`app/Services/ReportService.php`, etc.).
- Eloquent models, foreign keys, unique constraints, indexes, database transactions, and appropriate row locking (`lockForUpdate()`).
- All financial and quantity calculations must be done on the server using `DECIMAL` / `bcmath` accuracy, never trusted from client input.
- Strict inventory checks: reject sales when stock is insufficient. Never oversell.
- Indian GST rules: CGST + SGST (intra-state), IGST (inter-state), HSN codes, Place of Supply, B2B/B2C, tax inclusive/exclusive.
- Timezone: Asia/Kolkata. Currency: Indian Rupee (₹ / INR).
- UI: AdminLTE 3 / modern responsive layout, Chart.js, sweetalert2/toastr, barcode/keyboard support for POS.
- Testing: Automated tests for sales, purchases, insufficient stock, concurrent checkout, GST, returns, expenses, payments, customer/supplier dues, profit calculations, branch permissions.
</laravel-boost-guidelines>
