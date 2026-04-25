<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();
require_once __DIR__ . '/_layout_top.php';

function adsColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$tableName, $columnName]);
    return (int) $stmt->fetchColumn() > 0;
}

function adsEnsureSchema(PDO $pdo): void
{
    if (!adsColumnExists($pdo, 'menus', 'banner_image_docked')) {
        $pdo->exec('ALTER TABLE menus ADD COLUMN banner_image_docked VARCHAR(255) NULL AFTER banner_image');
    }
}

adsEnsureSchema($pdo);

$menus = $pdo->query('SELECT id, name, menu_code, banner_image, banner_image_docked FROM menus ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

$selectedId = (int) ($_POST['menu_id'] ?? $_GET['menu_id'] ?? 0);
$current = null;
if ($selectedId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, menu_code, banner_image, banner_image_docked FROM menus WHERE id = ? LIMIT 1');
    $stmt->execute([$selectedId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$current) {
        $selectedId = 0;
    }
}

$message = '';
$error = '';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_banners') {
    $menuId = (int) ($_POST['menu_id'] ?? 0);
    if ($menuId <= 0) {
        $error = 'Select a menu.';
    } else {
        $stmt = $pdo->prepare('SELECT id, banner_image, banner_image_docked FROM menus WHERE id = ? LIMIT 1');
        $stmt->execute([$menuId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $error = 'Menu not found.';
        } else {
            $bannerPath = $row['banner_image'];
            $dockedPath = $row['banner_image_docked'] ?? null;

            if (!empty($_POST['remove_banner'])) {
                safeDeleteUpload($bannerPath);
                $bannerPath = null;
            }
            $newBanner = uploadImage($_FILES['banner_image'] ?? [], 'menus');
            if ($newBanner !== null) {
                safeDeleteUpload($bannerPath);
                $bannerPath = $newBanner;
            }

            if (!empty($_POST['remove_banner_docked'])) {
                safeDeleteUpload($dockedPath);
                $dockedPath = null;
            }
            $newDocked = uploadImage($_FILES['banner_image_docked'] ?? [], 'menus');
            if ($newDocked !== null) {
                safeDeleteUpload($dockedPath);
                $dockedPath = $newDocked;
            }

            $upd = $pdo->prepare('UPDATE menus SET banner_image = ?, banner_image_docked = ? WHERE id = ?');
            $upd->execute([$bannerPath, $dockedPath, $menuId]);
            setFlashMessage('Banner images saved.');
            header('Location: ads.php?menu_id=' . $menuId);
            exit;
        }
    }
}

if ($error !== '' && (int) ($_POST['menu_id'] ?? 0) > 0) {
    $selectedId = (int) $_POST['menu_id'];
    $stmt = $pdo->prepare('SELECT id, name, menu_code, banner_image, banner_image_docked FROM menus WHERE id = ? LIMIT 1');
    $stmt->execute([$selectedId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
<div class="card">
    <h2 style="margin-top:0;">Ads &amp; banners</h2>
    <p class="muted" style="margin-top:0;">
        Choose a menu and set its public hero images. The <strong>main banner</strong> is the large image at the top of the customer menu.
        If you add a <strong>scrolled banner</strong>, the menu switches to that image when the header compacts instead of squeezing the same photo.
    </p>
    <?php if ($message): ?><p style="color:#059669;"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:#dc2626;"><?= e($error) ?></p><?php endif; ?>

    <form method="get" style="margin-bottom:20px;">
        <label for="pick_menu">Menu</label>
        <select id="pick_menu" name="menu_id" onchange="this.form.submit()">
            <option value="">— Select —</option>
            <?php foreach ($menus as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= $selectedId === (int) $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['name']) ?> (<?= e($m['menu_code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($current): ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_banners">
            <input type="hidden" name="menu_id" value="<?= (int) $current['id'] ?>">

            <h3 style="margin:0 0 10px;font-size:1.05rem;"><?= e($current['name']) ?></h3>
            <p class="muted" style="margin-top:0;font-size:13px;">
                <a href="../menu.php?id=<?= e($current['menu_code']) ?>" target="_blank" rel="noopener">Open public menu</a>
                · <a href="menu_edit.php?id=<?= (int) $current['id'] ?>">Edit menu details</a>
            </p>

            <label>Main banner</label>
            <?php if (!empty($current['banner_image'])): ?>
                <p style="margin:6px 0;">
                    <img src="<?= e(publicMediaUrl($current['banner_image'])) ?>" alt="" style="max-width:100%;max-height:120px;border-radius:10px;border:1px solid #e5e7eb;">
                </p>
                <label style="font-weight:normal;"><input type="checkbox" name="remove_banner" value="1"> Remove current</label>
            <?php endif; ?>
            <input type="file" name="banner_image" accept="image/*">
            <p class="muted" style="margin-top:-8px;font-size:13px;">Wide image (e.g. 1600×600) works well.</p>

            <label style="margin-top:16px;">Scrolled / compact banner (optional)</label>
            <?php if (!empty($current['banner_image_docked'] ?? null)): ?>
                <p style="margin:6px 0;">
                    <img src="<?= e(publicMediaUrl($current['banner_image_docked'])) ?>" alt="" style="max-width:100%;max-height:72px;border-radius:10px;border:1px solid #e5e7eb;">
                </p>
                <label style="font-weight:normal;"><input type="checkbox" name="remove_banner_docked" value="1"> Remove current</label>
            <?php endif; ?>
            <input type="file" name="banner_image_docked" accept="image/*">
            <p class="muted" style="margin-top:-8px;font-size:13px;">Shown when the customer scrolls and the hero bar shrinks—use a short strip or logo so it is not cropped awkwardly.</p>

            <button class="btn" type="submit" style="margin-top:16px;">Save banners</button>
        </form>
    <?php elseif ($selectedId === 0 && !empty($menus)): ?>
        <p class="muted">Select a menu above to manage its banners.</p>
    <?php else: ?>
        <p class="muted">No menus yet. <a href="menu_add.php">Create a menu</a> first.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
