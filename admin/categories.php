<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Invalid form submission. Please try again.');
        redirect('admin/categories.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $categoryName = sanitize_input($_POST['category_name'] ?? '');

        if ($categoryName === '') {
            set_flash('danger', 'Category name is required.');
            redirect('admin/categories.php');
        }

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $dupCheck = $pdo->prepare('SELECT id FROM categories WHERE category_name = ? AND id != ?');
        $dupCheck->execute([$categoryName, $categoryId]);
        if ($dupCheck->fetch()) {
            set_flash('danger', 'A category with that name already exists.');
            redirect('admin/categories.php');
        }

        if ($action === 'create') {
            $pdo->prepare('INSERT INTO categories (category_name) VALUES (?)')->execute([$categoryName]);
            set_flash('success', 'Category added successfully.');
        } else {
            $pdo->prepare('UPDATE categories SET category_name = ? WHERE id = ?')->execute([$categoryName, $categoryId]);
            set_flash('success', 'Category updated successfully.');
        }
    }

    if ($action === 'delete') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $countStmt->execute([$categoryId]);

        if ($countStmt->fetchColumn() > 0) {
            set_flash('danger', 'Cannot delete this category — it still has products assigned to it.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$categoryId]);
            set_flash('success', 'Category deleted successfully.');
        }
    }

    redirect('admin/categories.php');
}

$categories = $pdo->query(
    'SELECT c.id, c.category_name, COUNT(p.id) AS product_count
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.category_name
     ORDER BY c.category_name'
)->fetchAll();

$pageTitle = 'Manage Categories';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="eyebrow-text">Catalog</span>
                    <h2 class="section-heading mb-0">Categories</h2>
                </div>
                <button type="button" class="btn btn-coffee" data-bs-toggle="modal" data-bs-target="#categoryModal"
                        data-mode="create" data-id="" data-name="">
                    + Add Category
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <p class="text-muted mb-0">No categories yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Category</th><th>Products</th><th class="text-end">Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($category['category_name']) ?></td>
                                            <td><?= (int) $category['product_count'] ?></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                        data-bs-toggle="modal" data-bs-target="#categoryModal"
                                                        data-mode="update"
                                                        data-id="<?= (int) $category['id'] ?>"
                                                        data-name="<?= htmlspecialchars($category['category_name']) ?>">
                                                    Edit
                                                </button>
                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
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

<!-- Add/Edit Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" id="categoryModalAction" value="create">
                    <input type="hidden" name="category_id" id="categoryModalId" value="">

                    <label class="form-label">Category Name</label>
                    <input type="text" name="category_name" id="categoryModalName" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-coffee">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('categoryModal');
    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        var mode = btn.getAttribute('data-mode');
        document.getElementById('categoryModalTitle').textContent = mode === 'create' ? 'Add Category' : 'Edit Category';
        document.getElementById('categoryModalAction').value = mode;
        document.getElementById('categoryModalId').value = btn.getAttribute('data-id');
        document.getElementById('categoryModalName').value = btn.getAttribute('data-name');
    });
});
</script>
