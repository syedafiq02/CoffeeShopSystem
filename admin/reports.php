<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$periods = ['all' => 'All Time', '7' => 'Last 7 Days', '30' => 'Last 30 Days'];
$period = $_GET['period'] ?? 'all';
if (!array_key_exists($period, $periods)) {
    $period = 'all';
}

$dateCondition = '';
$params = [];
if ($period !== 'all') {
    $dateCondition = ' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)';
    $params[] = (int) $period;
}

$salesStmt = $pdo->prepare(
    "SELECT COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS revenue
     FROM orders
     WHERE payment_status = 'paid' AND order_status != 'cancelled' $dateCondition"
);
$salesStmt->execute($params);
$sales = $salesStmt->fetch();
$averageOrderValue = $sales['order_count'] > 0 ? $sales['revenue'] / $sales['order_count'] : 0;

$statusStmt = $pdo->prepare(
    "SELECT order_status, COUNT(*) AS total FROM orders WHERE 1=1 $dateCondition GROUP BY order_status"
);
$statusStmt->execute($params);
$statusCounts = array_column($statusStmt->fetchAll(), 'total', 'order_status');

$paymentStmt = $pdo->prepare(
    "SELECT payment_status, COUNT(*) AS total FROM orders WHERE 1=1 $dateCondition GROUP BY payment_status"
);
$paymentStmt->execute($params);
$paymentCounts = array_column($paymentStmt->fetchAll(), 'total', 'payment_status');

$typeStmt = $pdo->prepare(
    "SELECT order_type, COUNT(*) AS total FROM orders WHERE 1=1 $dateCondition GROUP BY order_type"
);
$typeStmt->execute($params);
$typeCounts = array_column($typeStmt->fetchAll(), 'total', 'order_type');

$allOrderStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
$allPaymentStatuses = ['unpaid', 'paid', 'failed'];
$allOrderTypes = ['pickup', 'delivery'];

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <span class="eyebrow-text">Insights</span>
            <h2 class="section-heading mb-4">Reports</h2>

            <div class="mb-3">
                <?php foreach ($periods as $key => $label): ?>
                    <a href="?period=<?= $key ?>" class="btn btn-sm <?= $period === $key ? 'btn-coffee' : 'btn-outline-secondary' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><strong><i class="bi bi-printer me-2"></i>Print Report</strong></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Generate a printable report for a specific period.</p>
                    <a href="<?= BASE_URL ?>admin/print_report.php?type=daily" target="_blank" class="btn btn-coffee btn-sm me-2">Daily</a>
                    <a href="<?= BASE_URL ?>admin/print_report.php?type=weekly" target="_blank" class="btn btn-coffee btn-sm me-2">Weekly</a>
                    <a href="<?= BASE_URL ?>admin/print_report.php?type=monthly" target="_blank" class="btn btn-coffee btn-sm">Monthly</a>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><strong>Sales Summary (<?= $periods[$period] ?>)</strong></div>
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

            <div class="row g-4">
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
                                    <?php foreach ($allOrderTypes as $type): ?>
                                        <tr>
                                            <td><?= ucfirst($type) ?></td>
                                            <td class="text-end"><?= (int) ($typeCounts[$type] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
