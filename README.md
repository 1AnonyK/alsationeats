# Alsation Eats

## Overview

Alsation Eats is a Consumer-to-Consumer (C2C) e-commerce platform for informal
township takeaway vendors near campus. Customers can browse vendors, add items to
cart, and pay via EFT. Vendors can manage their own menus, orders, and input custom banking details to receive direct payouts. One account can be both a buyer and a seller.

A R1 platform fee is charged per order (paid by the buyer, not the vendor).

---

## Tech Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Frontend   | HTML, CSS, Bootstrap                |
| Scripting  | JavaScript                          |
| Backend    | PHP                                 |
| Database   | MySQL                               |
| Fonts      | Google Fonts (Poppins + Nunito)     |

---

## File Structure

```
alsation-eats/
├── config.php              # DB config, constants, fallback platform bank details
├── index.php               # Homepage — vendor listing
├── vendor.php              # Vendor menu page
├── cart.php                # Shopping cart
├── checkout.php            # EFT payment step (Displays vendor bank details or fallback) + proof upload
├── orders.php              # Buyer order history
├── login.php               # Authentication
├── register.php            # New account
├── logout.php              # Logout Page
├── profile.php             # Account management
├── favourites.php          # Saved vendors
├── help.php                # FAQ
│
├── includes/
│   ├── db.php              # PDO singleton
│   ├── auth.php            # Login/logout/helpers
│   ├── header.php          # Shared HTML header
│   └── footer.php          # Shared HTML footer
│
├── assets/
│   ├── css/style.css       # Main stylesheet
│   └── js/main.js          # Cart AJAX, sidebar toggle, favourites
│
├── api/
│   ├── cart.php            # AJAX cart endpoint
│   └── favourites.php      # AJAX favourites toggle
│
├── seller/
│   ├── setup.php           # Open a new shop
│   ├── dashboard.php       # Seller stats overview
│   ├── menu.php            # Add/edit/delete menu items
│   ├── orders.php          # Manage incoming orders
│   └── settings.php        # Shop customization settings (Name, banner, status, and bank details configuration)
│
├── admin/
│   ├── header.php          # Admin HTML header
│   ├── footer.php          # Admin HTML footer
│   ├── index.php           # Admin dashboard
│   ├── orders.php          # All orders management
│   ├── payments.php        # Verify / reject EFT proofs
│   ├── vendors.php         # Manage vendor shops
│   ├── users.php           # Manage user accounts (Full view, update details/passwords/roles, or delete accounts)
│   └── menu.php            # Cross-vendor menu items
│
└── uploads/
├── proof/              # EFT payment screenshots
├── menu/               # Menu item photos
└── vendor/             # Shop banner images

---

```

## Order Flow

```
Customer browses vendors
→ Adds items to cart (single vendor per cart)
→ Places order (cart cleared)
→ Receives vendor-specific EFT bank details (automatically falls back to system platform details if unconfigured)
→ Makes EFT payment, uploads proof
→ Admin verifies payment → order marked Confirmed
→ Vendor prepares → marks Ready
→ Customer collects
```

---

## Key Features

- **Dual role**: One account handles both buyer and seller capabilities simultaneously.
- **Vendor dashboard**: Handle menu adjustments (CRUD), incoming order flows, stats mapping, and vendor banking profile inputs.
- **Admin panel**: Advanced RBAC administration — view, modify, or rewrite user details (roles, passwords, and information), toggle shop access, monitor orders, and verify or reject submitted EFT documents.
- **Dynamic EFT engine**: Informs checkout routines with direct vendor banking credentials, maintaining a systemic platform account safe-fallback if needed.
- **R1 platform fee**: Fixed application cost loaded onto every completed client transaction.
- **Favourites**: Bookmark and save preferred local vendors for fast tracking.
- **CSRF protection**: Cryptographic state checks embedded across all application form actions.
- **Mobile responsive**: Structured using Bootstrap layouts alongside fluid sidebar interactions for smaller viewport displays.
