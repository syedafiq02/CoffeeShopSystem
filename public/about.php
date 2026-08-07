<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'About Us';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<section class="container py-5">
    <div class="page-header">
        <span class="eyebrow-text">Our Journey</span>
        <h1 class="section-heading">Our Story</h1>
    </div>

    <div class="row justify-content-center fade-in-section">
        <div class="col-lg-8">
            <p>
                <?= htmlspecialchars(SITE_NAME) ?> started as a single counter with two chairs and a
                secondhand espresso machine. What began as a passion project between friends who couldn't
                agree on the "perfect" cup has grown into a neighborhood favorite — but the goal has never
                changed: pour a genuinely great cup of coffee, every single time.
            </p>
            <p>
                Every bean we serve is roasted in small batches, and every pastry is baked fresh each
                morning. We believe a coffee shop should feel like a second home — a place to slow down,
                catch up with a friend, or get an hour of focused work done.
            </p>
        </div>
    </div>

    <div class="row mt-5 g-4 fade-in-section">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h4><i class="bi bi-eye me-2"></i>Our Vision</h4>
                    <p class="text-muted mb-0">
                        To be the neighborhood's most loved coffee spot — known as much for our warmth
                        and consistency as for the quality in every cup.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h4><i class="bi bi-bullseye me-2"></i>Our Mission</h4>
                    <p class="text-muted mb-0">
                        Source responsibly, roast with care, and serve every customer like a regular —
                        whether it's their first visit or their five-hundredth.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
