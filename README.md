# 🍛 Ocarang Carenderia Self-Ordering System
### CCE IT6L – Information Management | Melicor, Joshua O.

---

## ⚡ Quick Setup (XAMPP)

### Step 1 — Copy Files
Copy the entire `ocarang` folder into:
```
C:\xampp\htdocs\ocarang\
```

### Step 2 — Set Up Database
1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **"Import"** tab at the top
3. Click **"Choose File"** and select `ocarang/database.sql`
4. Click **"Go"** — the database, tables, triggers, and views will be created automatically

### Step 3 — Check DB Config (if needed)
Open `includes/db.php` and confirm:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (blank for default XAMPP)
define('DB_NAME', 'ocarang_db');
```

### Step 4 — Open the System
- **Customer Ordering:** http://localhost/ocarang/
- **Admin Login:** http://localhost/ocarang/admin/login.php

---

## 🔐 Default Admin Login
| Field | Value |
|-------|-------|
| Admin ID | `ADM001` |
| Full Name | `Admin` |

| Field | Value |
|-------|-------|
| Admin ID | `STF001` |
| Full Name | `Staff One` |

---

## 📁 File Structure
```
ocarang/
├── index.php                  ← Root redirect
├── database.sql               ← Full DB setup (tables, triggers, views, seed data)
├── css/
│   └── style.css              ← Main stylesheet
├── includes/
│   ├── db.php                 ← Database connection
│   └── auth.php               ← Session & auth helpers
├── customer/
│   ├── index.php              ← Self-ordering menu page
│   ├── status.php             ← Order status tracker
│   └── track.php              ← Track order by ID
└── admin/
    ├── login.php              ← Admin/staff login
    ├── dashboard.php          ← Admin home with stats
    ├── menu.php               ← Menu CRUD management
    ├── orders.php             ← Order management & payment
    ├── revenue.php            ← Daily revenue reports
    └── logout.php             ← Session logout
```

---

## 🗄 Database Objects Created

### Tables
- `admin` — Stores admin/staff accounts
- `menu_item` — Dishes with price, status, admin FK
- `orders` — Customer orders with status
- `order_item` — Line items per order
- `payment` — Payment records

### Triggers
- `trg_autofill_unit_price` — Auto-fills unit_price from menu on ORDER_ITEM insert
- `trg_update_order_status` — Marks order as Completed after payment is inserted
- `trg_check_item_availability` — Blocks insert if menu item is Unavailable

### Views
- `v_customer_menu` — Available menu items shown to customers
- `v_daily_revenue` — Revenue grouped by date
- `v_order_receipt` — Full order receipt with item details

---

## 🔄 Order Flow
1. **Customer** browses menu → adds items → places order
2. **System** creates Order (Pending) + Order Items (trigger fills price)
3. **Admin** sees order on dashboard → clicks "Start Cooking" (→ Cooking)
4. **Admin** clicks "Mark Ready" (→ Ready)
5. **Customer** sees "Ready!" on status page → goes to counter
6. **Admin** collects payment → inserts Payment → trigger marks order Completed
