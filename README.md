# BharatPOS: Multi-Branch Wholesale & Retail POS (Indian GST)

A production-grade, database-backed wholesale and retail Point of Sale (POS) application built with **Laravel 12**, **MySQL / MariaDB**, **AdminLTE 3**, **Chart.js**, and designed specifically for Indian commercial enterprises operating multiple retail shops and storage warehouses.

---

## 🚀 Key Modules & Capabilities

1. **Authentication & Role-Based Permissions (RBAC)**
   - Roles: `Super Administrator`, `Branch Manager`, `POS Cashier`, `Warehouse Head`, `Accountant`.
   - Granular module permissions with route and UI level authorization.

2. **Multi-Branch & Warehouse Network**
   - Distinct entity types: `shop` (Retail / Wholesale counter) and `warehouse` (Storage and transit hub).
   - Strict branch data isolation: transactions and stock balances in one branch never leak or affect another branch.
   - Quick branch switcher in navigation bar with session scoping.

3. **Product Catalog, SKU, HSN & Unit Conversions**
   - Hierarchical product categories.
   - Units of Measurement (PCS, BOX, KG, LTR, PKT) with decimal quantity allowance support.
   - Unit conversions (e.g., 1 Box = 24 PCS) enabling automated conversions at checkout and stock keeping.
   - Barcode and SKU search with rapid scanner integration.

4. **Dual Pricing Architecture (Retail & Wholesale)**
   - Authoritative database pricing: Retail Price, Wholesale Price with minimum wholesale quantity thresholds.
   - Branch-specific price overrides (`product_branch_settings`).
   - Browser input prices are never trusted on checkout.

5. **Strict Inventory Management & Oversell Prevention**
   - Base-unit standardized stock keeping.
   - **Zero overselling rule**: checkout strictly fails if requested quantity exceeds available branch balance.
   - Enforces database transactions and row-level locks (`lockForUpdate()`) to handle concurrent checkouts safely.
   - Full movement ledger (`stock_movements`) tracking all 8 transaction types: `purchase_receive`, `sale`, `sale_return`, `purchase_return`, `transfer_in`, `transfer_out`, `adjustment`, `opening_stock`.
   - Moving Weighted Average Cost (WAC) tracking for compliant asset valuation (Ind AS 2).

6. **Branch-to-Branch Transfers**
   - Transfer stock between warehouse hubs and retail shops.
   - Atomic debit from origin and credit to destination, generating Rule 55 CGST Delivery Challans.

7. **Fast POS Terminal & Checkout**
   - Full-screen POS interface with live product search, category filtering, and barcode auto-addition.
   - Keyboard shortcuts (`Enter` to add from barcode, `F2` to trigger payment modal).
   - Idempotency key per order preventing duplicate submissions and double stock deductions on network retry.
   - Split payments supported: Cash, UPI, Card, Cheque, and Credit Sale.
   - Thermal receipt format (58mm/80mm) with auto-print dialog.

8. **Configurable Indian GST Billing Engine**
   - Intra-state supplies: CGST + SGST (split evenly).
   - Inter-state supplies: IGST (charged in full).
   - Configurable tax treatment: Tax-Exclusive and Tax-Inclusive (MRP).
   - Historical tax snapshots recorded line-by-line on transaction lines.
   - Standard A4 Tax Invoice with HSN summary breakdown table.

9. **Sales Returns & Credit Notes**
   - Authorized partial or full returns against original invoices.
   - Automatically restocks inventory into branch and reverses associated cost.
   - Issues Credit Notes (CN series) adjusting customer outstanding dues.

10. **Procurement, Supplier Invoices & Goods Receipts (GRN)**
    - Purchase orders (PO) and supplier bills with line-by-line GST.
    - Automatic goods receipt updating weighted average cost and inventory balances.
    - Supplier dues and accounts payable tracking.

11. **Contacts & Credit Accounts (AR / AP)**
    - Customers: B2B GST registered or B2C walk-in, credit limits, dues statement, and collection vouchers.
    - Suppliers: Company details, GSTIN, payables statement, and settlement vouchers.

