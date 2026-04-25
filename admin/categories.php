<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout_top.php';

$message = '';
$error = '';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
}
$allowedIds = allowedMenuIds();
$isSuper = isSuperAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $order = (int) ($_POST['sort_order'] ?? 0);
        $selectedExisting = array_values(array_unique(array_filter(array_map(
            static fn ($v): string => trim((string) $v),
            (array) ($_POST['existing_names'] ?? [])
        ))));

        if ($menuId <= 0 || !canAccessMenu($menuId)) {
            $error = 'Please select a valid menu.';
        } elseif ($name === '' && empty($selectedExisting)) {
            $error = 'Enter a category name or check at least one existing category.';
        } else {
            $inserted = 0;
            $existsStmt = $pdo->prepare('SELECT id FROM categories WHERE menu_id = ? AND LOWER(name) = LOWER(?) LIMIT 1');
            $insertStmt = $pdo->prepare('INSERT INTO categories (menu_id, name, sort_order, created_at) VALUES (?, ?, ?, NOW())');

            if ($name !== '') {
                $existsStmt->execute([$menuId, $name]);
                if (!$existsStmt->fetchColumn()) {
                    $insertStmt->execute([$menuId, $name, $order]);
                    $inserted++;
                }
            }

            foreach ($selectedExisting as $existingName) {
                $existsStmt->execute([$menuId, $existingName]);
                if (!$existsStmt->fetchColumn()) {
                    $insertStmt->execute([$menuId, $existingName, $order]);
                    $inserted++;
                }
            }

            $message = $inserted > 0
                ? ($inserted === 1 ? 'Category added.' : $inserted . ' categories added.')
                : 'No new categories were added (already exists for this menu).';
            setFlashMessage($message);
            header('Location: categories.php');
            exit;
        }
    }
    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $order = (int) ($_POST['sort_order'] ?? 0);

        if ($id <= 0 || $menuId <= 0 || !canAccessMenu($menuId)) {
            $error = 'Invalid category update request.';
        } elseif ($name === '') {
            $error = 'Category name is required for update.';
        } else {
            $matchStmt = $pdo->prepare('SELECT id FROM categories WHERE id = ? AND menu_id = ? LIMIT 1');
            $matchStmt->execute([$id, $menuId]);
            if (!$matchStmt->fetchColumn()) {
                $error = 'Category not found for this menu.';
            } else {
                $dupStmt = $pdo->prepare('SELECT id FROM categories WHERE menu_id = ? AND LOWER(name) = LOWER(?) AND id <> ? LIMIT 1');
                $dupStmt->execute([$menuId, $name, $id]);
                if ($dupStmt->fetchColumn()) {
                    $error = 'A category with the same name already exists in this menu.';
                } else {
                    $updStmt = $pdo->prepare('UPDATE categories SET name = ?, sort_order = ? WHERE id = ?');
                    $updStmt->execute([$name, $order, $id]);
                    setFlashMessage('Category updated.');
                    header('Location: categories.php');
                    exit;
                }
            }
        }
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT menu_id FROM categories WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $menuId = (int) ($stmt->fetchColumn() ?: 0);
            if (canAccessMenu($menuId)) {
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                setFlashMessage('Category deleted.');
                header('Location: categories.php');
                exit;
            }
        }
    }
}

