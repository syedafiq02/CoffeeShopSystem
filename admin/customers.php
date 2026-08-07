<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('admin/customers.php');
    }

    if (($_POST['action'] ?? '') === 'toggle_role') {
        $targetId = (int) ($_POST['user_id'] ?? 0);

        if ($targetId === current_user_id()) {
            set_flash('danger', 'You cannot change your own role.');
            redirect('admin/customers.php');
        }

        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $currentRole = $stmt->fetchColumn();

        if ($currentRole === false) {
            set_flash('danger', 'User not found.');
            redirect('admin/customers.php');
        }

        if ($currentRole === 'admin') {
            $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($adminCount <= 1) {
                set_flash('danger', 'Cannot demote the last remaining admin.');
                redirect('admin/customers.php');
            }
        }

        $newRole = $currentRole === 'admin' ? 'customer' : 'admin';
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $targetId]);
        set_flash('success', 'User role updated to ' . $newRole . '.');
    }

    redirect('admin/customers.php');
}

$roleFilter = $_GET['role'] ?? 'all';
$sql = 'SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at,
               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
        FROM users u';
$params = [];

if (in_array($roleFilter, ['customer', 'admin'], true)) {
    $sql .= ' WHERE u.role = ?';
    $params[] = $roleFilter;
}
$sql .= ' ORDER BY u.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Customers';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <span class="eyebrow-text">People</span>
            <h2 class="section-heading mb-4">Customers</h2>

            <div class="mb-3">
                <a href="?role=all" class="btn btn-sm <?= $roleFilter === 'all' ? 'btn-coffee' : 'btn-outline-secondary' ?>">All</a>
                <a href="?role=customer" class="btn btn-sm <?= $roleFilter === 'customer' ? 'btn-coffee' : 'btn-outline-secondary' ?>">Customers</a>
                <a href="?role=admin" class="btn btn-sm <?= $roleFilter === 'admin' ? 'btn-coffee' : 'btn-outline-secondary' ?>">Admins</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <p class="text-muted mb-0">No users found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Orders</th><th>Joined</th><th class="text-end">Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['phone']) ?></td>
                                            <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'coffee-brown' : 'secondary' ?>"><?= htmlspecialchars(ucfirst($user['role'])) ?></span></td>
                                            <td><?= (int) $user['order_count'] ?></td>
                                            <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                            <td class="text-end">
                                                <?php if ((int) $user['id'] === current_user_id()): ?>
                                                    <span class="text-muted small">This is you</span>
                                                <?php else: ?>
                                                    <form method="POST" action="" onsubmit="return confirm('Change this user\'s role to <?= $user['role'] === 'admin' ? 'customer' : 'admin' ?>?');">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="action" value="toggle_role">
                                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                            <?= $user['role'] === 'admin' ? 'Demote to Customer' : 'Promote to Admin' ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
