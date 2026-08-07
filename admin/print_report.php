<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$type = $_GET['type'] ?? 'daily';
if (!in_array($type, ['daily', 'weekly', 'monthly'], true)) {
    $type = 'daily';
}

switch ($type) {
    case 'weekly':
        $dateCondition = 'YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)';
        $reportTitle = 'Weekly Sales Report';
        $weekStart = new DateTime('monday this week');
        $weekEnd = new DateTime('sunday this week');
        $rangeLabel = $weekStart->format('d M Y') . ' – ' . $weekEnd->format('d M Y');
        break;
    case 'monthly':
        $dateCondition = 'YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())';
        $reportTitle = 'Monthly Sales Report';
        $rangeLabel = date('F Y');
        break;
    default:
        $type = 'daily';
        $dateCondition = 'DATE(o.created_at) = CURDATE()';
        $reportTitle = 'Daily Sales Report';
        $rangeLabel = date('d M Y');
        break;
}

$sales = $pdo->query(
    "SELECT COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS revenue
     FROM orders o
     WHERE o.payment_status = 'paid' AND o.order_status != 'cancelled' AND $dateCondition"
)->fetch();
$averageOrderValue = $sales['order_count'] > 0 ? $sales['revenue'] / $sales['order_count'] : 0;

$statusCounts = array_column($pdo->query(
    "SELECT o.order_status, COUNT(*) AS total FROM orders o WHERE $dateCondition GROUP BY o.order_status"
)->fetchAll(), 'total', 'order_status');

$paymentCounts = array_column($pdo->query(
    "SELECT o.payment_status, COUNT(*) AS total FROM orders o WHERE $dateCondition GROUP BY o.payment_status"
)->fetchAll(), 'total', 'payment_status');

$typeCounts = array_column($pdo->query(
    "SELECT o.order_type, COUNT(*) AS total FROM orders o WHERE $dateCondition GROUP BY o.order_type"
)->fetchAll(), 'total', 'order_type');

$popularProducts = $pdo->query(
    "SELECT p.name, SUM(od.quantity) AS total_sold
     FROM order_details od
     JOIN orders o ON o.id = od.order_id
     JOIN products p ON p.id = od.product_id
     WHERE o.order_status != 'cancelled' AND $dateCondition
     GROUP BY p.id, p.name
     ORDER BY total_sold DESC
     LIMIT 5"
)->fetchAll();

$orders = $pdo->query(
    "SELECT o.id, o.total_amount, o.order_status, o.payment_status, o.order_type, o.created_at, u.name AS customer_name
     FROM orders o JOIN users u ON u.id = o.user_id
     WHERE $dateCondition
     ORDER BY o.created_at DESC"
)->fetchAll();

$allOrderStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
$allPaymentStatuses = ['unpaid', 'paid', 'failed'];
$allOrderTypes = ['pickup', 'delivery'];

$pageTitle = $reportTitle;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 printable-page">
    <div class="no-print d-flex justify-content-between align-items-center mb-4">
        <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Reports</a>
        <button type="button" class="btn btn-coffee" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Print Report
        </button>
    </div>

    <div class="text-center mb-4">
        <img src="<?= BASE_URL ?>assets/images/logov3.png" alt="<?= htmlspecialchars(SITE_NAME) ?>" height="64">
        <h2 class="section-heading mt-2 mb-0"><?= htmlspecialchars($reportTitle) ?></h2>
        <p class="text-muted mb-0"><?= htmlspecialchars($rangeLabel) ?></p>
        <p class="text-muted small">
            Generated on <?= date('d M Y, h:i A') ?> by <?= htmlspecialchars($_SESSION['name']) ?>
        </p>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Sales Summary</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-4">
                    <p class="text-muted mb-1">Revenue (Paid)</p>
                    <h4><?= CURRENCY_SYMBOL ?> <?= number_format((float) $sales['revenue'], 2) ?></h4>
                </div>
                <div class="col-sm-4">
                    <p class="text-muted mb-1">Paid Orders</p>
                    <h4><?= (int) $sales['order_count'] ?></h4>
                </div>
                <div class="col-sm-4">
                    <p class="text-muted mb-1">Average Order Value</p>
                    <h4><?= CURRENCY_SYMBOL ?> <?= number_format($averageOrderValue, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><strong>By Order Status</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($allOrderStatuses as $status): ?>
                                <tr>
                                    <td><?= ucfirst($status) ?></td>
                                    <td class="text-end"><?= (int) ($statusCounts[$status] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><strong>By Payment Status</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($allPaymentStatuses as $status): ?>
                                <tr>
                                    <td><?= ucfirst($status) ?></td>
                                    <td class="text-end"><?= (int) ($paymentCounts[$status] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><strong>By Order Type</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($allOrderTypes as $orderType): ?>
                                <tr>
                                    <td><?= ucfirst($orderType) ?></td>
                                    <td class="text-end"><?= (int) ($typeCounts[$orderType] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Popular Products</strong></div>
        <div class="card-body">
            <?php if (empty($popularProducts)): ?>
                <p class="text-muted mb-0">No sales data for this period.</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Product</th><th class="text-end">Units Sold</th></tr></thead>
                    <tbody>
                        <?php foreach ($popularProducts as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td class="text-end"><?= (int) $product['total_sold'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>All Orders (<?= count($orders) ?>)</strong></div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <p class="text-muted mb-0">No orders in this period.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Order #</th><th>Customer</th><th>Date</th><th>Status</th><th>Payment</th><th>Type</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= (int) $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($order['order_status'])) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($order['payment_status'])) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($order['order_type'])) ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
