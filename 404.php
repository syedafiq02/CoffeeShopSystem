<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

http_response_code(404);

$pageTitle = 'Page Not Found';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5 text-center">
    <h1 class="display-1">404</h1>
    <p class="lead">Sorry, the page you're looking for doesn't exist.</p>
    <a href="<?= BASE_URL ?>public/index.php" class="btn btn-coffee">Back to Home</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
