<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success'=>false,'redirect'=>'/login.php']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$vendorId = (int)($input['vendor_id'] ?? 0);
$userId   = currentUserId();
$db       = db();

if (!$vendorId) { echo json_encode(['success'=>false]); exit; }

// Check if already faved
$stmt = $db->prepare("SELECT id FROM favourites WHERE user_id=? AND vendor_id=?");
$stmt->execute([$userId,$vendorId]);
$exists = $stmt->fetch();

if ($exists) {
    $db->prepare("DELETE FROM favourites WHERE user_id=? AND vendor_id=?")->execute([$userId,$vendorId]);
    echo json_encode(['success'=>true,'favourited'=>false]);
} else {
    $db->prepare("INSERT IGNORE INTO favourites (user_id, vendor_id) VALUES (?,?)")->execute([$userId,$vendorId]);
    echo json_encode(['success'=>true,'favourited'=>true]);
}
