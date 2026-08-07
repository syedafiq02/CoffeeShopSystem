<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$userId = current_user_id();

$stmt = $pdo->prepare('SELECT name, email, phone, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare('SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_spent FROM orders WHERE user_id = ?');
$stmt->execute([$userId]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT id, total_amount, order_status, payment_status, created_at
     FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5'
);
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/customer_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <h2 class="mb-1">Welcome back, <?= htmlspecialchars($user['name']) ?> 👋</h2>
            <p class="text-muted">Member since <?= date('d M Y', strtotime($user['created_at'])) ?></p>

            <div class="row g-3 mt-2 mb-4">
                <div class="col-sm-6">
                    <div class="card shadow-sm">
                        <div class="card-body stat-card">
                            <div class="stat-card-icon"><i class="bi bi-bag-check"></i></div>
                            <div>
                                <p class="text-muted mb-1">Total Orders</p>
                                <h3 class="mb-0"><?= (int) $stats['total_orders'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card shadow-sm">
                        <div class="card-body stat-card">
                            <div class="stat-card-icon"><i class="bi bi-wallet2"></i></div>
                            <div>
                                <p class="text-muted mb-1">Total Spent</p>
                                <h3 class="mb-0"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $stats['total_spent'], 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-clock-history me-2"></i>Recent Orders</strong>
                    <a href="<?= BASE_URL ?>customer/orders.php" class="small">View all</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-receipt empty-state-icon"></i>
                            <p class="text-muted mb-3">You haven't placed any orders yet.</p>
                            <a href="<?= BASE_URL ?>public/menu.php" class="btn btn-coffee btn-sm">Browse the Menu</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr><th>Order #</th><th>Date</th><th>Status</th><th>Payment</th><th class="text-end">Total</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= (int) $order['id'] ?></td>
                                        <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($order['order_status'])) ?></span></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst($order['payment_status'])) ?></span></td>
                                        <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['total_amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
