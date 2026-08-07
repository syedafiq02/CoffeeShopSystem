    <footer class="pt-5 pb-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <img src="<?= BASE_URL ?>assets/images/logov3.png" alt="<?= htmlspecialchars(SITE_NAME) ?>" height="56" class="mb-3">
                    <p class="footer-tagline mb-0">
                        Crafted coffee, served with warmth. Small-batch roasted and brewed with care, every single day.
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="footer-heading">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?= BASE_URL ?>public/index.php">Home</a></li>
                        <li><a href="<?= BASE_URL ?>public/about.php">About Us</a></li>
                        <li><a href="<?= BASE_URL ?>public/menu.php">Menu</a></li>
                        <li><a href="<?= BASE_URL ?>public/gallery.php">Gallery</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="footer-heading">Visit Us</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?= BASE_URL ?>public/location.php">Store Location &amp; Hours</a></li>
                        <li><a href="<?= BASE_URL ?>public/contact.php">Contact Us</a></li>
                    </ul>
                </div>
            </div>

            <hr>

            <p class="footer-copyright text-center mb-0 small">
                &copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS bundle (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/navbar-scroll.js"></script>
    <script src="<?= BASE_URL ?>assets/js/scroll-animations.js"></script>
</body>
</html>
