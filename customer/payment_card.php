<?php
// Mock Card payment flow. 100% simulated — card details are validated for
// format only, held in the POST body for a single request, and NEVER
// stored anywhere (no card number, name, expiry, or CVV is written to the
// database, session, or any log). Same PRG pattern as payment_fpx.php so
// refresh/back-button never re-triggers a status change.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$userId = current_user_id();
$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, total_amount, payment_status FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Order not found.');
    redirect('customer/dashboard.php');
}

$requestedStep = $_GET['step'] ?? '';
if ($order['payment_status'] === 'paid' && !in_array($requestedStep, ['processing', 'result'], true)) {
    redirect('customer/order_confirmation.php?order_id=' . $orderId);
}

$paymentStmt = $pdo->prepare('SELECT id, method, amount FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([$orderId]);
$payment = $paymentStmt->fetch();

if (!$payment || $payment['method'] !== 'card') {
    redirect('customer/payment_gateway.php?order_id=' . $orderId);
}

$step = $_GET['step'] ?? 'details';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('customer/payment_card.php?order_id=' . $orderId);
    }

    $formStep = $_POST['form_step'] ?? '';

    if ($formStep === 'cancel') {
        $pdo->prepare('UPDATE payments SET status = "cancelled" WHERE id = ?')->execute([$payment['id']]);
        $pdo->prepare('UPDATE orders SET payment_status = "cancelled" WHERE id = ?')->execute([$orderId]);
        redirect('customer/payment_card.php?order_id=' . $orderId . '&step=result');
    }

    if ($formStep === 'details') {
        // Format-only validation. These values are never persisted anywhere.
        $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $cardName   = trim($_POST['card_name'] ?? '');
        $expiry     = trim($_POST['card_expiry'] ?? '');
        $cvv        = trim($_POST['card_cvv'] ?? '');

        if (!ctype_digit($cardNumber) || strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            $errors[] = 'Please enter a valid card number.';
        }
        if ($cardName === '') {
            $errors[] = 'Cardholder name is required.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $errors[] = 'Please enter a valid expiry date (MM/YY).';
        }
        if (!preg_match('/^\d{3}$/', $cvv)) {
            $errors[] = 'CVV must be 3 digits.';
        }

        if (empty($errors)) {
            redirect('customer/payment_card.php?order_id=' . $orderId . '&step=otp');
        }
        $step = 'details';
    }

    if ($formStep === 'otp') {
        $otp = trim($_POST['otp'] ?? '');

        if ($otp === '123456') {
            $transactionRef = generate_demo_transaction_ref('CARD');
            $pdo->prepare('UPDATE payments SET status = "success", transaction_ref = ? WHERE id = ?')
                ->execute([$transactionRef, $payment['id']]);
            $pdo->prepare('UPDATE orders SET payment_status = "paid" WHERE id = ?')->execute([$orderId]);
            redirect('customer/payment_card.php?order_id=' . $orderId . '&step=processing&outcome=success');
        } elseif ($otp === '000000') {
            $pdo->prepare('UPDATE payments SET status = "failed" WHERE id = ?')->execute([$payment['id']]);
            $pdo->prepare('UPDATE orders SET payment_status = "failed" WHERE id = ?')->execute([$orderId]);
            redirect('customer/payment_card.php?order_id=' . $orderId . '&step=processing&outcome=fail');
        } else {
            $errors[] = 'Incorrect OTP. Please try again.';
            $step = 'otp';
        }
    }
}

