<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$orderId = (int) ($_GET['id'] ?? $_POST['order_id'] ?? 0);
$validStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('admin/order_details.php?id=' . $orderId);
    }

    $action = $_POST['action'] ?? 'update_status';

    if ($action === 'mark_paid') {
        $pdo->prepare('UPDATE orders SET payment_status = "paid" WHERE id = ?')->execute([$orderId]);
        set_flash('success', 'Order marked as paid (cash received).');
    } else {
        $newStatus = $_POST['order_status'] ?? '';
        if (!in_array($newStatus, $validStatuses, true)) {
            set_flash('danger', 'Invalid status.');
        } else {
            $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?')->execute([$newStatus, $orderId]);
            set_flash('success', 'Order status updated.');
        }
    }

    redirect('admin/order_details.php?id=' . $orderId);
}

$stmt = $pdo->prepare(
    'SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, pc.code AS promo_code
     FROM orders o
     JOIN users u ON u.id = o.user_id
     LEFT JOIN promo_codes pc ON pc.id = o.promo_code_id
     WHERE o.id = ?'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Order not found.');
    redirect('admin/orders.php');
}

$itemsStmt = $pdo->prepare(
    'SELECT od.quantity, od.price, p.name
     FROM order_details od JOIN products p ON p.id = od.product_id
     WHERE od.order_id = ?'
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$paymentsStmt = $pdo->prepare('SELECT method, amount, status, transaction_ref, created_at FROM payments WHERE order_id = ? ORDER BY id');
$paymentsStmt->execute([$orderId]);
$payments = $paymentsStmt->fetchAll();

$statusColors = [
    'pending' => 'secondary', 'preparing' => 'warning', 'ready' => 'info',
    'completed' => 'success', 'cancelled' => 'danger',
];
$paymentColors = ['pending' => 'secondary', 'success' => 'success', 'failed' => 'danger'];

$pageTitle = 'Order #' . $orderId;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="eyebrow-text">Fulfillment</span>
                    <h2 class="section-heading mb-0">Order #<?= (int) $order['id'] ?></h2>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>admin/print_order.php?id=<?= (int) $order['id'] ?>" target="_blank" class="btn btn-coffee btn-sm">
                        <i class="bi bi-printer me-2"></i>Print Order
                    </a>
                    <a href="<?= BASE_URL ?>admin/orders.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Orders</a>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Customer</h5>
                            <p class="mb-1"><?= htmlspecialchars($order['customer_name']) ?></p>
                            <p class="mb-1 text-muted"><?= htmlspecialchars($order['customer_email']) ?></p>
                            <p class="mb-0 text-muted"><?= htmlspecialchars($order['customer_phone']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Order Info</h5>
                            <p class="mb-1">Placed: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
                            <p class="mb-1">Type: <?= htmlspecialchars(ucfirst($order['order_type'])) ?></p>
                            <?php if ($order['order_type'] === 'delivery'): ?>
                                <p class="mb-0">Address: <?= htmlspecialchars($order['delivery_address']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white"><strong>Items</strong></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Product</th><th>Qty</th><th class="text-end">Price</th></tr></thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= (int) $item['quantity'] ?></td>
                                        <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $item['price'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ((float) $order['discount_amount'] > 0): ?>
                                    <tr><td colspan="2">Discount<?= $order['promo_code'] ? ' (' . htmlspecialchars($order['promo_code']) . ')' : '' ?></td><td class="text-end">-<?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['discount_amount'], 2) ?></td></tr>
                                <?php endif; ?>
                                <?php if ($order['order_type'] === 'delivery'): ?>
                                    <tr><td colspan="2">Delivery Fee</td><td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['delivery_fee'], 2) ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr><th colspan="2">Total</th><th class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['total_amount'], 2) ?></th></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white"><strong>Payment History</strong></div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <p class="text-muted">Cash order — no online payment attempts.</p>
                        <?php if ($order['payment_status'] !== 'paid'): ?>
                            <form method="POST" action="" onsubmit="return confirm('Confirm cash payment received for this order?');">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <input type="hidden" name="action" value="mark_paid">
                                <button type="submit" class="btn btn-coffee btn-sm">Mark as Paid (Cash Received)</button>
                            </form>
                        <?php else: ?>
                            <span class="badge bg-success">Paid (Cash)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Method</th><th>Amount</th><th>Status</th><th>Transaction Ref</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['method']))) ?></td>
                                            <td><?= CURRENCY_SYMBOL ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                                            <td><span class="badge bg-<?= $paymentColors[$payment['status']] ?? 'secondary' ?>"><?= htmlspecialchars(ucfirst($payment['status'])) ?></span></td>
                                            <td><?= htmlspecialchars($payment['transaction_ref'] ?? '—') ?></td>
                                            <td><?= date('d M Y, h:i A', strtotime($payment['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white"><strong>Update Status</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        Current: <span class="badge bg-<?= $statusColors[$order['order_status']] ?? 'secondary' ?>"><?= htmlspecialchars(ucfirst($order['order_status'])) ?></span>
                    </div>
                    <form method="POST" action="" class="d-flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <select name="order_status" class="form-select" style="max-width:220px;">
                            <?php foreach ($validStatuses as $status): ?>
                                <option value="<?= $status ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-coffee">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
