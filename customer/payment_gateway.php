<?php
// Entry point / dispatcher for the mock payment gateway. Verifies the order,
// handles "Retry Payment" (creates a fresh payments row, same as before),
// then routes into the method-specific mock flow. Renders no UI of its own.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$userId = current_user_id();
$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, total_amount, payment_status FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Order not found.');
    redirect('customer/dashboard.php');
}

if ($order['payment_status'] === 'paid') {
    redirect('customer/order_confirmation.php?order_id=' . $orderId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('customer/payment_gateway.php?order_id=' . $orderId);
    }

    if (($_POST['action'] ?? '') === 'retry') {
        $lastPayment = $pdo->prepare('SELECT method FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
        $lastPayment->execute([$orderId]);
        $method = $lastPayment->fetchColumn() ?: 'online_banking';

        $pdo->prepare('INSERT INTO payments (order_id, method, amount, status) VALUES (?, ?, ?, "pending")')
            ->execute([$orderId, $method, $order['total_amount']]);
        $pdo->prepare('UPDATE orders SET payment_status = "unpaid" WHERE id = ?')->execute([$orderId]);
    }

    redirect('customer/payment_gateway.php?order_id=' . $orderId);
}

$paymentStmt = $pdo->prepare('SELECT method FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([$orderId]);
$method = $paymentStmt->fetchColumn();

if (!$method) {
    set_flash('danger', 'No payment attempt found for this order.');
    redirect('customer/dashboard.php');
}

if ($method === 'card') {
    redirect('customer/payment_card.php?order_id=' . $orderId);
}

redirect('customer/payment_fpx.php?order_id=' . $orderId);
