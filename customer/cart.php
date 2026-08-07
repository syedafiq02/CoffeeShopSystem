<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$userId = current_user_id();

$stmt = $pdo->prepare(
    'SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image
     FROM cart c
     JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?
     ORDER BY c.id DESC'
);
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['quantity'] * $item['price'];
}

$pageTitle = 'My Cart';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="page-header">
        <span class="eyebrow-text">Your Order</span>
        <h1 class="section-heading">My Cart</h1>
    </div>

    <?php if (empty($cartItems)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-cart-x empty-state-icon"></i>
                <p class="text-muted mb-3">Your cart is empty.</p>
                <a href="<?= BASE_URL ?>public/menu.php" class="btn btn-coffee">Browse Menu</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width:120px;">Quantity</th>
                                <th class="text-end">Line Total</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <?php
                                $imageUrl = $item['image']
                                    ? UPLOAD_PRODUCTS_URL . htmlspecialchars($item['image'])
                                    : BASE_URL . 'assets/images/product-placeholder.svg';
                                $lineTotal = $item['quantity'] * $item['price'];
                                ?>
                                <tr id="cartRow<?= (int) $item['cart_id'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:56px;height:56px;object-fit:cover;" class="rounded">
                                            <div>
                                                <div><?= htmlspecialchars($item['name']) ?></div>
                                                <small class="text-muted"><?= CURRENCY_SYMBOL ?> <?= number_format((float) $item['price'], 2) ?> each</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm cart-qty-input"
                                               data-cart-id="<?= (int) $item['cart_id'] ?>"
                                               value="<?= (int) $item['quantity'] ?>" min="1" max="20">
                                    </td>
                                    <td class="text-end">
                                        <?= CURRENCY_SYMBOL ?> <span id="lineTotal<?= (int) $item['cart_id'] ?>"><?= number_format($lineTotal, 2) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm cart-remove-btn" data-cart-id="<?= (int) $item['cart_id'] ?>">
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <h5 class="mb-0">Total: <?= CURRENCY_SYMBOL ?> <span id="cartGrandTotal"><?= number_format($cartTotal, 2) ?></span></h5>
                    <a href="<?= BASE_URL ?>customer/checkout.php" class="btn btn-coffee">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/cart.js"></script>
