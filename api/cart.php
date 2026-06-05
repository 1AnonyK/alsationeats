<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success'=>false,'message'=>'Please log in to use the cart.','redirect'=>'/login.php']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_POST['action'] ?? '';
$userId = currentUserId();
$db     = db();

function cartSummary(int $userId, PDO $db): array {
    $stmt = $db->prepare("
        SELECT ci.quantity, mi.price, mi.vendor_id
        FROM cart_items ci
        JOIN menu_items mi ON mi.id = ci.menu_item_id
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $subtotal = 0; $count = 0;
    foreach ($rows as $r) { $subtotal += $r['price']*$r['quantity']; $count += $r['quantity']; }
    $total = $subtotal + ($count > 0 ? PLATFORM_FEE : 0);
    return ['subtotal'=>$subtotal,'total'=>$total,'cart_count'=>$count];
}

switch ($action) {

    case 'add':
        $itemId   = (int)($input['item_id'] ?? 0);
        $vendorId = (int)($input['vendor_id'] ?? 0);
        if (!$itemId) { echo json_encode(['success'=>false,'message'=>'Invalid item.']); exit; }

        // Check item exists and belongs to vendor
        $chk = $db->prepare("SELECT id, vendor_id FROM menu_items WHERE id = ? AND is_available = 1");
        $chk->execute([$itemId]);
        $item = $chk->fetch();
        if (!$item) { echo json_encode(['success'=>false,'message'=>'Item not available.']); exit; }

        // Enforce single-vendor cart
        $existingVendor = $db->prepare("
            SELECT DISTINCT mi.vendor_id FROM cart_items ci
            JOIN menu_items mi ON mi.id = ci.menu_item_id
            WHERE ci.user_id = ?
        ");
        $existingVendor->execute([$userId]);
        $ev = $existingVendor->fetchColumn();
        if ($ev && $ev != $item['vendor_id']) {
            echo json_encode(['success'=>false,'message'=>'Your cart has items from another vendor. Please clear your cart first.']);
            exit;
        }

        // Upsert
        $db->prepare("
            INSERT INTO cart_items (user_id, menu_item_id, quantity)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE quantity = quantity + 1
        ")->execute([$userId, $itemId]);

        echo json_encode(['success'=>true] + cartSummary($userId, $db));
        break;

    case 'inc':
        $itemId = (int)($input['item_id'] ?? 0);
        $db->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND menu_item_id = ?")->execute([$userId,$itemId]);
        $qty = $db->prepare("SELECT quantity FROM cart_items WHERE user_id=? AND menu_item_id=?");
        $qty->execute([$userId,$itemId]);
        $q = (int)$qty->fetchColumn();
        $price = $db->prepare("SELECT price FROM menu_items WHERE id=?")->execute([$itemId]);
        $price = $db->query("SELECT price FROM menu_items WHERE id=$itemId")->fetchColumn();
        $summary = cartSummary($userId,$db);
        echo json_encode(['success'=>true,'qty'=>$q,'line_total'=>$q*$price] + $summary);
        break;

    case 'dec':
        $itemId = (int)($input['item_id'] ?? 0);
        $qStmt  = $db->prepare("SELECT quantity FROM cart_items WHERE user_id=? AND menu_item_id=?");
        $qStmt->execute([$userId,$itemId]);
        $current = (int)$qStmt->fetchColumn();
        if ($current <= 1) {
            $db->prepare("DELETE FROM cart_items WHERE user_id=? AND menu_item_id=?")->execute([$userId,$itemId]);
            $q = 0;
        } else {
            $db->prepare("UPDATE cart_items SET quantity=quantity-1 WHERE user_id=? AND menu_item_id=?")->execute([$userId,$itemId]);
            $q = $current - 1;
        }
        $price = $db->query("SELECT price FROM menu_items WHERE id=$itemId")->fetchColumn();
        $summary = cartSummary($userId,$db);
        echo json_encode(['success'=>true,'qty'=>$q,'line_total'=>$q*$price] + $summary);
        break;

    case 'remove':
        $itemId = (int)($input['item_id'] ?? 0);
        $db->prepare("DELETE FROM cart_items WHERE user_id=? AND menu_item_id=?")->execute([$userId,$itemId]);
        echo json_encode(['success'=>true,'qty'=>0,'line_total'=>0] + cartSummary($userId,$db));
        break;

    case 'count':
        echo json_encode(['success'=>true,'cart_count'=>cartSummary($userId,$db)['cart_count']]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Unknown action.']);
}
