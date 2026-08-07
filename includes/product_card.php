<?php
// Reusable product card. Requires $product (assoc array with id, name,
// description, price, image, category_name) to be set before including.
$imageUrl = $product['image']
    ? UPLOAD_PRODUCTS_URL . htmlspecialchars($product['image'])
    : BASE_URL . 'assets/images/product-placeholder.svg';
?>
<div class="col-sm-6 col-lg-4 product-card" data-category-id="<?= (int) $product['category_id'] ?>">
    <div class="card h-100 shadow-sm">
        <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" style="height:200px;object-fit:cover;">
        <div class="card-body d-flex flex-column">
            <span class="badge bg-coffee-cream text-dark mb-2 align-self-start"><?= htmlspecialchars($product['category_name']) ?></span>
            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="price-tag"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $product['price'], 2) ?></span>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-toggle="modal" data-bs-target="#productDetailsModal"
                        data-name="<?= htmlspecialchars($product['name']) ?>"
                        data-description="<?= htmlspecialchars($product['description']) ?>"
                        data-price="<?= number_format((float) $product['price'], 2) ?>"
                        data-image="<?= $imageUrl ?>"
                        data-category="<?= htmlspecialchars($product['category_name']) ?>">
                    View Details
                </button>
            </div>

            <?php if (is_logged_in() && !is_admin()): ?>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control qty-input" value="1" min="1" max="20" aria-label="Quantity">
                    <button type="button" class="btn btn-coffee add-to-cart-btn" data-product-id="<?= (int) $product['id'] ?>">
                        Add to Cart
                    </button>
                </div>
            <?php elseif (!is_logged_in()): ?>
                <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-coffee btn-sm w-100">Login to Order</a>
            <?php endif; ?>
        </div>
    </div>
</div>
