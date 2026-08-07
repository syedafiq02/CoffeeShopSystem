<nav class="navbar navbar-expand-lg navbar-light bg-coffee-cream shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>public/index.php">
            <img src="<?= BASE_URL ?>assets/images/logo.png" alt="<?= htmlspecialchars(SITE_NAME) ?>" height="48">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>public/index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>public/about.php">About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>public/menu.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>public/gallery.php">Gallery</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>public/location.php">Location</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>public/contact.php">Contact</a></li>
            </ul>

            <ul class="navbar-nav">
                <?php if (!is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>auth/login.php">Login</a></li>
                    <li class="nav-item">
                        <a class="btn btn-coffee btn-sm ms-2" href="<?= BASE_URL ?>auth/register.php">Register</a>
                    </li>
                <?php elseif (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/index.php">Admin Panel</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <?php $cartCount = get_cart_count($pdo, current_user_id()); ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>customer/dashboard.php">My Dashboard</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>customer/cart.php">
                            Cart
                            <span class="badge bg-coffee-brown ms-1" id="cartCountBadge"><?= $cartCount ?></span>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>auth/logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