12. **Operating Expense Tracking**
    - Direct operating expenses (Rent, Electricity, Staff Wages, Transport, Packaging).
    - Automatic deduction from cashier cash drawer for cash expenses.

13. **Cash Drawer & Shift Management**
    - Daily shift opening with float amount.
    - Real-time tally: `Opening Cash + Cash Sales + Cash Collections - Cash Expenses - Cash Refunds`.
    - End-of-shift physical cash count and automatic discrepancy calculation.

14. **Executive Dashboard & Real-Time Analytics**
    - 12 real KPI cards:
      - Today's Sales, Gross Profit, Operating Expenses, Estimated Net Profit
      - Monthly Sales, Gross Profit, Expenses, Estimated Net Profit
      - Total Inventory Valuation at Cost
      - Low-Stock Alert Count
      - Total Customer Outstanding (AR)
      - Total Supplier Payables (AP)
    - Interactive Chart.js charts: Daily Sales/Profit trend, Operating expenses breakdown, Retail vs Wholesale distribution, Top products.
    - Date range filters: Today, Yesterday, 7 Days, 30 Days, This Month, Last Month, Custom Date Range.

15. **Statutory & Accounting Reports**
    - **Profit & Loss (P&L) Statement**: Net Sales - COGS = Gross Profit - Operating Expenses = Estimated Net Profit.
    - **Daily Sales Report**: Daily register reconciliation and invoice list.
    - **GSTR-1 Tax Summary**: Table 4 (B2B), Table 7 (B2C), Table 12 (HSN summary).
    - **Inventory Valuation Report**: Item-by-item asset valuation at weighted average cost.

16. **Security & Audit Logs**
    - Immutable audit trail (`audit_logs`) recording event type, user, IP address, and JSON before/after state diffs.

---

## 🛠️ Technology Stack

- **Backend**: Laravel 12 (PHP 8.3+)
- **Database**: MariaDB 13.0 / MySQL 8.0+
- **Frontend**: Blade, HTML5, Bootstrap 4, AdminLTE 3.2, Chart.js 3.9, SweetAlert2
- **Number & Currency Handling**: `DECIMAL(15, 4)` and `bcmath` accuracy, formatted in Indian Rupee (`₹` / `INR`)
- **Timezone**: `Asia/Kolkata`

---

## 🔑 Demo Access Credentials

The database is pre-seeded with realistic Indian distributor data:

| Role | Email | Password | Assigned Branches |
|------|-------|----------|-------------------|
| **Super Administrator** | `admin@pos.com` | `password` | All Branches (Full Access) |
| **Branch Manager** | `manager@pos.com` | `password` | T. Nagar Mart, Chennai WH |
| **POS Cashier** | `cashier@pos.com` | `password` | T. Nagar Super Retail Mart |
| **Warehouse Head** | `inventory@pos.com` | `password` | Chennai WH, Coimbatore WH |

---

## 📋 Installation & Running Locally

1. **Verify PHP 8.2+ and Composer**:
   ```bash
   php -v
   composer -V
   ```

2. **Configure Database (`.env`)**:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pos_db
   DB_USERNAME=root
   DB_PASSWORD=
   APP_TIMEZONE=Asia/Kolkata
   ```

3. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Run Test Suite**:
   ```bash
   php artisan test
   ```

5. **Start Application Server**:
   ```bash
   php artisan serve
   ```
   Open `http://localhost:8000` in your browser.

---

## 🧪 Automated Test Suite

Run all automated unit and feature tests:
```bash
php artisan test
```

Tests cover:
- Successful POS checkout, stock deduction, COGS calculation, and GST breakdown.
- Strict rejection when requested quantity exceeds available stock.
- Idempotency key handling preventing duplicate billing on double submissions.
- Strict multi-branch isolation: sales in one branch never deduct inventory from another branch.
- Indian GST logic: intra-state (CGST + SGST) vs inter-state (IGST).
- Branch-to-branch stock transfer execution.
- Financial report calculations (Net sales, COGS, Gross profit, and Estimated Net profit).
- Shift opening, drawer reconciliation and discrepancy calculation.
- Customer due payment collections and supplier settlements.
- Operating expense logging and cash drawer reduction.
