<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$userId = current_user_id();
$profileErrors = [];
$passwordErrors = [];

// Fetch current profile
$stmt = $pdo->prepare('SELECT name, email, phone FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $profileErrors[] = 'Invalid form submission. Please try again.';
    }

    $name  = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');

    if ($name === '' || $email === '' || $phone === '') {
        $profileErrors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileErrors[] = 'Please enter a valid email address.';
    }
    if ($phone !== '' && !is_valid_phone($phone)) {
        $profileErrors[] = 'Please enter a valid phone number.';
    }

    if (empty($profileErrors) && $email !== $user['email']) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            $profileErrors[] = 'That email is already in use by another account.';
        }
    }

    if (empty($profileErrors)) {
        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([$name, $email, $phone, $userId]);

        $_SESSION['name'] = $name;
        set_flash('success', 'Profile updated successfully.');
        redirect('customer/profile.php');
    }

    $user = ['name' => $name, 'email' => $email, 'phone' => $phone];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $passwordErrors[] = 'Invalid form submission. Please try again.';
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hashedPassword = $stmt->fetchColumn();

    if (!password_verify($currentPassword, $hashedPassword)) {
        $passwordErrors[] = 'Current password is incorrect.';
    }
    if (strlen($newPassword) < 6) {
        $passwordErrors[] = 'New password must be at least 6 characters long.';
    }
    if ($newPassword !== $confirmPassword) {
        $passwordErrors[] = 'New password and confirmation do not match.';
    }

    if (empty($passwordErrors)) {
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

        set_flash('success', 'Password changed successfully.');
        redirect('customer/profile.php');
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/customer_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="page-header text-lg-start">
                <span class="eyebrow-text">My Account</span>
                <h1 class="section-heading">My Profile</h1>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-person-vcard me-2"></i>Profile Information</h5>

                    <?php if (!empty($profileErrors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($profileErrors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
                        </div>

                        <button type="submit" class="btn btn-coffee">Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-shield-lock me-2"></i>Change Password</h5>

                    <?php if (!empty($passwordErrors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($passwordErrors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                        </div>

                        <button type="submit" class="btn btn-coffee">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
