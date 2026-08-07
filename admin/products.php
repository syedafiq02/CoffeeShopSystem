<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('admin/products.php');
    }

    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'toggle_status') {
        $stmt = $pdo->prepare('SELECT status FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $current = $stmt->fetchColumn();
        if ($current !== false) {
            $newStatus = $current === 'active' ? 'inactive' : 'active';
            $pdo->prepare('UPDATE products SET status = ? WHERE id = ?')->execute([$newStatus, $productId]);
            set_flash('success', 'Product status updated.');
        }
    }

    if ($action === 'delete') {
        $usedStmt = $pdo->prepare('SELECT COUNT(*) FROM order_details WHERE product_id = ?');
        $usedStmt->execute([$productId]);

        if ($usedStmt->fetchColumn() > 0) {
            set_flash('danger', 'Cannot delete this product — it appears in existing orders. Set it to Inactive instead.');
        } else {
            $imgStmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
            $imgStmt->execute([$productId]);
            $image = $imgStmt->fetchColumn();

            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$productId]);
            delete_uploaded_file(UPLOAD_PRODUCTS_DIR, $image ?: null);
            set_flash('success', 'Product deleted successfully.');
        }
    }

    redirect('admin/products.php');
}

$products = $pdo->query(
    'SELECT p.id, p.name, p.price, p.image, p.status, c.category_name
     FROM products p JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC'
)->fetchAll();

$pageTitle = 'Manage Products';
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
                    <span class="eyebrow-text">Catalog</span>
                    <h2 class="section-heading mb-0">Products</h2>
                </div>
                <a href="<?= BASE_URL ?>admin/product_form.php" class="btn btn-coffee">+ Add Product</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($products)): ?>
                        <p class="text-muted mb-0">No products yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th class="text-end">Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <?php
                                        $imageUrl = $product['image']
                                            ? UPLOAD_PRODUCTS_URL . htmlspecialchars($product['image'])
                                            : BASE_URL . 'assets/images/product-placeholder.svg';
                                        ?>
                                        <tr>
                                            <td><img src="<?= $imageUrl ?>" alt="" style="width:48px;height:48px;object-fit:cover;" class="rounded"></td>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                                            <td><?= CURRENCY_SYMBOL ?> <?= number_format((float) $product['price'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars(ucfirst($product['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>admin/product_form.php?id=<?= (int) $product['id'] ?>" class="btn btn-outline-secondary btn-sm">Edit</a>

                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-warning btn-sm">
                                                        <?= $product['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>

                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this product?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
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
