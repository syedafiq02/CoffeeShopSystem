<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

$galleryId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['gallery_id']) ? (int) $_POST['gallery_id'] : 0);
$isEdit = $galleryId > 0;

$item = ['type' => 'shop', 'title' => '', 'product_id' => '', 'description' => '', 'image' => null];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM gallery WHERE id = ?');
    $stmt->execute([$galleryId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        set_flash('danger', 'Gallery item not found.');
        redirect('admin/gallery.php');
    }
    $item = $existing;
}

$products = $pdo->query('SELECT id, name FROM products ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $type        = $_POST['type'] ?? '';
    $title       = sanitize_input($_POST['title'] ?? '');
    $productId   = $_POST['product_id'] !== '' ? (int) $_POST['product_id'] : null;
    $description = sanitize_input($_POST['description'] ?? '');

    $item = [
        'type' => $type, 'title' => $title, 'product_id' => $productId,
        'description' => $description, 'image' => $item['image'],
    ];

    if (!in_array($type, ['product', 'shop', 'event', 'promotion'], true)) {
        $errors[] = 'Please select a valid type.';
    }
    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!$isEdit && empty($_FILES['image']['name'])) {
        $errors[] = 'An image is required.';
    }

    $newImageFilename = $item['image'];
    if (empty($errors) && !empty($_FILES['image']['name'])) {
        $upload = handle_image_upload($_FILES['image'], UPLOAD_GALLERY_DIR);
        if (!$upload['success']) {
            $errors[] = $upload['error'];
        } elseif ($upload['filename']) {
            $newImageFilename = $upload['filename'];
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            $oldImage = $pdo->prepare('SELECT image FROM gallery WHERE id = ?');
            $oldImage->execute([$galleryId]);
            $previousImage = $oldImage->fetchColumn();

            $pdo->prepare(
                'UPDATE gallery SET type = ?, title = ?, product_id = ?, description = ?, image = ? WHERE id = ?'
            )->execute([$type, $title, $productId, $description, $newImageFilename, $galleryId]);

            if ($newImageFilename !== $previousImage) {
                delete_uploaded_file(UPLOAD_GALLERY_DIR, $previousImage ?: null);
            }

            set_flash('success', 'Gallery item updated successfully.');
        } else {
            $pdo->prepare(
                'INSERT INTO gallery (type, title, product_id, description, image) VALUES (?, ?, ?, ?, ?)'
            )->execute([$type, $title, $productId, $description, $newImageFilename]);

            set_flash('success', 'Gallery item added successfully.');
        }

        redirect('admin/gallery.php');
    }
}

$pageTitle = $isEdit ? 'Edit Gallery Item' : 'Add Gallery Item';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <span class="eyebrow-text">Media</span>
            <h2 class="section-heading mb-4"><?= $isEdit ? 'Edit Gallery Item' : 'Add Gallery Item' ?></h2>

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
                            <input type="hidden" name="gallery_id" value="<?= (int) $galleryId ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <?php foreach (['shop' => 'Shop', 'product' => 'Product', 'event' => 'Event', 'promotion' => 'Promotion'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $item['type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Linked Product (optional)</label>
                            <select name="product_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= (int) $product['id'] ?>" <?= (string) $item['product_id'] === (string) $product['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Image<?= $isEdit ? '' : ' <span class="text-danger">*</span>' ?></label>
                            <?php if (!empty($item['image'])): ?>
                                <div class="mb-2">
                                    <img src="<?= UPLOAD_GALLERY_URL . htmlspecialchars($item['image']) ?>" alt="" style="width:100px;height:100px;object-fit:cover;" class="rounded d-block">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                            <div class="form-text">JPG, PNG, GIF, or WEBP. Max 2MB. <?= $isEdit ? 'Leave empty to keep the current image.' : '' ?></div>
                        </div>

                        <button type="submit" class="btn btn-coffee">Save</button>
                        <a href="<?= BASE_URL ?>admin/gallery.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
