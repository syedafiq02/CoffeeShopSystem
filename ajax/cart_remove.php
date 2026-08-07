<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
require_login_json();

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid form submission.']);
    exit;
}

$userId = current_user_id();
$cartId = (int) ($_POST['cart_id'] ?? 0);

$stmt = $pdo->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?');
$stmt->execute([$cartId, $userId]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
    exit;
}

$totalStmt = $pdo->prepare(
    'SELECT COALESCE(SUM(c.quantity * p.price), 0)
     FROM cart c JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?'
);
$totalStmt->execute([$userId]);
$cartTotal = (float) $totalStmt->fetchColumn();

echo json_encode([
    'success'    => true,
    'cart_total' => number_format($cartTotal, 2),
    'cart_count' => get_cart_count($pdo, $userId),
]);
