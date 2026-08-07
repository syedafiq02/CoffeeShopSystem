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

$userId    = current_user_id();
$productId = (int) ($_POST['product_id'] ?? 0);
$quantity  = max(1, min(20, (int) ($_POST['quantity'] ?? 1)));

$stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? AND status = "active"');
$stmt->execute([$productId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
);
$stmt->execute([$userId, $productId, $quantity]);

echo json_encode([
    'success'    => true,
    'message'    => 'Added to cart.',
    'cart_count' => get_cart_count($pdo, $userId),
]);
