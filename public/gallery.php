<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$galleryItems = $pdo->query(
    'SELECT id, type, title, image, description FROM gallery ORDER BY created_at DESC'
)->fetchAll();

$types = ['all' => 'All', 'shop' => 'Shop', 'product' => 'Products', 'event' => 'Events', 'promotion' => 'Promotions'];

$pageTitle = 'Gallery';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<section class="container py-5">
    <div class="page-header">
        <span class="eyebrow-text">Take a Look</span>
        <h1 class="section-heading">Gallery</h1>
        <p class="section-subheading">Our space, our drinks, and the moments in between.</p>
    </div>

    <div class="filter-bar mb-4">
        <?php foreach ($types as $typeKey => $typeLabel): ?>
            <button type="button"
                    class="btn btn-sm gallery-filter-btn <?= $typeKey === 'all' ? 'active' : '' ?>"
                    data-type="<?= $typeKey ?>">
                <?= htmlspecialchars($typeLabel) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php if (empty($galleryItems)): ?>
        <p class="text-center text-muted">No gallery items yet. Please check back soon.</p>
    <?php else: ?>
        <div class="row g-4 fade-in-section">
            <?php foreach ($galleryItems as $item): ?>
                <?php $imageUrl = UPLOAD_GALLERY_URL . htmlspecialchars($item['image']); ?>
                <div class="col-sm-6 col-lg-4 gallery-item" data-type="<?= htmlspecialchars($item['type']) ?>">
                    <div class="card h-100 shadow-sm" role="button"
                         data-bs-toggle="modal" data-bs-target="#galleryModal"
                         data-image="<?= $imageUrl ?>"
                         data-title="<?= htmlspecialchars($item['title']) ?>"
                         data-description="<?= htmlspecialchars($item['description']) ?>">
                        <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>" style="height:200px;object-fit:cover;">
                        <div class="card-body">
                            <span class="badge bg-coffee-cream text-dark mb-2"><?= htmlspecialchars(ucfirst($item['type'])) ?></span>
                            <h6 class="card-title mb-0"><?= htmlspecialchars($item['title']) ?></h6>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Lightbox modal -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="galleryModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="galleryModalImage" src="" alt="" class="img-fluid rounded mb-3">
                <p id="galleryModalDescription" class="text-muted"></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/gallery.js"></script>
