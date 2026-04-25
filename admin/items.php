<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout_top.php';

$message = '';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
}
$allowedIds = allowedMenuIds();
$isSuper = isSuperAdmin();

if ($isSuper) {
    $menus = $pdo->query('SELECT id, name FROM menus ORDER BY name ASC')->fetchAll();
} elseif (!empty($allowedIds)) {
    $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
    $menuStmt = $pdo->prepare("SELECT id, name FROM menus WHERE id IN ($placeholders) ORDER BY name ASC");
    $menuStmt->execute($allowedIds);
    $menus = $menuStmt->fetchAll();
} else {
    $menus = [];
}

$selectedMenuId = (int) ($_GET['menu_id'] ?? 0);
if ($selectedMenuId > 0 && !canAccessMenu($selectedMenuId)) {
    $selectedMenuId = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT menu_id FROM items WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $menuId = (int) ($stmt->fetchColumn() ?: 0);
            if (canAccessMenu($menuId)) {
                $pdo->prepare('UPDATE items SET is_visible = 1 - is_visible WHERE id = ?')->execute([$id]);
                setFlashMessage('Visibility changed.');
                header('Location: items.php?menu_id=' . $menuId);
                exit;
            }
        }
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT menu_id FROM items WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $menuId = (int) ($stmt->fetchColumn() ?: 0);
            if (canAccessMenu($menuId)) {
                $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
                setFlashMessage('Item deleted.');
                header('Location: items.php?menu_id=' . $menuId);
                exit;
            }
        }
    }
}

$rows = [];
if ($selectedMenuId > 0) {
    $rowStmt = $pdo->prepare('
        SELECT i.*, m.name AS menu_name, c.name AS category_name
        FROM items i
        JOIN menus m ON m.id = i.menu_id
        JOIN categories c ON c.id = i.category_id
        WHERE i.menu_id = ?
        ORDER BY i.id DESC
    ');
    $rowStmt->execute([$selectedMenuId]);
    $rows = $rowStmt->fetchAll();
}

$totalItems = count($rows);
$visibleItems = count(array_filter($rows, static fn ($item) => (int) $item['is_visible'] === 1));
$hiddenItems = $totalItems - $visibleItems;
$avgPrice = $totalItems > 0
    ? (array_sum(array_map(static fn ($item) => (float) $item['price'], $rows)) / $totalItems)
    : 0.0;
?>
<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
        <h3 style="margin:0;">Items by Menu</h3>
        <a class="btn btn-outline" href="item_add.php">+ Add New Item</a>
    </div>
    <?php if ($message): ?><p style="color:#059669;"><?= e($message) ?></p><?php endif; ?>
    <form id="itemsMenuFilterForm" method="get" style="display:grid;grid-template-columns:minmax(0,1fr);gap:8px;align-items:end;margin-bottom:10px;">
        <div>
            <label>Select Menu</label>
            <select name="menu_id" id="itemsMenuFilterSelect" required>
                <option value="">Choose menu</option>
                <?php foreach ($menus as $menu): ?>
                    <option value="<?= (int) $menu['id'] ?>" <?= $selectedMenuId === (int) $menu['id'] ? 'selected' : '' ?>>
                        <?= e($menu['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($selectedMenuId > 0): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:12px;">
        <div class="card" style="margin:0;padding:10px 12px;">
            <strong><?= $totalItems ?></strong>
            <div class="muted">Total Items</div>
        </div>
        <div class="card" style="margin:0;padding:10px 12px;">
            <strong><?= $visibleItems ?></strong>
            <div class="muted">Visible</div>
        </div>
        <div class="card" style="margin:0;padding:10px 12px;">
            <strong><?= $hiddenItems ?></strong>
            <div class="muted">Hidden</div>
        </div>
        <div class="card" style="margin:0;padding:10px 12px;">
            <strong><?= e(formatPriceEtb($avgPrice)) ?></strong>
            <div class="muted">Average Price</div>
        </div>
    </div>

    <div class="filter-row">
        <button class="chip active js-item-filter" data-state="all" type="button">All</button>
        <button class="chip js-item-filter" data-state="visible" type="button">Visible</button>
        <button class="chip js-item-filter" data-state="hidden" type="button">Hidden</button>
    </div>
    <table>
            <thead><tr><th>Picture</th><th>Item</th><th>Menu</th><th>Category</th><th>Price (ETB)</th><th>Visible</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $thumbUrl = publicMediaUrl($row['image'] ?? null); ?>
                <tr class="<?= (int) $row['is_visible'] === 1 ? '' : 'row-hidden' ?>" data-visible="<?= (int) $row['is_visible'] === 1 ? 'visible' : 'hidden' ?>">
                    <td data-label="Picture">
                        <?php if (!empty($row['image'])): ?>
                            <img class="admin-item-thumb" src="<?= e($thumbUrl) ?>" alt="" width="56" height="56" loading="lazy" decoding="async">
                        <?php else: ?>
                            <span class="muted" style="display:inline-block;min-width:56px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Item"><?= e($row['name']) ?></td>
                    <td data-label="Menu"><?= e($row['menu_name']) ?></td>
                    <td data-label="Category"><?= e($row['category_name']) ?></td>
                    <td data-label="Price (ETB)"><?= e(formatPriceEtb($row['price'])) ?></td>
                    <td data-label="Visible">
                        <?php if ((int) $row['is_visible'] === 1): ?>
                            <span class="status-pill status-visible">Visible</span>
                        <?php else: ?>
                            <span class="status-pill status-hidden">Hidden</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Actions" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <a class="btn btn-outline btn-compact btn-edit" href="item_edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                        <form method="post">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="menu_id" value="<?= $selectedMenuId ?>">
                            <button class="btn btn-outline btn-compact <?= (int) $row['is_visible'] === 1 ? 'btn-hide' : 'btn-show' ?>" type="submit"><?= (int) $row['is_visible'] === 1 ? 'Hide' : 'Show' ?></button>
                        </form>
                        <form method="post" onsubmit="return confirm('Delete item?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="menu_id" value="<?= $selectedMenuId ?>">
                            <button class="btn btn-outline btn-compact btn-delete" type="submit">Del</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="muted">Select a menu to display its items and statistics.</p>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuFilterForm = document.getElementById('itemsMenuFilterForm');
    const menuFilterSelect = document.getElementById('itemsMenuFilterSelect');
    if (menuFilterForm && menuFilterSelect) {
        menuFilterSelect.addEventListener('change', function () {
            menuFilterForm.submit();
        });
    }

    const filterButtons = document.querySelectorAll('.js-item-filter');
    const rows = document.querySelectorAll('tbody tr[data-visible]');

    function applyFilter(state) {
        rows.forEach(function (row) {
            const rowState = row.dataset.visible || 'hidden';
            row.style.display = state === 'all' || state === rowState ? '' : 'none';
        });
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterButtons.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            applyFilter(this.dataset.state || 'all');
        });
    });
});
</script>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
