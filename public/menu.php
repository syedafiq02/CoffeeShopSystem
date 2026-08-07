<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$categories = $pdo->query('SELECT id, category_name FROM categories ORDER BY category_name')->fetchAll();

$products = $pdo->query(
    'SELECT p.id, p.category_id, p.name, p.description, p.price, p.image, c.category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.status = "active"
     ORDER BY c.category_name, p.name'
)->fetchAll();

$pageTitle = 'Menu';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<section class="container py-5">
    <div class="page-header">
        <span class="eyebrow-text">Explore</span>
        <h1 class="section-heading">Our Menu</h1>
        <p class="section-subheading">Coffee, non-coffee, and fresh bakes — something for everyone.</p>
    </div>

    <div class="filter-bar mb-4">
        <button type="button" class="btn btn-sm category-filter-btn active" data-category-id="0">All</button>
        <?php foreach ($categories as $category): ?>
            <button type="button" class="btn btn-sm category-filter-btn" data-category-id="<?= (int) $category['id'] ?>">
                <?= htmlspecialchars($category['category_name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
        <p class="text-center text-muted">No products available yet. Please check back soon.</p>
    <?php else: ?>
        <div class="row g-4 fade-in-section">
            <?php foreach ($products as $product): ?>
                <?php require __DIR__ . '/../includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/product_modal.php'; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/menu.js"></script>
<script src="<?= BASE_URL ?>assets/js/cart.js"></script>
