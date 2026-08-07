<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="card shadow-sm">
    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['name'] ?? '?', 0, 1))) ?></div>
        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></div>
    </div>
    <div class="list-group list-group-flush">
        <a href="<?= BASE_URL ?>customer/dashboard.php"
           class="list-group-item list-group-item-action <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
        <a href="<?= BASE_URL ?>customer/profile.php"
           class="list-group-item list-group-item-action <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person me-2"></i>My Profile
        </a>
        <a href="<?= BASE_URL ?>customer/orders.php"
           class="list-group-item list-group-item-action <?= $currentPage === 'orders.php' ? 'active' : '' ?>">
            <i class="bi bi-receipt me-2"></i>Order History
        </a>
        <a href="<?= BASE_URL ?>public/index.php" class="list-group-item list-group-item-action">
            <i class="bi bi-shop me-2"></i>Back to Shop
        </a>
    </div>
</div>
