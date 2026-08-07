<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$productId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0);
$isEdit = $productId > 0;

$product = ['name' => '', 'category_id' => '', 'description' => '', 'price' => '', 'image' => null, 'status' => 'active'];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        set_flash('danger', 'Product not found.');
        redirect('admin/products.php');
    }
    $product = $existing;
}

$categories = $pdo->query('SELECT id, category_name FROM categories ORDER BY category_name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $name        = sanitize_input($_POST['name'] ?? '');
    $categoryId  = (int) ($_POST['category_id'] ?? 0);
    $description = sanitize_input($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $status      = $_POST['status'] ?? 'active';

    $product = [
        'name' => $name, 'category_id' => $categoryId, 'description' => $description,
        'price' => $price, 'image' => $product['image'], 'status' => $status,
    ];

    if ($name === '' || $categoryId <= 0 || $description === '' || $price === '') {
        $errors[] = 'All fields are required.';
    }
    if (!is_numeric($price) || (float) $price <= 0) {
        $errors[] = 'Price must be a positive number.';
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Invalid status.';
    }

    $newImageFilename = $product['image'];
    if (empty($errors) && !empty($_FILES['image']['name'])) {
        $upload = handle_image_upload($_FILES['image'], UPLOAD_PRODUCTS_DIR);
        if (!$upload['success']) {
            $errors[] = $upload['error'];
        } elseif ($upload['filename']) {
            $newImageFilename = $upload['filename'];
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            $oldImage = $pdo->prepare('SELECT image FROM products WHERE id = ?');
            $oldImage->execute([$productId]);
            $previousImage = $oldImage->fetchColumn();

            $pdo->prepare(
                'UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, image = ?, status = ? WHERE id = ?'
            )->execute([$categoryId, $name, $description, $price, $newImageFilename, $status, $productId]);

            if ($newImageFilename !== $previousImage) {
                delete_uploaded_file(UPLOAD_PRODUCTS_DIR, $previousImage ?: null);
            }

            set_flash('success', 'Product updated successfully.');
        } else {
            $pdo->prepare(
                'INSERT INTO products (category_id, name, description, price, image, status) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$categoryId, $name, $description, $price, $newImageFilename, $status]);

            set_flash('success', 'Product added successfully.');
        }

        redirect('admin/products.php');
    }
}

$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <span class="eyebrow-text">Catalog</span>
            <h2 class="section-heading mb-4"><?= $isEdit ? 'Edit Product' : 'Add Product' ?></h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="product_id" value="<?= (int) $productId ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= (int) $product['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" name="price" step="0.01" min="0.01" class="form-control" value="<?= htmlspecialchars((string) $product['price']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <?php if (!empty($product['image'])): ?>
                                <div class="mb-2">
                                    <img src="<?= UPLOAD_PRODUCTS_URL . htmlspecialchars($product['image']) ?>" alt="" style="width:100px;height:100px;object-fit:cover;" class="rounded d-block">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                            <div class="form-text">JPG, PNG, GIF, or WEBP. Max 2MB. <?= $isEdit ? 'Leave empty to keep the current image.' : '' ?></div>
                        </div>

                        <button type="submit" class="btn btn-coffee">Save Product</button>
                        <a href="<?= BASE_URL ?>admin/products.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
