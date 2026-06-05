<?php
require_once __DIR__ . '/db.php';

// ── Checks ──────────────────────────────────────────────────
function isLoggedIn(): bool  { return isset($_SESSION['user_id']); }
function isAdmin(): bool     { return ($_SESSION['role'] ?? '') === 'admin'; }
function isSeller(): bool    { return !empty($_SESSION['vendor_id']); }

function requireLogin(string $redirect = '/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . $redirect);
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) { http_response_code(403); die('Access denied.'); }
}

// ── Current user helpers ─────────────────────────────────────
function currentUserId(): int    { return (int)($_SESSION['user_id'] ?? 0); }
function currentUserName(): string { return $_SESSION['name'] ?? 'Guest'; }
function currentVendorId(): ?int { return $_SESSION['vendor_id'] ?? null; }

// ── Cart helper (count) ──────────────────────────────────────
function cartCount(): int {
    if (!isLoggedIn()) return 0;
    $stmt = db()->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?");
    $stmt->execute([currentUserId()]);
    return (int)$stmt->fetchColumn();
}

// ── Login / logout ───────────────────────────────────────────
function loginUser(string $email, string $password): bool {
    $stmt = db()->prepare("SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) return false;

    // Check if user has a vendor
    $vStmt = db()->prepare("SELECT id FROM vendors WHERE user_id = ? AND is_active = 1 LIMIT 1");
    $vStmt->execute([$user['id']]);
    $vendor = $vStmt->fetch();

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['name']      = $user['name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['vendor_id'] = $vendor ? $vendor['id'] : null;
    return true;
}

function logoutUser(): void {
    session_unset();
    session_destroy();
}

// ── Registration ─────────────────────────────────────────────
function registerUser(string $name, string $email, string $password, string $phone = ''): int|false {
    try {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = db()->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $phone]);
        return (int)db()->lastInsertId();
    } catch (PDOException $e) {
        return false; // email duplicate
    }
}

// ── Sanitise ─────────────────────────────────────────────────
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403); die('CSRF check failed.');
    }
}

// ── File upload helper (InfinityFree compatible) ─────────────
// Uses file_get_contents/file_put_contents as fallback because
// InfinityFree disables chmod and move_uploaded_file can fail.
function handleUpload(string $fieldName, string $dir, array $allowed = ['jpg','jpeg','png','gif','webp','pdf']): string|false {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) return false;
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($_FILES[$fieldName]['size'] > MAX_UPLOAD_MB * 1024 * 1024) return false;

    // Create upload directory if it doesn't exist
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = uniqid('', true) . '.' . $ext;
    $dest     = $dir . $filename;

    // Try move_uploaded_file first (standard), fall back to file_get_contents
    // The fallback is needed on InfinityFree where move_uploaded_file can fail
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $dest)) {
        return $filename;
    }
    $data = file_get_contents($_FILES[$fieldName]['tmp_name']);
    if ($data !== false && file_put_contents($dest, $data) !== false) {
        return $filename;
    }
    return false;
}
