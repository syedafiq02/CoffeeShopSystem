<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$orderId = (int) ($_GET['id'] ?? 0);

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

$pageTitle = 'Order #' . $orderId . ' Receipt';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 printable-page">
    <div class="no-print d-flex justify-content-between align-items-center mb-4">
        <a href="<?= BASE_URL ?>admin/order_details.php?id=<?= (int) $order['id'] ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Order</a>
        <button type="button" class="btn btn-coffee" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Print Order
        </button>
    </div>

    <div class="text-center mb-4">
        <img src="<?= BASE_URL ?>assets/images/logov3.png" alt="<?= htmlspecialchars(SITE_NAME) ?>" height="64">
        <h2 class="section-heading mt-2 mb-0">Order Receipt</h2>
        <p class="text-muted mb-0">Order #<?= (int) $order['id'] ?></p>
        <p class="text-muted small">
            Generated on <?= date('d M Y, h:i A') ?> by <?= htmlspecialchars($_SESSION['name']) ?>
        </p>
    </div>

    <div class="row g-4 mb-4">
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
                    <p class="mb-1">Status: <?= htmlspecialchars(ucfirst($order['order_status'])) ?></p>
                    <p class="mb-1">Payment: <?= htmlspecialchars(ucfirst($order['payment_status'])) ?></p>
                    <p class="mb-1">Type: <?= htmlspecialchars(ucfirst($order['order_type'])) ?></p>
                    <?php if ($order['order_type'] === 'delivery'): ?>
                        <p class="mb-0">Address: <?= htmlspecialchars($order['delivery_address']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
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

    <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Payment History</strong></div>
        <div class="card-body">
            <?php if (empty($payments)): ?>
                <p class="text-muted mb-0">Cash order — no online payment attempts.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Method</th><th>Amount</th><th>Status</th><th>Transaction Ref</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['method']))) ?></td>
                                    <td><?= CURRENCY_SYMBOL ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($payment['status'])) ?></td>
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

    <p class="text-center text-muted small mt-4">Thank you for choosing <?= htmlspecialchars(SITE_NAME) ?>.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
