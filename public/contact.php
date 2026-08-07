<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $name    = sanitize_input($_POST['name'] ?? '');
    $email   = sanitize_input($_POST['email'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');
    $old = ['name' => $name, 'email' => $email, 'message' => $message];

    if ($name === '' || $email === '' || $message === '') {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $message]);

        set_flash('success', 'Thanks for reaching out! We will get back to you soon.');
        redirect('public/contact.php');
    }
}

$pageTitle = 'Contact Us';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<section class="container py-5">
    <div class="page-header">
        <span class="eyebrow-text">Contact Us</span>
        <h1 class="section-heading">Get In Touch</h1>
        <p class="section-subheading">Questions, feedback, or just want to say hi? We'd love to hear from you.</p>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-lg-4">
            <div class="contact-info-item">
                <div class="contact-info-icon"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <h6 class="mb-1">Visit Us</h6>
                    <p class="text-muted small mb-0">
                        See our address and opening hours on the
                        <a href="<?= BASE_URL ?>public/location.php">Location page</a>.
                    </p>
                </div>
            </div>
            <div class="contact-info-item">
                <div class="contact-info-icon"><i class="bi bi-clock"></i></div>
                <div>
                    <h6 class="mb-1">We Reply Fast</h6>
                    <p class="text-muted small mb-0">Our team typically responds within 24 hours.</p>
                </div>
            </div>
            <div class="contact-info-item">
                <div class="contact-info-icon"><i class="bi bi-cup-hot"></i></div>
                <div>
                    <h6 class="mb-1">Feedback Welcome</h6>
                    <p class="text-muted small mb-0">Tell us how we can make your next visit even better.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="mb-3">
                            <label class="form-label">Your Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($old['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required><?= htmlspecialchars($old['message']) ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-coffee w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
