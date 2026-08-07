<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$validStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
$statusFilter = $_GET['status'] ?? 'all';

$sql = 'SELECT o.id, o.total_amount, o.order_status, o.payment_status, o.order_type, o.created_at,
               u.name AS customer_name, u.email AS customer_email
        FROM orders o JOIN users u ON u.id = o.user_id';
$params = [];

if (in_array($statusFilter, $validStatuses, true)) {
    $sql .= ' WHERE o.order_status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY o.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusColors = [
    'pending' => 'secondary', 'preparing' => 'warning', 'ready' => 'info',
    'completed' => 'success', 'cancelled' => 'danger',
];

$pageTitle = 'Manage Orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <span class="eyebrow-text">Fulfillment</span>
            <h2 class="section-heading mb-4">Orders</h2>

            <div class="mb-3">
                <a href="?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-coffee' : 'btn-outline-secondary' ?>">All</a>
                <?php foreach ($validStatuses as $status): ?>
                    <a href="?status=<?= $status ?>" class="btn btn-sm <?= $statusFilter === $status ? 'btn-coffee' : 'btn-outline-secondary' ?>">
                        <?= ucfirst($status) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <p class="text-muted mb-0">No orders found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr><th>Order #</th><th>Customer</th><th>Date</th><th>Type</th><th>Status</th><th>Payment</th><th class="text-end">Total</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= (int) $order['id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($order['customer_name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                            </td>
                                            <td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($order['order_type'])) ?></td>
                                            <td><span class="badge bg-<?= $statusColors[$order['order_status']] ?? 'secondary' ?>"><?= htmlspecialchars(ucfirst($order['order_status'])) ?></span></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst($order['payment_status'])) ?></span></td>
                                            <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $order['total_amount'], 2) ?></td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>admin/order_details.php?id=<?= (int) $order['id'] ?>" class="btn btn-outline-secondary btn-sm">View</a>
                                            </td>
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
