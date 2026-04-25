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
    $menuId = (int) ($_POST['menu_id'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $manualCategoryName = trim((string) ($_POST['manual_category_name'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $order = (int) ($_POST['sort_order'] ?? 0);
    $isVisible = isset($_POST['is_visible']) ? 1 : 0;

    if (!canAccessMenu($menuId)) {
        $error = 'You are not allowed to add items to this menu.';
    } elseif ($menuId <= 0 || $name === '') {
        $error = 'Menu and item name are required.';
    } else {
        $validCategory = 0;
        if ($manualCategoryName !== '') {
            $manualStmt = $pdo->prepare('SELECT id FROM categories WHERE menu_id = ? AND LOWER(name) = LOWER(?) LIMIT 1');
            $manualStmt->execute([$menuId, $manualCategoryName]);
            $validCategory = (int) ($manualStmt->fetchColumn() ?: 0);

            if ($validCategory <= 0) {
                $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories WHERE menu_id = ?');
                $orderStmt->execute([$menuId]);
                $newOrder = (int) $orderStmt->fetchColumn();

                $createCatStmt = $pdo->prepare('INSERT INTO categories (menu_id, name, sort_order, created_at) VALUES (?, ?, ?, NOW())');
                $createCatStmt->execute([$menuId, $manualCategoryName, $newOrder]);
                $validCategory = (int) $pdo->lastInsertId();
            }
        } else {
            $catStmt = $pdo->prepare('SELECT id FROM categories WHERE id = ? AND menu_id = ? LIMIT 1');
            $catStmt->execute([$categoryId, $menuId]);
            $validCategory = (int) ($catStmt->fetchColumn() ?: 0);
        }

        if ($validCategory <= 0) {
            $error = 'Select a category from the selected menu or type a new category.';
        } else {
            $image = uploadImage($_FILES['image'] ?? [], 'items');
            $stmt = $pdo->prepare('INSERT INTO items (menu_id, category_id, name, description, price, image, sort_order, is_visible, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$menuId, $validCategory, $name, $description, $price, $image, $order, $isVisible]);
            setFlashMessage('Item created successfully.');
            header('Location: item_add.php');
            exit;
        }
    }
}

if ($isSuper) {
    $menus = $pdo->query('SELECT id, name FROM menus ORDER BY id DESC')->fetchAll();
    $categories = $pdo->query('SELECT id, menu_id, name FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
} elseif (!empty($allowedIds)) {
    $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
    $menuStmt = $pdo->prepare("SELECT id, name FROM menus WHERE id IN ($placeholders) ORDER BY id DESC");
    $menuStmt->execute($allowedIds);
    $menus = $menuStmt->fetchAll();

    $catStmt = $pdo->prepare("SELECT id, menu_id, name FROM categories WHERE menu_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
    $catStmt->execute($allowedIds);
    $categories = $catStmt->fetchAll();
} else {
    $menus = [];
    $categories = [];
}

$selectedMenuId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedMenuId = (int) ($_POST['menu_id'] ?? 0);
}
if ($selectedMenuId <= 0 && !empty($menus)) {
    $selectedMenuId = (int) $menus[0]['id'];
}

$selectedCategoryId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedCategoryId = (int) ($_POST['category_id'] ?? 0);
}
?>
<div class="card compact-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
        <h2 style="margin:0;">Add Item</h2>
        <a class="btn btn-outline" href="items.php">View All Items</a>
    </div>
    <?php if ($message): ?><p style="color:#059669;"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:#dc2626;"><?= e($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <?php if (!$isSuper): ?>
            <input type="hidden" name="menu_id" value="<?= $selectedMenuId ?>">
        <?php endif; ?>
        <div class="form-grid">
            <?php if ($isSuper): ?>
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
            <?php endif; ?>
            <div>
                <label>Category</label>
                <select name="category_id" id="categorySelect">
                    <option value="">Select category</option>
                    <?php foreach ($categories as $c): ?>
                        <option
                            value="<?= (int) $c['id'] ?>"
                            data-menu-id="<?= (int) $c['menu_id'] ?>"
                            <?= ((int) $c['id'] === $selectedCategoryId) ? 'selected' : '' ?>
                        >
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="__other__">Other (type manually)</option>
                </select>
                <p class="muted" style="margin-top:-8px;font-size:13px;">Only categories of the selected menu are shown.</p>
            </div>
            <div id="manualCategoryWrap" style="display:none;">
                <label>Or add category manually</label>
                <input name="manual_category_name" value="<?= e((string) ($_POST['manual_category_name'] ?? '')) ?>" placeholder="Type new category (optional)">
            </div>
            <div>
                <label>Item Name</label>
                <input name="name" required>
            </div>
            <div>
                <label>Price (ETB)</label>
                <input name="price" type="number" step="0.01" min="0" value="0.00" required>
            </div>
            <div class="full">
                <label>Description</label>
                <textarea name="description"></textarea>
            </div>
            <div>
                <label>Image</label>
                <input type="file" name="image" accept="image/*">
            </div>
            <div>
                <label>Sort Order</label>
                <input name="sort_order" type="number" value="0">
            </div>
            <div class="full">
                <label><input style="width:auto;" type="checkbox" name="is_visible" checked> Visible on menu</label>
            </div>
            <div class="full">
                <button class="btn" type="submit">Save Item</button>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuSelect = document.querySelector('select[name="menu_id"]');
    const hiddenMenuInput = document.querySelector('input[name="menu_id"]');
    const categorySelect = document.getElementById('categorySelect');
    if (!categorySelect) {
        return;
    }

    const originalOptions = Array.from(categorySelect.querySelectorAll('option'));
    const placeholderOption = originalOptions.find(function (option) {
        return option.value === '';
    });
    const manualOption = originalOptions.find(function (option) {
        return option.value === '__other__';
    });
    const manualWrap = document.getElementById('manualCategoryWrap');
    const manualInput = manualWrap ? manualWrap.querySelector('input[name="manual_category_name"]') : null;

    function getSelectedMenuId() {
        if (menuSelect) {
            return menuSelect.value;
        }
        return hiddenMenuInput ? hiddenMenuInput.value : '';
    }

    function filterCategories() {
        const selectedMenuId = String(getSelectedMenuId());
        const currentValue = categorySelect.value;
        categorySelect.innerHTML = '';

        if (placeholderOption) {
            categorySelect.appendChild(placeholderOption.cloneNode(true));
        }

        originalOptions.forEach(function (option) {
            if (option.value === '') {
                return;
            }
            if (option.value === '__other__') {
                return;
            }
            if (String(option.getAttribute('data-menu-id')) === selectedMenuId) {
                categorySelect.appendChild(option.cloneNode(true));
            }
        });
        if (manualOption) {
            categorySelect.appendChild(manualOption.cloneNode(true));
        }

        const existing = categorySelect.querySelector('option[value="' + currentValue + '"]');
        categorySelect.value = existing ? currentValue : '';
        syncManualCategoryField();
    }

    function syncManualCategoryField() {
        if (!manualWrap || !manualInput) {
            return;
        }
        const isOther = categorySelect.value === '__other__';
        manualWrap.style.display = isOther ? 'block' : 'none';
        manualInput.required = isOther;
        if (!isOther) {
            manualInput.value = '';
        }
    }

    filterCategories();
    if (menuSelect) {
        menuSelect.addEventListener('change', filterCategories);
    }
    categorySelect.addEventListener('change', syncManualCategoryField);
    syncManualCategoryField();
});
</script>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
