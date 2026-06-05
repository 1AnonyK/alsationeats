# 🍖 Alsation Eats

## Overview

Alsation Eats is a Consumer-to-Consumer (C2C) e-commerce platform for informal
township takeaway vendors near campus. Customers can browse vendors, add items to
cart, and pay via EFT. Vendors manage their own menus and orders. One account can
be both buyer and seller.

A R1 platform fee is charged per order (to the buyer, not the vendor).

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
├── config.php              # DB config, constants
├── index.php               # Homepage — vendor listing
├── vendor.php              # Vendor menu page
├── cart.php                # Shopping cart
├── checkout.php            # EFT payment + proof upload
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
│   └── settings.php        # Shop name, banner, active toggle
│
├── admin/
│   ├── header.php          # Admin HTML header
│   ├── footer.php          # Admin HTML footer
│   ├── index.php           # Admin dashboard
│   ├── orders.php          # All orders management
│   ├── payments.php        # Verify / reject EFT proofs
│   ├── vendors.php         # Manage vendor shops
│   ├── users.php           # Manage user accounts
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
    → Receives EFT bank details
    → Makes EFT payment, uploads proof
    → Admin verifies payment → order marked Confirmed
    → Vendor prepares → marks Ready
    → Customer collects
```

---

## Key Features

- **Dual role**: One account = buyer + seller
- **Vendor dashboard**: Menu CRUD, order management, stats
- **Admin panel**: Full RBAC — users, vendors, orders, payments
- **EFT flow**: Bank details shown, proof upload, admin verification
- **R1 platform fee**: Added to each order total (buyer pays)
- **Favourites**: Heart any vendor, view on Favourites page
- **CSRF protection**: All POST forms protected
- **Mobile responsive**: Bootstrap sidebar, hamburger menu
