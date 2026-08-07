<?php
// Deliberately does NOT require config/db.php — this page must still render
// even when the database itself is the thing that failed.
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

http_response_code(500);

$pageTitle = 'Server Error';
require_once __DIR__ . '/includes/header.php';
?>

<nav class="navbar navbar-light bg-coffee-cream shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>public/index.php">
            <img src="<?= BASE_URL ?>assets/images/logo.png" alt="<?= htmlspecialchars(SITE_NAME) ?>" height="48">
        </a>
    </div>
</nav>

<div class="container py-5 text-center">
    <h1 class="display-1">500</h1>
    <p class="lead">Something went wrong on our end. Please try again shortly.</p>
    <a href="<?= BASE_URL ?>public/index.php" class="btn btn-coffee">Back to Home</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
