SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `phone`      VARCHAR(20) DEFAULT '',
  `role`       ENUM('customer','admin') DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Vendors ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vendors` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `description` TEXT,
  `banner_img`  VARCHAR(255) DEFAULT '',
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Menu Items ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vendor_id`    INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `description`  TEXT,
  `price`        DECIMAL(10,2) NOT NULL,
  `image`        VARCHAR(255) DEFAULT '',
  `category`     VARCHAR(60) DEFAULT 'General',
  `is_available` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Orders ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `buyer_id`     INT UNSIGNED NOT NULL,
  `vendor_id`    INT UNSIGNED NOT NULL,
  `status`       ENUM('pending_payment','payment_uploaded','confirmed','preparing','ready','collected','cancelled') DEFAULT 'pending_payment',
  `subtotal`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `platform_fee` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `total`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes`        TEXT,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`buyer_id`)  REFERENCES `users`(`id`),
  FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Order Items ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`     INT UNSIGNED NOT NULL,
  `menu_item_id` INT UNSIGNED NOT NULL,
  `item_name`    VARCHAR(100) NOT NULL,
  `quantity`     INT UNSIGNED NOT NULL DEFAULT 1,
  `price`        DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`)     REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Payments ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payments` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`    INT UNSIGNED NOT NULL,
  `proof_file`  VARCHAR(255) NOT NULL,
  `reference`   VARCHAR(100) DEFAULT '',
  `status`      ENUM('pending','verified','rejected') DEFAULT 'pending',
  `notes`       TEXT,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `verified_at` TIMESTAMP NULL,
  `verified_by` INT UNSIGNED NULL,
  UNIQUE KEY `order_id` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Cart Items ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `menu_item_id` INT UNSIGNED NOT NULL,
  `quantity`     INT UNSIGNED NOT NULL DEFAULT 1,
  `added_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_cart` (`user_id`, `menu_item_id`),
  FOREIGN KEY (`user_id`)      REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Favourites ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `favourites` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `vendor_id`  INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_fav` (`user_id`, `vendor_id`),
  FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;