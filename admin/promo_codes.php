<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('admin/promo_codes.php');
    }

    $action = $_POST['action'] ?? '';
    $promoId = (int) ($_POST['promo_id'] ?? 0);

    if ($action === 'toggle_status') {
        $stmt = $pdo->prepare('SELECT status FROM promo_codes WHERE id = ?');
        $stmt->execute([$promoId]);
        $current = $stmt->fetchColumn();
        if ($current !== false) {
            $newStatus = $current === 'active' ? 'inactive' : 'active';
            $pdo->prepare('UPDATE promo_codes SET status = ? WHERE id = ?')->execute([$newStatus, $promoId]);
            set_flash('success', 'Promo code status updated.');
        }
    }

    if ($action === 'delete') {
        $usedStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE promo_code_id = ?');
        $usedStmt->execute([$promoId]);

        if ($usedStmt->fetchColumn() > 0) {
            set_flash('danger', 'Cannot delete this code — it has already been used on an order. Deactivate it instead.');
        } else {
            $pdo->prepare('DELETE FROM promo_codes WHERE id = ?')->execute([$promoId]);
            set_flash('success', 'Promo code deleted successfully.');
        }
    }

    redirect('admin/promo_codes.php');
}

$promoCodes = $pdo->query(
    'SELECT pc.*, (SELECT COUNT(*) FROM orders o WHERE o.promo_code_id = pc.id) AS times_used
     FROM promo_codes pc
     ORDER BY pc.created_at DESC'
)->fetchAll();

$pageTitle = 'Manage Promo Codes';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="eyebrow-text">Marketing</span>
                    <h2 class="section-heading mb-0">Promo Codes</h2>
                </div>
                <a href="<?= BASE_URL ?>admin/promo_form.php" class="btn btn-coffee">+ Add Promo Code</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($promoCodes)): ?>
                        <p class="text-muted mb-0">No promo codes yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr><th>Code</th><th>Discount</th><th>Usage</th><th>Expiry</th><th>Status</th><th class="text-end">Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($promoCodes as $promo): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($promo['code']) ?></code></td>
                                            <td>
                                                <?php if ($promo['discount_type'] === 'percentage'): ?>
                                                    <?= number_format((float) $promo['discount_value'], 2) ?>% off
                                                <?php else: ?>
                                                    <?= CURRENCY_SYMBOL ?> <?= number_format((float) $promo['discount_value'], 2) ?> off
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= (int) $promo['times_used'] ?><?= $promo['usage_limit'] !== null ? ' / ' . (int) $promo['usage_limit'] : '' ?>
                                            </td>
                                            <td><?= $promo['expiry_date'] ? date('d M Y', strtotime($promo['expiry_date'])) : '—' ?></td>
                                            <td>
                                                <span class="badge bg-<?= $promo['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars(ucfirst($promo['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>admin/promo_form.php?id=<?= (int) $promo['id'] ?>" class="btn btn-outline-secondary btn-sm">Edit</a>

                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="promo_id" value="<?= (int) $promo['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-warning btn-sm">
                                                        <?= $promo['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>

                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this promo code?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="promo_id" value="<?= (int) $promo['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
