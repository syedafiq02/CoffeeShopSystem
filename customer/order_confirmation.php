<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$userId = current_user_id();
$orderId = (int) ($_GET['order_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT o.id, o.total_amount, o.order_status, o.payment_status, o.order_type, o.delivery_address,
            o.delivery_fee, o.discount_amount, o.created_at, pc.code AS promo_code
     FROM orders o LEFT JOIN promo_codes pc ON pc.id = o.promo_code_id
     WHERE o.id = ? AND o.user_id = ?'
);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Order not found.');
    redirect('customer/dashboard.php');
}

$itemsStmt = $pdo->prepare(
    'SELECT od.quantity, od.price, p.name
     FROM order_details od JOIN products p ON p.id = od.product_id
     WHERE od.order_id = ?'
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Order Confirmation';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="text-center mb-4">
                <h2>Thank you for your order! 🎉</h2>
                <p class="text-muted">Order #<?= (int) $order['id'] ?> placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Order Status</span>
                        <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($order['order_status'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Payment Status</span>
                        <span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst($order['payment_status'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Order Type</span>
                        <span><?= htmlspecialchars(ucfirst($order['order_type'])) ?></span>
                    </div>
                    <?php if ($order['order_type'] === 'delivery'): ?>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Delivery Address</span>
                            <span class="text-end"><?= htmlspecialchars($order['delivery_address']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-sm mt-3">
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
                                    <tr>
                                        <td colspan="2">Discount<?= $order['promo_code'] ? ' (' . htmlspecialchars($order['promo_code']) . ')' : '' ?></td>
                                        <td class="text-end">-<?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['discount_amount'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($order['order_type'] === 'delivery'): ?>
                                    <tr>
                                        <td colspan="2">Delivery Fee</td>
                                        <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['delivery_fee'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Total</th>
                                    <th class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['total_amount'], 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="<?= BASE_URL ?>customer/orders.php" class="btn btn-coffee me-2">View Order History</a>
                <a href="<?= BASE_URL ?>public/menu.php" class="btn btn-outline-secondary">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
