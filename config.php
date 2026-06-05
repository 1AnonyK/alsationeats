<?php
define('DB_HOST', 'sql212.infinityfree.com');
define('DB_NAME', 'if0_42095682_alsation');
define('DB_USER', 'if0_42095682');
define('DB_PASS', 'Alsation10');
define('DB_CHARSET', 'utf8mb4');

define('PLATFORM_FEE', 1.00);
define('SITE_NAME',  'Alsation Eats');
define('SITE_URL',   'http://localhost');

// EFT Bank details for platform fee
define('BANK_NAME',    'Capitec');
define('BANK_ACCOUNT', '2527394198');
define('BANK_BRANCH',  '470010');
define('BANK_TYPE',    'Savings');
define('BANK_HOLDER',  'Alsation Eats (Pty) Ltd');

// Upload directories
define('UPLOAD_PROOF',  __DIR__ . '/uploads/proof/');
define('UPLOAD_MENU',   __DIR__ . '/uploads/menu/');
define('UPLOAD_VENDOR', __DIR__ . '/uploads/vendor/');
define('MAX_UPLOAD_MB', 5);

// Session
define('SESSION_LIFETIME', 3600 * 8);  // 8 hours

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => SESSION_LIFETIME, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
