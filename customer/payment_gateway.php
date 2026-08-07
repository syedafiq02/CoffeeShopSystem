<?php
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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'retry') {
            $lastPayment = $pdo->prepare('SELECT method FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
            $lastPayment->execute([$orderId]);
            $method = $lastPayment->fetchColumn() ?: 'online_banking';

            $pdo->prepare('INSERT INTO payments (order_id, method, amount, status) VALUES (?, ?, ?, "pending")')
                ->execute([$orderId, $method, $order['total_amount']]);
            $pdo->prepare('UPDATE orders SET payment_status = "unpaid" WHERE id = ?')->execute([$orderId]);

            redirect('customer/payment_gateway.php?order_id=' . $orderId);
        }

        if ($action === 'simulate') {
            $outcome = $_POST['outcome'] ?? '';
            $pendingPayment = $pdo->prepare(
                'SELECT id FROM payments WHERE order_id = ? AND status = "pending" ORDER BY id DESC LIMIT 1'
            );
            $pendingPayment->execute([$orderId]);
            $paymentId = $pendingPayment->fetchColumn();

            if ($paymentId && in_array($outcome, ['success', 'fail'], true)) {
                $newStatus = $outcome === 'success' ? 'success' : 'failed';
                $transactionRef = 'TXN' . strtoupper(bin2hex(random_bytes(6)));

                $pdo->prepare('UPDATE payments SET status = ?, transaction_ref = ? WHERE id = ?')
                    ->execute([$newStatus, $transactionRef, $paymentId]);

                if ($newStatus === 'success') {
                    $pdo->prepare('UPDATE orders SET payment_status = "paid" WHERE id = ?')->execute([$orderId]);
                    set_flash('success', 'Payment successful!');
                    redirect('customer/order_confirmation.php?order_id=' . $orderId);
                } else {
                    $pdo->prepare('UPDATE orders SET payment_status = "failed" WHERE id = ?')->execute([$orderId]);
                    redirect('customer/payment_gateway.php?order_id=' . $orderId);
                }
            }
        }
    }
}

$paymentStmt = $pdo->prepare('SELECT id, method, amount, status FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([$orderId]);
$latestPayment = $paymentStmt->fetch();

$methodLabels = ['cash' => 'Cash', 'online_banking' => 'Online Banking', 'card' => 'Card Payment'];

$pageTitle = 'Payment';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body text-center p-4">
                    <h4 class="mb-3">Order #<?= (int) $orderId ?> Payment</h4>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
                    <?php endif; ?>

                    <?php if (!$latestPayment): ?>
                        <p class="text-muted">No payment attempt found for this order.</p>
                    <?php elseif ($latestPayment['status'] === 'failed'): ?>
                        <div class="alert alert-danger">Your last payment attempt failed.</div>
                        <p class="text-muted">Amount Due: <strong><?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['total_amount'], 2) ?></strong></p>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="action" value="retry">
                            <button type="submit" class="btn btn-coffee">Retry Payment</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted mb-1">Paying via <strong><?= htmlspecialchars($methodLabels[$latestPayment['method']] ?? $latestPayment['method']) ?></strong></p>
                        <h3 class="mb-4"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $latestPayment['amount'], 2) ?></h3>

                        <p class="text-muted small">This is a simulated gateway for demo purposes — choose an outcome below.</p>

                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="action" value="simulate">
                            <input type="hidden" name="outcome" value="success">
                            <button type="submit" class="btn btn-coffee me-2">Simulate Successful Payment</button>
                        </form>
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="action" value="simulate">
                            <input type="hidden" name="outcome" value="fail">
                            <button type="submit" class="btn btn-outline-danger">Simulate Failed Payment</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
