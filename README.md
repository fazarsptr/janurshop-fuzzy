# Janurshop - Inventory Optimization System using Fuzzy Mamdani

A PHP Native web application designed to optimize retail inventory management, prevent stockouts, and streamline replenishment decisions using the **Fuzzy Mamdani** algorithm.

---

## Overview

Managing inventory for fast-moving retail products requires balancing customer demand against supplier lead times, product shelf-life, and storage capacity. Janurshop integrates classic inventory control with intelligent fuzzy logic decision support to compute optimal restocking quantities and prioritize procurement orders across 108 predefined inference rules.

The system is developed using modern PHP Native without heavy framework overhead, adhering to clean architecture, prepared statements for database operations, and responsive Bootstrap 5 styling.

---

## Key Features

- **Fuzzy Decision Support**: Calculates restocking recommendations and prioritizes items (*Prioritas Utama*, *Prioritas Cadangan*, *Aman*).
- **Inventory Management**: Comprehensive catalog management with real-time stock tracking, keyword search, status filtering, and live summary statistics.
- **Stock Movement History**: Complete audit trail tracking inventory changes (*Stock In*, *Stock Out*, *Adjustment*, *Opening Balance*) with row-level locking.
- **Low-Stock Alerts**: Global navbar notification bell and sidebar badges for items reaching or falling below safety stock limits.
- **Procurement & Reports**: Direct reporting tools with PDF export and Microsoft Excel (.xls) downloads.
- **Activity Logging & Access Control**: Role-based access control (Admin and Owner) and full activity audit logging.

---

## Fuzzy Mamdani Decision Engine

The decision engine evaluates five core input variables for each product:

1. **Current Stock** (*Stok Saat Ini*): Low, Medium, High.
2. **Demand** (*Tingkat Permintaan*): Low, Medium, High.
3. **Supplier Lead Time** (*Waktu Tunggu Pengiriman*): Fast, Moderate, Slow.
4. **Item Price** (*Harga Beli*): Cheap, Moderate, Expensive.
5. **Product Expiration** (*Masa Kedaluwarsa*): Near, Moderate, Far.

### Inference & Defuzzification
- **108 Rule Base**: Formulated to map combinations of inventory factors to a crisp procurement recommendation.
- **Defuzzification Method**: Centroid / Center of Gravity (CoG) calculating the final output weight ($Z$).
- **Decision Categories**:
  - **Prioritas Utama (High Priority)**: Critical stock levels with high demand. Immediate reordering recommended.
  - **Prioritas Cadangan (Buffer / Secondary)**: Decreasing stock with moderate demand. Buffer procurement recommended.
  - **Aman (Safe)**: Ample stock or low demand. No procurement required.

---

## Inventory Management

The inventory module (`items.php`) provides robust stock tracking, audit capabilities, and dynamic operational analytics:

### 1. Product Search by Name or Code
- Supports partial keyword matching across product codes (`kode_barang`) and product names (`nama_barang`).
- Powered by MySQLi prepared statements (`LIKE ?`) with wildcard wrapping for safe, performant search queries.
- Preserves the search term in the input field across filter interactions.

### 2. Inventory Stock Status Filtering
Allows administrators to quickly isolate items requiring immediate attention via an integrated status filter:
- **Semua Stok (`all`)**: Displays the complete product inventory (default).
- **Stok Rendah (`low`)**: Isolates products where current stock has reached or fallen below the minimum safety threshold (`stock <= min_stock_limit`).
- **Stok Habis (`empty`)**: Isolates products that have been completely depleted (`stock <= 0`).
- **Combined Filtering**: Operates simultaneously with keyword search so users can filter within specific search results (e.g., searching for a specific product line while filtering for low stock).

### 3. Inventory Summary Statistics
A compact, four-card operational metric section displayed above the product catalog:
- **Total Produk**: Total number of distinct items matching the active filters.
- **Total Unit Stok**: Aggregate count of physical units in stock (`Pcs`) for matching items.
- **Stok Rendah**: Number of matching products at or below their safety stock limit.
- **Stok Habis**: Number of matching products with zero physical stock.
- **Dynamic & Accurate**: Calculated directly from database records via a single, optimized SQL aggregation query. Recalculates dynamically when filters or searches are applied. Displays `0` safely without PHP notices when no records match.

