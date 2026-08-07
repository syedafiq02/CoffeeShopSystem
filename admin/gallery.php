<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('admin/gallery.php');
    }

    if (($_POST['action'] ?? '') === 'delete') {
        $galleryId = (int) ($_POST['gallery_id'] ?? 0);

        $imgStmt = $pdo->prepare('SELECT image FROM gallery WHERE id = ?');
        $imgStmt->execute([$galleryId]);
        $image = $imgStmt->fetchColumn();

        $pdo->prepare('DELETE FROM gallery WHERE id = ?')->execute([$galleryId]);
        delete_uploaded_file(UPLOAD_GALLERY_DIR, $image ?: null);
        set_flash('success', 'Gallery item deleted successfully.');
    }

    redirect('admin/gallery.php');
}

$galleryItems = $pdo->query(
    'SELECT g.id, g.type, g.title, g.image, p.name AS product_name
     FROM gallery g LEFT JOIN products p ON p.id = g.product_id
     ORDER BY g.created_at DESC'
)->fetchAll();

$pageTitle = 'Manage Gallery';
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
                    <span class="eyebrow-text">Media</span>
                    <h2 class="section-heading mb-0">Gallery</h2>
                </div>
                <a href="<?= BASE_URL ?>admin/gallery_form.php" class="btn btn-coffee">+ Add Gallery Item</a>
            </div>

            <?php if (empty($galleryItems)): ?>
                <div class="card shadow-sm"><div class="card-body text-muted">No gallery items yet.</div></div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($galleryItems as $item): ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <img src="<?= UPLOAD_GALLERY_URL . htmlspecialchars($item['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>" style="height:150px;object-fit:cover;">
                                <div class="card-body">
                                    <span class="badge bg-coffee-cream text-dark mb-2"><?= htmlspecialchars(ucfirst($item['type'])) ?></span>
                                    <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                    <?php if ($item['product_name']): ?>
                                        <p class="small text-muted mb-2">Linked: <?= htmlspecialchars($item['product_name']) ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between">
                                        <a href="<?= BASE_URL ?>admin/gallery_form.php?id=<?= (int) $item['id'] ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                                        <form method="POST" action="" onsubmit="return confirm('Delete this gallery item?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="gallery_id" value="<?= (int) $item['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