if ($isSuper) {
    $menus = $pdo->query('SELECT id, name FROM menus ORDER BY id DESC')->fetchAll();
    $rows = $pdo->query('
        SELECT c.*, m.name AS menu_name
        FROM categories c
        JOIN menus m ON m.id = c.menu_id
        ORDER BY c.id DESC
    ')->fetchAll();
} elseif (!empty($allowedIds)) {
    $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
    $menuStmt = $pdo->prepare("SELECT id, name FROM menus WHERE id IN ($placeholders) ORDER BY id DESC");
    $menuStmt->execute($allowedIds);
    $menus = $menuStmt->fetchAll();

    $rowStmt = $pdo->prepare("
        SELECT c.*, m.name AS menu_name
        FROM categories c
        JOIN menus m ON m.id = c.menu_id
        WHERE c.menu_id IN ($placeholders)
        ORDER BY c.id DESC
    ");
    $rowStmt->execute($allowedIds);
    $rows = $rowStmt->fetchAll();
} else {
    $menus = [];
    $rows = [];
}

$selectedMenuId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $selectedMenuId = (int) ($_POST['menu_id'] ?? 0);
}
if ($selectedMenuId <= 0 && !empty($menus)) {
    $selectedMenuId = (int) $menus[0]['id'];
}
$selectedExistingFromPost = array_values(array_unique(array_filter(array_map(
    static fn ($v): string => trim((string) $v),
    (array) ($_POST['existing_names'] ?? [])
))));

$presetCategoryNames = [
    'Breakfast',
    'Lunch',
    'Dinner',
    'Appetizers',
    'Main Course',
    'Desserts',
    'Drinks',
    'Hot Beverages',
    'Cold Beverages',
    'Kids Menu',
    'Vegan',
    'Special Offers',
];
?>
<div class="card compact-card">
    <h2 style="margin-top:0;">Add Category</h2>
    <?php if ($message): ?><p style="color:#059669;"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:#dc2626;"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <div class="responsive-two-col">
            <div>
                <label>Menu</label>
                <select name="menu_id" required>
                    <?php foreach ($menus as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= ((int) $m['id'] === $selectedMenuId) ? 'selected' : '' ?>>
                            <?= e($m['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Category Name</label>
                <input name="name" placeholder="Enter category name">
            </div>
        </div>
        <?php if (!empty($presetCategoryNames)): ?>
            <label style="margin-top:8px;">Or pick from existing categories</label>
            <div class="preset-grid-4rows">
                <?php foreach ($presetCategoryNames as $catName): ?>
                    <label style="display:flex;align-items:center;gap:8px;margin:0 0 6px;font-weight:normal;">
                        <input type="checkbox" name="existing_names[]" value="<?= e($catName) ?>" style="width:auto;" <?= in_array($catName, $selectedExistingFromPost, true) ? 'checked' : '' ?>>
                        <?= e($catName) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="muted" style="margin-top:8px;font-size:13px;">Multi-select supported: check one or more categories and save once.</p>
        <?php endif; ?>
        <label>Sort Order</label>
        <input name="sort_order" type="number" value="0">
        <button class="btn" type="submit">Save Category</button>
    </form>
</div>

<div class="card">
    <style>
        .btn-mini {
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 12px;
            line-height: 1;
        }
        .categories-table-wrap {
            margin-top: 10px;
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .categories-table {
            width: 100%;
            border-collapse: collapse;
        }
        .categories-table th,
        .categories-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
        }
        .categories-table thead th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            background: #f8fafc;
        }
        .categories-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .categories-edit-row td {
            background: #fcfcfd;
        }
        .categories-edit-form {
            display: none;
            padding: 8px 0;
        }
        .categories-edit-form.is-open {
            display: block;
        }
        .categories-edit-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.5fr) auto auto;
            gap: 8px;
            align-items: end;
        }
        .empty-categories {
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
        }
        .categories-filter-bar {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            margin: 10px 0 6px;
        }
        .categories-filter-field {
            min-width: min(280px, 100%);
        }
        .categories-filter-empty {
            display: none;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
            margin-top: 8px;
        }
        @media (max-width: 760px) {
            .categories-edit-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <h3 style="margin-top:0;">All Categories - Modern View</h3>
    <?php if (empty($rows)): ?>
        <div class="empty-categories">No categories found yet.</div>
    <?php else: ?>
        <div class="categories-filter-bar">
            <div class="categories-filter-field">
                <label>Filter by menu</label>
                <select id="categoriesMenuFilter">
                    <option value="">Select menu</option>
                    <?php foreach ($menus as $menu): ?>
                        <option value="<?= (int) $menu['id'] ?>"><?= e($menu['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="categories-table-wrap">
            <table class="categories-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr class="categories-data-row" data-menu-id="<?= (int) $row['menu_id'] ?>" style="display:none;">
                            <td><?= e($row['name']) ?></td>
                            <td># <?= (int) $row['sort_order'] ?></td>
                            <td>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <button type="button" class="btn btn-outline btn-mini js-toggle-edit">Edit</button>
                                    <form method="post" onsubmit="return confirm('Delete category?');" style="margin:0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-outline btn-mini btn-delete" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="categories-edit-row" data-menu-id="<?= (int) $row['menu_id'] ?>" style="display:none;">
                            <td colspan="3">
                                <form method="post" class="categories-edit-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <input type="hidden" name="menu_id" value="<?= (int) $row['menu_id'] ?>">
                                    <div class="categories-edit-grid">
                                        <div>
                                            <label>Category Name</label>
                                            <input name="name" value="<?= e($row['name']) ?>" required>
                                        </div>
                                        <div>
                                            <label>Order</label>
                                            <input name="sort_order" type="number" value="<?= (int) $row['sort_order'] ?>">
                                        </div>
                                        <button class="btn btn-mini" type="submit">Save</button>
                                        <button type="button" class="btn btn-outline btn-mini js-cancel-edit">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="categoriesFilterEmpty" class="categories-filter-empty">Select menu to display categories.</div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.js-toggle-edit');
    editButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row = this.closest('tr');
            const editRow = row ? row.nextElementSibling : null;
            const form = editRow ? editRow.querySelector('.categories-edit-form') : null;
            if (!form) {
                return;
            }
            form.classList.toggle('is-open');
        });
    });

    const cancelButtons = document.querySelectorAll('.js-cancel-edit');
    cancelButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = this.closest('.categories-edit-form');
            if (form) {
                form.classList.remove('is-open');
            }
        });
    });

    const menuFilter = document.getElementById('categoriesMenuFilter');
    const categoryRows = document.querySelectorAll('.categories-data-row');
    const editRows = document.querySelectorAll('.categories-edit-row');
    const filterEmpty = document.getElementById('categoriesFilterEmpty');
    if (menuFilter && categoryRows.length > 0) {
        function applyMenuFilter() {
            const selected = menuFilter.value;
            let visibleCount = 0;

            categoryRows.forEach(function (row) {
                const menuId = row.getAttribute('data-menu-id');
                const show = selected !== '' && menuId === selected;
                row.style.display = show ? '' : 'none';
                if (show) {
                    visibleCount++;
                }
            });
            editRows.forEach(function (row) {
                const menuId = row.getAttribute('data-menu-id');
                const show = selected !== '' && menuId === selected;
                row.style.display = show ? '' : 'none';
                if (!show) {
                    const form = row.querySelector('.categories-edit-form');
                    if (form) {
                        form.classList.remove('is-open');
                    }
                }
            });

            if (filterEmpty) {
                filterEmpty.textContent = selected === ''
                    ? 'Select menu to display categories.'
                    : (visibleCount === 0 ? 'No categories found for selected menu.' : '');
                filterEmpty.style.display = (selected === '' || visibleCount === 0) ? 'block' : 'none';
            }
        }

        menuFilter.addEventListener('change', applyMenuFilter);
        applyMenuFilter();
    }
});
</script>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
