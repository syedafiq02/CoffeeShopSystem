<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$promoId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['promo_id']) ? (int) $_POST['promo_id'] : 0);
$isEdit = $promoId > 0;

$promo = [
    'code' => '', 'discount_type' => 'percentage', 'discount_value' => '',
    'usage_limit' => '', 'expiry_date' => '', 'status' => 'active',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM promo_codes WHERE id = ?');
    $stmt->execute([$promoId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        set_flash('danger', 'Promo code not found.');
        redirect('admin/promo_codes.php');
    }
    $promo = $existing;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $code          = strtoupper(sanitize_input($_POST['code'] ?? ''));
    $discountType  = $_POST['discount_type'] ?? '';
    $discountValue = $_POST['discount_value'] ?? '';
    $usageLimit    = trim($_POST['usage_limit'] ?? '');
    $expiryDate    = trim($_POST['expiry_date'] ?? '');
    $status        = $_POST['status'] ?? 'active';

    $promo = [
        'code' => $code, 'discount_type' => $discountType, 'discount_value' => $discountValue,
        'usage_limit' => $usageLimit, 'expiry_date' => $expiryDate, 'status' => $status,
    ];

    if ($code === '' || !preg_match('/^[A-Z0-9_-]{3,50}$/', $code)) {
        $errors[] = 'Code must be 3-50 characters: letters, numbers, hyphens, or underscores only.';
    }
    if (!in_array($discountType, ['percentage', 'fixed'], true)) {
        $errors[] = 'Please select a valid discount type.';
    }
    if (!is_numeric($discountValue) || (float) $discountValue <= 0) {
        $errors[] = 'Discount value must be a positive number.';
    }
    if ($discountType === 'percentage' && is_numeric($discountValue) && (float) $discountValue > 100) {
        $errors[] = 'Percentage discount cannot exceed 100.';
    }
    if ($usageLimit !== '' && (!ctype_digit($usageLimit) || (int) $usageLimit <= 0)) {
        $errors[] = 'Usage limit must be a positive whole number, or left blank for unlimited.';
    }
    if ($expiryDate !== '' && !DateTime::createFromFormat('Y-m-d', $expiryDate)) {
        $errors[] = 'Please enter a valid expiry date.';
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Invalid status.';
    }

    if (empty($errors)) {
        $dupCheck = $pdo->prepare('SELECT id FROM promo_codes WHERE code = ? AND id != ?');
        $dupCheck->execute([$code, $promoId]);
        if ($dupCheck->fetch()) {
            $errors[] = 'A promo code with that code already exists.';
        }
    }

    if (empty($errors)) {
        $usageLimitValue = $usageLimit === '' ? null : (int) $usageLimit;
        $expiryDateValue = $expiryDate === '' ? null : $expiryDate;

        if ($isEdit) {
            $pdo->prepare(
                'UPDATE promo_codes SET code = ?, discount_type = ?, discount_value = ?, usage_limit = ?, expiry_date = ?, status = ? WHERE id = ?'
            )->execute([$code, $discountType, $discountValue, $usageLimitValue, $expiryDateValue, $status, $promoId]);

            set_flash('success', 'Promo code updated successfully.');
        } else {
            $pdo->prepare(
                'INSERT INTO promo_codes (code, discount_type, discount_value, usage_limit, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$code, $discountType, $discountValue, $usageLimitValue, $expiryDateValue, $status]);

            set_flash('success', 'Promo code added successfully.');
        }

        redirect('admin/promo_codes.php');
    }
}

$pageTitle = $isEdit ? 'Edit Promo Code' : 'Add Promo Code';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <span class="eyebrow-text">Marketing</span>
            <h2 class="section-heading mb-4"><?= $isEdit ? 'Edit Promo Code' : 'Add Promo Code' ?></h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="promo_id" value="<?= (int) $promoId ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control text-uppercase" value="<?= htmlspecialchars($promo['code']) ?>" placeholder="e.g. WELCOME10" required>
                            <div class="form-text">Letters, numbers, hyphens, underscores only. Automatically uppercased.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Discount Type</label>
                                <select name="discount_type" class="form-select">
                                    <option value="percentage" <?= $promo['discount_type'] === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                    <option value="fixed" <?= $promo['discount_type'] === 'fixed' ? 'selected' : '' ?>>Fixed Amount (<?= CURRENCY_SYMBOL ?>)</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Discount Value</label>
                                <input type="number" name="discount_value" step="0.01" min="0.01" class="form-control" value="<?= htmlspecialchars((string) $promo['discount_value']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-sm-6">
                                <label class="form-label">Usage Limit (optional)</label>
                                <input type="number" name="usage_limit" min="1" step="1" class="form-control" value="<?= htmlspecialchars((string) $promo['usage_limit']) ?>" placeholder="Unlimited">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Expiry Date (optional)</label>
                                <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars((string) $promo['expiry_date']) ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $promo['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $promo['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-coffee">Save Promo Code</button>
                        <a href="<?= BASE_URL ?>admin/promo_codes.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
