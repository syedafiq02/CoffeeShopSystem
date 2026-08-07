<?php
// Reusable page header. Requires BASE_URL (config/constants.php) to already
// be loaded by the including page. $pageTitle is optional.
// $hasHero (optional, bool) flags pages with a hero section immediately
// below the navbar, so the navbar can render transparent until scrolled.
$pageTitle = $pageTitle ?? SITE_NAME;
$hasHero = $hasHero ?? false;
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/images/logov3.png">

    <!-- Google Fonts: Playfair Display (headings) + Inter (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom site styles -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">

    <script>
        const BASE_URL = <?= json_encode(BASE_URL) ?>;
        const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
    </script>
</head>
<body<?= $hasHero ? ' class="has-hero"' : '' ?>>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> text-center mb-0 rounded-0" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>
