<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$userId = current_user_id();

$stmt = $pdo->prepare(
    'SELECT id, total_amount, order_status, payment_status, order_type, delivery_address, delivery_fee, created_at
     FROM orders WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

$itemsStmt = $pdo->prepare(
    'SELECT od.quantity, od.price, p.name
     FROM order_details od
     JOIN products p ON p.id = od.product_id
     WHERE od.order_id = ?'
);

$statusColors = [
    'pending'   => 'secondary',
    'preparing' => 'warning',
    'ready'     => 'info',
    'completed' => 'success',
    'cancelled' => 'danger',
];

$pageTitle = 'Order History';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/customer_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="page-header text-lg-start">
                <span class="eyebrow-text">My Account</span>
                <h1 class="section-heading">Order History</h1>
            </div>

            <?php if (empty($orders)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-receipt empty-state-icon"></i>
                        <p class="text-muted mb-3">You haven't placed any orders yet.</p>
                        <a href="<?= BASE_URL ?>public/menu.php" class="btn btn-coffee">Browse Menu</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $itemsStmt->execute([$order['id']]);
                    $items = $itemsStmt->fetchAll();
                    $collapseId = 'orderItems' . $order['id'];
                    ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <div>
                                    <strong>Order #<?= (int) $order['id'] ?></strong>
                                    <span class="text-muted ms-2"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
                                </div>
                                <div>
                                    <span class="badge bg-<?= $statusColors[$order['order_status']] ?? 'secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                    </span>
                                    <span class="badge bg-info text-dark">
                                        <?= htmlspecialchars(ucfirst($order['payment_status'])) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars(ucfirst($order['order_type'])) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
                                    View Items
                                </button>
                                <strong>Total: <?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['total_amount'], 2) ?></strong>
                            </div>

                            <div class="collapse mt-3" id="<?= $collapseId ?>">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr><th>Product</th><th>Qty</th><th class="text-end">Price</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                                    <td><?= (int) $item['quantity'] ?></td>
                                                    <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $item['price'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if ($order['order_type'] === 'delivery'): ?>
                                                <tr>
                                                    <td colspan="2">Delivery Fee</td>
                                                    <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['delivery_fee'], 2) ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($order['order_type'] === 'delivery' && $order['delivery_address']): ?>
                                    <p class="text-muted small mt-2 mb-0">Delivery to: <?= htmlspecialchars($order['delivery_address']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
