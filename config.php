<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('DB_HOST', 'mysql.railway.internal');
define('DB_NAME', 'railway');
define('DB_USER', 'root'); 
define('DB_PASS', 'zCOmMIVooihyltiIdskLeAdXGKzrykvy'); 
define('DB_PORT', '3306');

define('PLATFORM_FEE', 1.00);
define('SITE_NAME',  'Alsation Eats');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $protocol = "https://";
} else {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://");
}

$host = $_SERVER['HTTP_HOST'];
$subfolder = '/';

define('SITE_URL', $protocol . $host . $subfolder);

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
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME, 
        'httponly' => true, 
        'secure' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
?>
