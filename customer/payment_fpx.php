<?php
// Mock Online Banking / FPX-style payment flow. 100% simulated — no real
// bank, no real FPX, nothing is transmitted anywhere. Every state-changing
// step (login, cancel, OTP outcome) redirects (PRG pattern) before
// rendering, so refreshing or using the back button anywhere in this flow
// never re-triggers a status change — only the final GET step=result reads
// and displays whatever is already authoritatively stored in the database.
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

if (!$payment || $payment['method'] !== 'online_banking') {
    redirect('customer/payment_gateway.php?order_id=' . $orderId);
}

$banks = [
    'maybank'     => 'Maybank',
    'cimb'        => 'CIMB Bank',
    'public_bank' => 'Public Bank',
    'rhb'         => 'RHB Bank',
    'hong_leong'  => 'Hong Leong Bank',
    'bank_islam'  => 'Bank Islam',
    'ambank'      => 'AmBank',
    'bank_rakyat' => 'Bank Rakyat',
];

$step = $_GET['step'] ?? 'bank';
$bank = $_GET['bank'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('customer/payment_fpx.php?order_id=' . $orderId);
    }

    $formStep = $_POST['form_step'] ?? '';

    if ($formStep === 'cancel') {
        $pdo->prepare('UPDATE payments SET status = "cancelled" WHERE id = ?')->execute([$payment['id']]);
        $pdo->prepare('UPDATE orders SET payment_status = "cancelled" WHERE id = ?')->execute([$orderId]);
        redirect('customer/payment_fpx.php?order_id=' . $orderId . '&step=result');
    }

    if ($formStep === 'bank') {
        $selectedBank = $_POST['bank'] ?? '';
        if (!array_key_exists($selectedBank, $banks)) {
            $errors[] = 'Please select a bank.';
        } else {
            redirect('customer/payment_fpx.php?order_id=' . $orderId . '&step=login&bank=' . urlencode($selectedBank));
        }
    }

    if ($formStep === 'login') {
        $bank = $_POST['bank'] ?? '';
        $demoUsername = trim($_POST['demo_username'] ?? '');
        $demoPassword = trim($_POST['demo_password'] ?? '');
        // Demo login never validates against anything real or transmits
        // anywhere — any non-empty input simply proceeds to the OTP step.
        if (!array_key_exists($bank, $banks) || $demoUsername === '' || $demoPassword === '') {
            $errors[] = 'Please enter both demo username and password.';
            $step = 'login';
        } else {
            redirect('customer/payment_fpx.php?order_id=' . $orderId . '&step=otp&bank=' . urlencode($bank));
        }
    }

    if ($formStep === 'otp') {
        $bank = $_POST['bank'] ?? '';
        $otp = trim($_POST['otp'] ?? '');

        if ($otp === '123456') {
            $transactionRef = generate_demo_transaction_ref('FPX');
            $pdo->prepare('UPDATE payments SET status = "success", transaction_ref = ? WHERE id = ?')
                ->execute([$transactionRef, $payment['id']]);
            $pdo->prepare('UPDATE orders SET payment_status = "paid" WHERE id = ?')->execute([$orderId]);
            redirect('customer/payment_fpx.php?order_id=' . $orderId . '&step=processing&outcome=success');
        } elseif ($otp === '000000') {
            $pdo->prepare('UPDATE payments SET status = "failed" WHERE id = ?')->execute([$payment['id']]);
            $pdo->prepare('UPDATE orders SET payment_status = "failed" WHERE id = ?')->execute([$orderId]);
            redirect('customer/payment_fpx.php?order_id=' . $orderId . '&step=processing&outcome=fail');
        } else {
            $errors[] = 'Incorrect OTP. Please try again.';
            $step = 'otp';
        }
    }
}

