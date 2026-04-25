<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout_top.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: items.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) {
    header('Location: items.php');
    exit;
}

$menuId = (int) $item['menu_id'];
if (!canAccessMenu($menuId)) {
    header('Location: items.php');
    exit;
}

$menuStmt = $pdo->prepare('SELECT id, name FROM menus WHERE id = ? LIMIT 1');
$menuStmt->execute([$menuId]);
$menu = $menuStmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => $menuId, 'name' => ''];

$catStmt = $pdo->prepare('SELECT id, name FROM categories WHERE menu_id = ? ORDER BY sort_order ASC, id ASC');
$catStmt->execute([$menuId]);
$categories = $catStmt->fetchAll();

$message = '';
$error = '';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $order = (int) ($_POST['sort_order'] ?? 0);
    $isVisible = isset($_POST['is_visible']) ? 1 : 0;

    $catOk = $pdo->prepare('SELECT id FROM categories WHERE id = ? AND menu_id = ? LIMIT 1');
    $catOk->execute([$categoryId, $menuId]);
    if (!(int) ($catOk->fetchColumn() ?: 0)) {
        $error = 'Choose a category that belongs to this menu.';
    } elseif ($name === '') {
        $error = 'Item name is required.';
    } else {
        $imagePath = $item['image'];
        if (!empty($_POST['remove_image'])) {
            safeDeleteUpload(is_string($imagePath) ? $imagePath : null);
            $imagePath = null;
        }
        $newImage = uploadImage($_FILES['image'] ?? [], 'items');
        if ($newImage !== null) {
            safeDeleteUpload(is_string($item['image']) ? $item['image'] : null);
            $imagePath = $newImage;
        }

        $upd = $pdo->prepare(
            'UPDATE items SET category_id = ?, name = ?, description = ?, price = ?, image = ?, sort_order = ?, is_visible = ? WHERE id = ? AND menu_id = ?'
        );
        $upd->execute([$categoryId, $name, $description, $price, $imagePath, $order, $isVisible, $id, $menuId]);
        setFlashMessage('Item updated successfully.');
        header('Location: items.php?menu_id=' . $menuId);
        exit;
    }

    $item['category_id'] = $categoryId;
    $item['name'] = $name;
    $item['description'] = $description;
    $item['price'] = $price;
    $item['sort_order'] = $order;
    $item['is_visible'] = $isVisible;
}

$itemImg = publicMediaUrl($item['image'] ?? null);
?>
<div class="card compact-card">
    <h2 style="margin-top:0;">Edit item</h2>
    <p class="muted" style="margin-top:0;">
        <a href="items.php?menu_id=<?= (int) $menuId ?>">&larr; Back to items</a>
        · Menu: <strong><?= e((string) ($menu['name'] ?? '')) ?></strong>
    </p>
    <?php if ($message): ?><p style="color:#059669;"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:#dc2626;"><?= e($error) ?></p><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">

        <div class="form-grid">
            <div>
                <label>Category</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $item['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Item name</label>
                <input name="name" value="<?= e((string) $item['name']) ?>" required>
            </div>
            <div>
                <label>Price (ETB)</label>
                <input name="price" type="number" step="0.01" min="0" value="<?= e(formatPrice($item['price'])) ?>" required>
            </div>
            <div class="full">
                <label>Description</label>
                <textarea name="description" rows="4"><?= e((string) ($item['description'] ?? '')) ?></textarea>
            </div>
            <div class="full">
                <label>Current image</label>
                <?php if (!empty($item['image'])): ?>
                    <p style="margin:6px 0;">
                        <img src="<?= e($itemImg) ?>" alt="" class="admin-item-edit-preview">
                    </p>
                    <label style="font-weight:normal;"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                <?php else: ?>
                    <p class="muted" style="margin:6px 0;">No image uploaded.</p>
                <?php endif; ?>
                <label style="margin-top:10px;display:block;">Replace image</label>
                <input type="file" name="image" accept="image/*">
                <p class="muted" style="margin-top:-8px;font-size:13px;">Leave empty to keep the current image.</p>
            </div>
            <div>
                <label>Sort order</label>
                <input name="sort_order" type="number" value="<?= (int) ($item['sort_order'] ?? 0) ?>">
            </div>
            <div class="full">
                <label><input style="width:auto;" type="checkbox" name="is_visible" value="1" <?= (int) ($item['is_visible'] ?? 0) === 1 ? 'checked' : '' ?>> Visible on public menu</label>
            </div>
            <div class="full" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn" type="submit">Save changes</button>
                <a class="btn btn-outline" href="items.php?menu_id=<?= (int) $menuId ?>">Cancel</a>
            </div>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