### 4. Improved Empty-State Handling & Filter Reset
- **Clear Empty-State Distinction**:
  - *Empty Catalog*: When the inventory database contains no records, displays `"Belum ada data barang."` with a call-to-action button to add the first product.
  - *Zero Filter Matches*: When active filters or searches yield no matches, displays the friendly message: `"Tidak ada barang yang sesuai dengan pencarian atau filter."`
- **Streamlined Usability**:
  - Hides redundant alert banners when zero results match to prevent duplicate controls.
  - Provides a visible, prominent **"Reset Filter"** button in the empty-state container as well as inline controls to immediately restore the unfiltered catalog view.

### 5. Low-Stock Notifications
- **Navbar Notification Bell**: Fixed top navbar component in `views/header.php` displaying an interactive notification bell with a dynamic red badge count of items where `stock <= min_stock_limit`.
- **Quick-Access Dropdown**: Shows product name, current stock, and minimum limit, with direct navigation to the product detail view.
- **Sidebar Badge**: Low-stock product count badge displayed alongside the "Data Barang" menu item.
- **Visual Table Warnings**: Products with low stock are highlighted with warning rows (`table-warning`) and badges, while out-of-stock products are highlighted with danger rows (`table-danger`).

### 6. Stock Movement History
- **Audit Trail Integrity**: Tracks all stock modifications via `helpers/StockManager.php` with distinct transaction types:
  - `in`: Incoming supplier purchases or restocks.
  - `out`: Outgoing stock or usage.
  - `adjustment`: Inventory corrections or cycle count adjustments.
  - `opening_balance`: Initial inventory balance records.
- **Concurrency & Transaction Safety**: Employs database transactions (`BEGIN`, `COMMIT`, `ROLLBACK`) and pessimistic row-level locking (`SELECT ... FOR UPDATE`) to prevent race conditions during concurrent stock updates.
- **Referential Integrity**: Configured with `ON DELETE RESTRICT` on related foreign keys to ensure historical audit movements cannot be deleted or orphaned.

---

## How to Use Inventory Search, Filters & Statistics

1. **Searching Products**:
   - Locate the search bar at the top-right of the "Data Barang" card.
   - Enter a product code (e.g., `DTF`) or product name.
   - Press **Enter** or click the search icon (<i class="fas fa-search"></i>).
2. **Filtering by Stock Status**:
   - Use the dropdown menu adjacent to the search input.
   - Choose **Semua Stok**, **Stok Rendah**, or **Stok Habis**.
   - The catalog and summary statistics update immediately upon selection.
3. **Reading Summary Statistics**:
   - The four cards at the top of the page reflect the current catalog scope.
   - When searching or filtering, these metrics adjust to reflect the filtered subset, giving immediate insight into unit counts and stockout risks.
4. **Resetting Filters**:
   - Click the **"Reset Filter"** button located in the empty-state area, the search bar's clear button (`x`), or the filter info banner to return to the full catalog.

---

## Technology Stack

- **Backend**: PHP 8.0+ (PHP Native, OOP helpers)
- **Database**: MySQL / MariaDB (InnoDB, MySQLi with Prepared Statements)
- **Frontend**: Bootstrap 5.3, FontAwesome 6, Inter Font Family
- **Visualization**: Chart.js
- **Export Engines**: HTML table-to-Excel export, printable CSS PDF generation

---

## Installation & Setup

### Prerequisites
- PHP 8.0 or newer
- MySQL 5.7+ or MariaDB 10.3+
- Web server environment (e.g., Apache via XAMPP, Laragon, or standalone LAMP/LEMP)

### Steps
1. **Clone the Repository**:
   ```bash
   git clone https://github.com/your-username/janurshop-fuzzy.git
   cd janurshop-fuzzy
   ```

2. **Web Server Deployment**:
   - Place the project directory into your web server document root (e.g., `C:/xampp/htdocs/janurshop-fuzzy` or `/var/www/html/janurshop-fuzzy`).

3. **Database Configuration**:
   - Create a new MySQL database named `janurshop_fuzzy`.
   - Import the database schema from the `database/` directory.
   - Run the stock movement migration if setting up from an existing dataset:
     ```bash
     php migrate_stock_movements.php
     ```
   - Update your connection settings in `config/database.php`:
     ```php
     $host = 'localhost';
     $user = 'your_database_user';
     $pass = 'your_database_password';
     $db   = 'janurshop_fuzzy';
     ```

4. **Run Application**:
   - Start Apache and MySQL services.
   - Open your browser and navigate to:
     ```text
     http://localhost/janurshop-fuzzy/login.php
     ```

---

## License

This project is developed for academic and operational inventory research. All rights reserved.
