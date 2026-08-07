<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$userId = current_user_id();
$errors = [];

function get_cart_with_prices(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT c.id AS cart_id, c.product_id, c.quantity, p.name, p.price
         FROM cart c JOIN products p ON p.id = c.product_id
         WHERE c.user_id = ?'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

$cartItems = get_cart_with_prices($pdo, $userId);

if (empty($cartItems)) {
    set_flash('warning', 'Your cart is empty. Add some items before checking out.');
    redirect('customer/cart.php');
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['quantity'] * $item['price'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $orderType = $_POST['order_type'] ?? '';
    $deliveryAddress = sanitize_input($_POST['delivery_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';

    if (!in_array($orderType, ['pickup', 'delivery'], true)) {
        $errors[] = 'Please select a valid order type.';
    }
    if ($orderType === 'delivery' && $deliveryAddress === '') {
        $errors[] = 'Delivery address is required for delivery orders.';
    }
    if (!in_array($paymentMethod, ['cash', 'online_banking', 'card'], true)) {
        $errors[] = 'Please select a valid payment method.';
    }

    // Re-check cart hasn't changed/emptied between page load and submit
    $cartItems = get_cart_with_prices($pdo, $userId);
    if (empty($cartItems)) {
        $errors[] = 'Your cart is empty.';
    }

    if (empty($errors)) {
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['quantity'] * $item['price'];
        }
        $deliveryFee = $orderType === 'delivery' ? DELIVERY_FEE : 0;
        $totalAmount = $subtotal + $deliveryFee;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO orders (user_id, total_amount, order_status, payment_status, order_type, delivery_address, delivery_fee)
                 VALUES (?, ?, "pending", "unpaid", ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $totalAmount,
                $orderType,
                $orderType === 'delivery' ? $deliveryAddress : null,
                $deliveryFee,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $detailStmt = $pdo->prepare(
                'INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
            );
            foreach ($cartItems as $item) {
                $detailStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            }

            $pdo->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);

            if ($paymentMethod !== 'cash') {
                $pdo->prepare(
                    'INSERT INTO payments (order_id, method, amount, status) VALUES (?, ?, ?, "pending")'
                )->execute([$orderId, $paymentMethod, $totalAmount]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while placing your order. Please try again.';
        }

        if (empty($errors)) {
            if ($paymentMethod === 'cash') {
                set_flash('success', 'Order placed! Please pay ' . ($orderType === 'delivery' ? 'on delivery' : 'at pickup') . '.');
                redirect('customer/order_confirmation.php?order_id=' . $orderId);
            } else {
                redirect('customer/payment_gateway.php?order_id=' . $orderId);
            }
        }
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<script>
    const CART_SUBTOTAL = <?= json_encode($subtotal) ?>;
    const DELIVERY_FEE_JS = <?= json_encode((float) DELIVERY_FEE) ?>;
</script>

<div class="container py-5">
    <div class="page-header">
        <span class="eyebrow-text">Almost There</span>
        <h1 class="section-heading">Checkout</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <form method="POST" action="" id="checkoutForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-bag-check me-2"></i>Order Type</h5>
                        <div class="form-check option-check">
                            <input class="form-check-input" type="radio" name="order_type" id="orderTypePickup" value="pickup" checked>
                            <label class="form-check-label" for="orderTypePickup">Pickup at Store</label>
                        </div>
                        <div class="form-check option-check">
                            <input class="form-check-input" type="radio" name="order_type" id="orderTypeDelivery" value="delivery">
                            <label class="form-check-label" for="orderTypeDelivery">Delivery (+<?= CURRENCY_SYMBOL ?> <?= number_format(DELIVERY_FEE, 2) ?>)</label>
                        </div>

                        <div id="deliveryAddressGroup" style="display:none;">
                            <label class="form-label">Delivery Address</label>
                            <textarea name="delivery_address" id="deliveryAddressInput" class="form-control" rows="3" placeholder="Enter your full delivery address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-credit-card me-2"></i>Payment Method</h5>
                        <div class="form-check option-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="paymentCash" value="cash" checked>
                            <label class="form-check-label" for="paymentCash">Cash</label>
                        </div>
                        <div class="form-check option-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="paymentOnlineBanking" value="online_banking">
                            <label class="form-check-label" for="paymentOnlineBanking">Online Banking</label>
                        </div>
                        <div class="form-check option-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="paymentCard" value="card">
                            <label class="form-check-label" for="paymentCard">Card Payment</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-coffee btn-lg w-100">Place Order</button>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><i class="bi bi-receipt me-2"></i><strong>Order Summary</strong></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?> &times; <?= (int) $item['quantity'] ?></td>
                                    <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td>Subtotal</td>
                                <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format($subtotal, 2) ?></td>
                            </tr>
                            <tr id="deliveryFeeRow" style="display:none;">
                                <td>Delivery Fee</td>
                                <td class="text-end"><?= CURRENCY_SYMBOL ?> <?= number_format(DELIVERY_FEE, 2) ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="text-end"><?= CURRENCY_SYMBOL ?> <span id="grandTotalDisplay"><?= number_format($subtotal, 2) ?></span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/checkout.js"></script>