$pageTitle = 'Online Banking Payment';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="gateway-header">
                <h4 class="mb-1"><?= htmlspecialchars(SITE_NAME) ?></h4>
                <p class="text-muted mb-2">Payment Gateway</p>
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
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Payment Method</span><span>Online Banking</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold mt-2 pt-2 border-top">
                        <span>Amount</span><span><?= CURRENCY_SYMBOL ?> <?= number_format((float) $payment['amount'], 2) ?></span>
                    </div>
                </div>
            </div>

            <?php if ($step === 'login'): ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-bank me-2"></i>Secure Online Banking</h5>
                        <p class="text-muted small mb-3">Bank: <strong><?= htmlspecialchars($banks[$bank] ?? $bank) ?></strong></p>

                        <div class="demo-notice mb-3">DEMO LOGIN — DO NOT ENTER REAL BANKING CREDENTIALS</div>
                        <p class="small text-muted">Use demo credentials: <strong>demo</strong> / <strong>demo123</strong></p>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="form_step" value="login">
                            <input type="hidden" name="bank" value="<?= htmlspecialchars($bank) ?>">

                            <div class="mb-3">
                                <label class="form-label">Demo Username</label>
                                <input type="text" name="demo_username" class="form-control" placeholder="demo" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Demo Password</label>
                                <input type="password" name="demo_password" class="form-control" placeholder="demo123" required>
                            </div>

                            <button type="submit" class="btn btn-coffee w-100 mb-2">Login &amp; Continue</button>
                        </form>
                        <?php require __DIR__ . '/../includes/payment_cancel_form.php'; ?>
                    </div>
                </div>

            <?php elseif ($step === 'otp'): ?>

                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-3"><i class="bi bi-shield-lock me-2"></i>Payment Verification</h5>
                        <p class="text-muted small">A demo OTP has been sent to your registered mobile number.</p>
                        <p class="mb-3">Demo OTP: <strong>123456</strong></p>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="form_step" value="otp">
                            <input type="hidden" name="bank" value="<?= htmlspecialchars($bank) ?>">

                            <div class="mb-3 text-start">
                                <label class="form-label">Enter OTP</label>
                                <input type="text" name="otp" class="form-control text-center" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required>
                            </div>

                            <button type="submit" class="btn btn-coffee w-100 mb-2">Verify Payment</button>
                        </form>
                        <?php require __DIR__ . '/../includes/payment_cancel_form.php'; ?>
                    </div>
                </div>

            <?php elseif ($step === 'processing'): ?>

                <?php $outcome = $_GET['outcome'] ?? 'success'; ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border mb-3" style="color: var(--gold);" role="status"></div>
                        <h5>Processing Payment...</h5>
                        <p class="text-muted small">Please do not close this window.</p>
                        <a href="<?= BASE_URL ?>customer/payment_fpx.php?order_id=<?= (int) $orderId ?>&step=result" class="small">Continue</a>
                    </div>
                </div>
                <script>
                    setTimeout(function () {
                        window.location.href = <?= json_encode(BASE_URL . 'customer/payment_fpx.php?order_id=' . $orderId . '&step=result') ?>;
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
                    redirect('customer/payment_fpx.php?order_id=' . $orderId);
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
                                <p class="mb-1">Payment Method: <strong>Online Banking</strong></p>
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
                        <h5 class="card-title mb-3">Select Your Bank</h5>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="form_step" value="bank">

                            <div class="row g-2 mb-3">
                                <?php foreach ($banks as $key => $label): ?>
                                    <div class="col-sm-6">
                                        <div class="form-check option-check">
                                            <input class="form-check-input" type="radio" name="bank" id="bank_<?= $key ?>" value="<?= $key ?>" <?= $key === 'maybank' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="bank_<?= $key ?>">
                                                <i class="bi bi-bank me-1"></i><?= htmlspecialchars($label) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="submit" class="btn btn-coffee w-100 mb-2">Continue</button>
                        </form>
                        <?php require __DIR__ . '/../includes/payment_cancel_form.php'; ?>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
