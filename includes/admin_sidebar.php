<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="card shadow-sm">
    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['name'] ?? '?', 0, 1))) ?></div>
        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></div>
        <span class="role-badge">Admin</span>
    </div>
    <div class="list-group list-group-flush">
        <a href="<?= BASE_URL ?>admin/index.php"
           class="list-group-item list-group-item-action <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
        <a href="<?= BASE_URL ?>admin/products.php"
           class="list-group-item list-group-item-action <?= in_array($currentPage, ['products.php', 'product_form.php']) ? 'active' : '' ?>">
            <i class="bi bi-box-seam me-2"></i>Products
        </a>
        <a href="<?= BASE_URL ?>admin/categories.php"
           class="list-group-item list-group-item-action <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
            <i class="bi bi-tags me-2"></i>Categories
        </a>
        <a href="<?= BASE_URL ?>admin/gallery.php"
           class="list-group-item list-group-item-action <?= in_array($currentPage, ['gallery.php', 'gallery_form.php']) ? 'active' : '' ?>">
            <i class="bi bi-images me-2"></i>Gallery
        </a>
        <a href="<?= BASE_URL ?>admin/orders.php"
           class="list-group-item list-group-item-action <?= in_array($currentPage, ['orders.php', 'order_details.php']) ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff me-2"></i>Orders
        </a>
        <a href="<?= BASE_URL ?>admin/customers.php"
           class="list-group-item list-group-item-action <?= $currentPage === 'customers.php' ? 'active' : '' ?>">
            <i class="bi bi-people me-2"></i>Customers
        </a>
        <a href="<?= BASE_URL ?>admin/reports.php"
           class="list-group-item list-group-item-action <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart me-2"></i>Reports
        </a>
        <a href="<?= BASE_URL ?>public/index.php" class="list-group-item list-group-item-action">
            <i class="bi bi-shop me-2"></i>Back to Site
        </a>
    </div>
</div>