$pageTitle = 'Card Payment';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="gateway-header">
                <h4 class="mb-1"><?= htmlspecialchars(SITE_NAME) ?></h4>
                <p class="text-muted mb-2">Card Payment</p>
                <span class="demo-badge"><i class="bi bi-shield-check"></i> Demo Payment</span>
            </div>

            <div class="demo-notice mb-4">
                <strong>DEMO PAYMENT</strong> — This is a simulated payment environment. No real transaction will occur.
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Merchant</span><span><?= htmlspecialchars(SITE_NAME) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Order ID</span><span>#<?= (int) $orderId ?></span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold mt-2 pt-2 border-top">
                        <span>Amount</span><span><?= CURRENCY_SYMBOL ?> <?= number_format((float) $payment['amount'], 2) ?></span>
                    </div>
                </div>
            </div>

            <?php if ($step === 'otp'): ?>

                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-3"><i class="bi bi-shield-lock me-2"></i>Card Verification</h5>
                        <p class="text-muted small">Your bank requires additional verification.</p>
                        <p class="mb-3">Demo OTP: <strong>123456</strong></p>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="form_step" value="otp">

                            <div class="mb-3 text-start">
                                <label class="form-label">Enter OTP</label>
                                <input type="text" name="otp" class="form-control text-center" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required>
                            </div>

                            <button type="submit" class="btn btn-coffee w-100 mb-2">Verify</button>
                        </form>
                        <?php require __DIR__ . '/../includes/payment_cancel_form.php'; ?>
                    </div>
                </div>

            <?php elseif ($step === 'processing'): ?>

                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border mb-3" style="color: var(--gold);" role="status"></div>
                        <h5>Processing Card Payment...</h5>
                        <p class="text-muted small">Please do not close this window.</p>
                        <a href="<?= BASE_URL ?>customer/payment_card.php?order_id=<?= (int) $orderId ?>&step=result" class="small">Continue</a>
                    </div>
                </div>
                <script>
                    setTimeout(function () {
                        window.location.href = <?= json_encode(BASE_URL . 'customer/payment_card.php?order_id=' . $orderId . '&step=result') ?>;
                    }, 1600);
                </script>

            <?php elseif ($step === 'result'): ?>

                <?php
                $freshStmt = $pdo->prepare(
                    'SELECT o.payment_status, p.status AS latest_payment_status, p.transaction_ref
                     FROM orders o
                     LEFT JOIN payments p ON p.id = (SELECT id FROM payments WHERE order_id = o.id ORDER BY id DESC LIMIT 1)
                     WHERE o.id = ?'
                );
                $freshStmt->execute([$orderId]);
                $fresh = $freshStmt->fetch();

                if (!$fresh || $fresh['payment_status'] === 'unpaid') {
                    redirect('customer/payment_card.php?order_id=' . $orderId);
                }
                ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <?php if ($fresh['payment_status'] === 'paid'): ?>
                            <i class="bi bi-check-circle-fill payment-result-icon text-success"></i>
                            <h4>Payment Successful</h4>
                            <p class="text-muted small mb-4">Demo transaction — no real money was charged.</p>
                            <div class="text-start d-inline-block mb-4">
                                <p class="mb-1">Order ID: <strong>#<?= (int) $orderId ?></strong></p>
                                <p class="mb-1">Amount: <strong><?= CURRENCY_SYMBOL ?> <?= number_format((float) $payment['amount'], 2) ?></strong></p>
                                <p class="mb-1">Payment Method: <strong>Card</strong></p>
                                <p class="mb-0">Transaction Ref: <strong><?= htmlspecialchars($fresh['transaction_ref']) ?></strong></p>
                            </div>
                            <br>
                            <a href="<?= BASE_URL ?>customer/order_confirmation.php?order_id=<?= (int) $orderId ?>" class="btn btn-coffee">Return to Nōva Brew</a>
                        <?php elseif ($fresh['payment_status'] === 'cancelled'): ?>
                            <i class="bi bi-dash-circle-fill payment-result-icon text-secondary"></i>
                            <h4>Payment Cancelled</h4>
                            <div class="text-start d-inline-block mb-4">
                                <p class="mb-1">Order ID: <strong>#<?= (int) $orderId ?></strong></p>
                                <p class="mb-1">Amount: <strong><?= CURRENCY_SYMBOL ?> <?= number_format((float) $payment['amount'], 2) ?></strong></p>
                                <p class="mb-0">Payment Status: <strong>Cancelled</strong></p>
                            </div>
                            <br>
                            <a href="<?= BASE_URL ?>customer/cart.php" class="btn btn-coffee">Return to Checkout</a>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill payment-result-icon text-danger"></i>
                            <h4>Payment Failed</h4>
                            <p class="text-muted small mb-4">Reason: Demo transaction declined (payment authorization failed).</p>
                            <div class="text-start d-inline-block mb-4">
                                <p class="mb-1">Order ID: <strong>#<?= (int) $orderId ?></strong></p>
                                <p class="mb-0">Amount: <strong><?= CURRENCY_SYMBOL ?> <?= number_format((float) $payment['amount'], 2) ?></strong></p>
                            </div>
                            <br>
                            <form method="POST" action="<?= BASE_URL ?>customer/payment_gateway.php">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                                <input type="hidden" name="action" value="retry">
                                <button type="submit" class="btn btn-coffee">Try Again</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-credit-card me-2"></i>Card Payment</h5>
                        <p class="demo-notice mb-3">DEMO ONLY — Do not enter a real card.</p>

                        <form method="POST" action="" id="cardPaymentForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="form_step" value="details">

                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" name="card_number" id="cardNumberInput" class="form-control card-number-input" placeholder="4242 4242 4242 4242" maxlength="19" inputmode="numeric" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cardholder Name</label>
                                <input type="text" name="card_name" class="form-control text-uppercase" placeholder="DEMO USER" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="text" name="card_expiry" id="cardExpiryInput" class="form-control" placeholder="12/30" maxlength="5" inputmode="numeric" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">CVV</label>
                                    <input type="text" name="card_cvv" id="cardCvvInput" class="form-control" placeholder="123" maxlength="3" inputmode="numeric" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-coffee w-100 mt-3 mb-2">Continue</button>
                        </form>
                        <?php require __DIR__ . '/../includes/payment_cancel_form.php'; ?>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/payment_card.js"></script>
