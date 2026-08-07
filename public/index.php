<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$stmt = $pdo->query(
    'SELECT p.id, p.category_id, p.name, p.description, p.price, p.image, c.category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.status = "active"
     ORDER BY p.created_at DESC
     LIMIT 6'
);
$featuredProducts = $stmt->fetchAll();

$pageTitle = 'Home';
$hasHero = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Hero -->
<section class="hero-section py-5">
    <div class="container py-5 text-center">
        <span class="eyebrow-text">Premium Coffee Experience</span>
        <h1 class="hero-title fw-bold">Great Coffee, Great Company</h1>
        <p class="lead mb-4">Freshly roasted beans, handcrafted drinks, and warm pastries — made for your every morning.</p>
        <a href="<?= BASE_URL ?>public/menu.php" class="btn btn-gold btn-lg me-2">View Menu</a>
        <a href="<?= BASE_URL ?>public/location.php" class="btn btn-outline-light btn-lg">Find Us</a>
    </div>
</section>

<!-- Why choose us -->
<section class="container py-5 fade-in-section">
    <div class="text-center mb-5">
        <span class="eyebrow-text">Why Choose Us</span>
        <h2 class="section-heading">What Makes Us Different</h2>
        <p class="section-subheading">A few reasons regulars keep coming back.</p>
    </div>
    <div class="row g-4 text-center">
        <div class="col-sm-6 col-lg-3 feature-card">
            <div class="feature-icon"><i class="bi bi-cup-hot"></i></div>
            <h5>Freshly Roasted Daily</h5>
            <p class="text-muted small">Beans roasted in small batches for peak flavor in every single cup.</p>
        </div>
        <div class="col-sm-6 col-lg-3 feature-card">
            <div class="feature-icon"><i class="bi bi-award"></i></div>
            <h5>Crafted by Experts</h5>
            <p class="text-muted small">Our baristas train for months to pull the perfect shot, every time.</p>
        </div>
        <div class="col-sm-6 col-lg-3 feature-card">
            <div class="feature-icon"><i class="bi bi-truck"></i></div>
            <h5>Fast Pickup &amp; Delivery</h5>
            <p class="text-muted small">Order online and skip the line, or have it delivered to your door.</p>
        </div>
        <div class="col-sm-6 col-lg-3 feature-card">
            <div class="feature-icon"><i class="bi bi-heart"></i></div>
            <h5>Community First</h5>
            <p class="text-muted small">A warm, welcoming space to work, meet, and unwind.</p>
        </div>
    </div>
</section>

<!-- Featured products -->
<section class="container py-5 fade-in-section">
    <div class="text-center mb-5">
        <span class="eyebrow-text">Fan Favorites</span>
        <h2 class="section-heading">Featured Products</h2>
        <p class="section-subheading">Handpicked favorites, freshly made.</p>
    </div>
    <?php if (empty($featuredProducts)): ?>
        <p class="text-center text-muted">No products available yet. Please check back soon.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <?php require __DIR__ . '/../includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>public/menu.php" class="btn btn-coffee">See Full Menu</a>
    </div>
</section>

<!-- Coffee shop story teaser -->
<section class="container py-5 fade-in-section">
    <div class="story-panel">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="eyebrow-text">Our Story</span>
                <h2 class="section-heading">Crafted With Passion Since Day One</h2>
                <p class="text-muted mb-4">
                    What began as a single counter and a secondhand espresso machine has grown into a
                    neighborhood favorite — but the goal has never changed: pour a genuinely great cup
                    of coffee, every single time.
                </p>
                <a href="<?= BASE_URL ?>public/about.php" class="btn btn-coffee">Read Our Story</a>
            </div>
            <div class="col-lg-5 text-center">
                <img src="<?= BASE_URL ?>assets/images/logo.png" alt="<?= htmlspecialchars(SITE_NAME) ?>" style="max-width: 220px; width: 100%;">
            </div>
        </div>
    </div>
</section>

<!-- Promotions -->
<section class="promo-band py-5 fade-in-section">
    <div class="container text-center">
        <span class="eyebrow-text">Limited Time</span>
        <h2 class="section-heading text-white">Buy 1 Free 1 on All Coffee</h2>
        <p class="mb-4" style="color: rgba(255,255,255,0.85);">Every Monday, all day. No code needed — just order in-store or online.</p>
        <a href="<?= BASE_URL ?>public/contact.php" class="btn btn-gold">Ask Us More</a>
    </div>
</section>

<!-- Customer reviews -->
<section class="bg-coffee-cream py-5 fade-in-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="eyebrow-text">Testimonials</span>
            <h2 class="section-heading">What Our Customers Say</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card review-card h-100">
                    <div class="card-body">
                        <span class="review-quote-icon"><i class="bi bi-quote"></i></span>
                        <p class="card-text">"Best latte in town, and the staff always remembers my order!"</p>
                        <p class="fw-bold mb-0">— Aisyah R.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card review-card h-100">
                    <div class="card-body">
                        <span class="review-quote-icon"><i class="bi bi-quote"></i></span>
                        <p class="card-text">"Cozy spot to work from, great wifi and even better croissants."</p>
                        <p class="fw-bold mb-0">— Daniel T.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card review-card h-100">
                    <div class="card-body">
                        <span class="review-quote-icon"><i class="bi bi-quote"></i></span>
                        <p class="card-text">"Ordering online for pickup saved me so much time. Love it."</p>
                        <p class="fw-bold mb-0">— Priya M.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final call to action -->
<section class="container py-5 fade-in-section">
    <div class="cta-band text-center p-5">
        <h2 class="section-heading text-white">Ready to Taste the Difference?</h2>
        <p class="mb-4" style="color: rgba(255,255,255,0.85);">Order online for pickup or delivery — your next favorite cup is a few clicks away.</p>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>public/menu.php" class="btn btn-gold btn-lg">Order Now</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-gold btn-lg">Join Now &amp; Order</a>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/product_modal.php'; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/menu.js"></script>
<script src="<?= BASE_URL ?>assets/js/cart.js"></script>
