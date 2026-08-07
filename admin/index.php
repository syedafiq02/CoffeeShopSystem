<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$totalSales = (float) $pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND order_status != 'cancelled'"
)->fetchColumn();

$totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

$popularProducts = $pdo->query(
    "SELECT p.name, SUM(od.quantity) AS total_sold
     FROM order_details od
     JOIN orders o ON o.id = od.order_id
     JOIN products p ON p.id = od.product_id
     WHERE o.order_status != 'cancelled'
     GROUP BY p.id, p.name
     ORDER BY total_sold DESC
     LIMIT 5"
)->fetchAll();

$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$galleryCount = (int) $pdo->query('SELECT COUNT(*) FROM gallery')->fetchColumn();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <span class="eyebrow-text">Admin Panel</span>
            <h1 class="section-heading mb-1">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h1>
            <p class="text-muted">Overview of sales, orders, and customers.</p>

            <div class="row g-3 mt-2">
                <div class="col-sm-4">
                    <div class="card shadow-sm">
                        <div class="card-body stat-card">
                            <div class="stat-card-icon"><i class="bi bi-cash-stack"></i></div>
                            <div>
                                <p class="text-muted mb-1">Total Sales</p>
                                <h3 class="mb-0"><?= CURRENCY_SYMBOL ?> <?= number_format($totalSales, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card shadow-sm">
                        <div class="card-body stat-card">
                            <div class="stat-card-icon"><i class="bi bi-bag-check"></i></div>
                            <div>
                                <p class="text-muted mb-1">Total Orders</p>
                                <h3 class="mb-0"><?= $totalOrders ?></h3>
                                <a href="<?= BASE_URL ?>admin/orders.php" class="small">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card shadow-sm">
                        <div class="card-body stat-card">
                            <div class="stat-card-icon"><i class="bi bi-people"></i></div>
                            <div>
                                <p class="text-muted mb-1">Total Customers</p>
                                <h3 class="mb-0"><?= $totalCustomers ?></h3>
                                <a href="<?= BASE_URL ?>admin/customers.php" class="small">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-star me-2"></i>Popular Products</strong>
                    <a href="<?= BASE_URL ?>admin/reports.php" class="small">Full Reports</a>
                </div>
                <div class="card-body">
                    <?php if (empty($popularProducts)): ?>
                        <p class="text-muted mb-0">No sales data yet.</p>
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

            <div class="row g-3 mt-1">
                <div class="col-sm-4">
                    <div class="card shadow-sm">
                        <div class="card-body stat-card">
                            <div class="stat-card-icon"><i class="bi bi-box-seam"></i></div>
                            <div>
                                <p class="text-muted mb-1">Products</p>
                                <h4 class="mb-0"><?= $productCount ?></h4>
                                <a href="<?= BASE_URL ?>admin/products.php" class="small">Manage</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card shadow-sm">
                        <div class="card-body stat-card">
                            <div class="stat-card-icon"><i class="bi bi-tags"></i></div>
                            <div>
                                <p class="text-muted mb-1">Categories</p>
                                <h4 class="mb-0"><?= $categoryCount ?></h4>
                                <a href="<?= BASE_URL ?>admin/categories.php" class="small">Manage</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card shadow-sm">
                        <div class="card-body stat-card">
                            <div class="stat-card-icon"><i class="bi bi-images"></i></div>
                            <div>
                                <p class="text-muted mb-1">Gallery Items</p>
                                <h4 class="mb-0"><?= $galleryCount ?></h4>
                                <a href="<?= BASE_URL ?>admin/gallery.php" class="small">Manage</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
