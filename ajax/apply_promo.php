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
$code = strtoupper(trim($_POST['code'] ?? ''));

if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter a promo code.']);
    exit;
}

$subtotalStmt = $pdo->prepare(
    'SELECT COALESCE(SUM(c.quantity * p.price), 0)
     FROM cart c JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?'
);
$subtotalStmt->execute([$userId]);
$subtotal = (float) $subtotalStmt->fetchColumn();

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

$promoStmt = $pdo->prepare('SELECT * FROM promo_codes WHERE code = ?');
$promoStmt->execute([$code]);
$promo = $promoStmt->fetch();

if (!$promo) {
    echo json_encode(['success' => false, 'message' => 'That promo code does not exist.']);
    exit;
}

if ($promo['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'That promo code is no longer active.']);
    exit;
}

if ($promo['expiry_date'] !== null && $promo['expiry_date'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'That promo code has expired.']);
    exit;
}

if ($promo['usage_limit'] !== null) {
    $usedStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE promo_code_id = ?');
    $usedStmt->execute([$promo['id']]);
    if ((int) $usedStmt->fetchColumn() >= (int) $promo['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'That promo code has reached its usage limit.']);
        exit;
    }
}

$discountAmount = $promo['discount_type'] === 'percentage'
    ? $subtotal * ((float) $promo['discount_value'] / 100)
    : (float) $promo['discount_value'];
$discountAmount = min($discountAmount, $subtotal);

echo json_encode([
    'success'         => true,
    'promo_id'        => (int) $promo['id'],
    'code'            => $promo['code'],
    'discount_amount' => number_format($discountAmount, 2, '.', ''),
    'message'         => 'Promo code applied!',
]);
