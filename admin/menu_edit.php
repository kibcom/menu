<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout_top.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: menus.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$menu = $stmt->fetch();

if (!$menu) {
    header('Location: menus.php');
    exit;
}
if (!canAccessMenu((int) $menu['id'])) {
    header('Location: menus.php');
    exit;
}

$message = '';
$error = '';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
}
$isSuper = isSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $error = 'Menu name is required.';
        } else {
            $logoPath = $menu['logo_image'];
            $bannerPath = $menu['banner_image'];

            if (!empty($_POST['remove_logo'])) {
                safeDeleteUpload($logoPath);
                $logoPath = null;
            }
            $newLogo = uploadImage($_FILES['logo_image'] ?? [], 'menus');
            if ($newLogo !== null) {
                safeDeleteUpload($logoPath);
                $logoPath = $newLogo;
            }

            if (!empty($_POST['remove_banner'])) {
                safeDeleteUpload($bannerPath);
                $bannerPath = null;
            }
            $newBanner = uploadImage($_FILES['banner_image'] ?? [], 'menus');
            if ($newBanner !== null) {
                safeDeleteUpload($bannerPath);
                $bannerPath = $newBanner;
            }

            $upd = $pdo->prepare('UPDATE menus SET name = ?, description = ?, logo_image = ?, banner_image = ? WHERE id = ?');
            $upd->execute([$name, $description, $logoPath, $bannerPath, $id]);
            setFlashMessage('Menu updated successfully.');
            header('Location: menu_edit.php?id=' . $id);
            exit;
        }
    }
}

$publicUrl = baseUrl() . '/menu.php?id=' . urlencode($menu['menu_code']);
?>
<div class="card">
    <h2 style="margin-top:0;">Edit menu</h2>
    <p class="muted" style="margin-top:0;">
        <a href="menus.php">&larr; Back to all menus</a>
    </p>
    <?php if ($message): ?><p style="color:#059669;"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:#dc2626;"><?= e($error) ?></p><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">

        <label>Menu name</label>
        <input name="name" value="<?= e($menu['name']) ?>" required>

        <label>Description</label>
        <textarea name="description" rows="4"><?= e((string) ($menu['description'] ?? '')) ?></textarea>

        <label>Menu code (used in QR &amp; URL)</label>
        <input type="text" value="<?= e($menu['menu_code']) ?>" readonly style="background:#f3f4f6;cursor:not-allowed;">
        <p class="muted" style="margin-top:-8px;font-size:13px;">Changing the code would break existing QR codes. Create a new menu if you need a different link.</p>

        <?php if ($isSuper): ?>
            <label>Public menu link</label>
            <input type="text" readonly value="<?= e($publicUrl) ?>" id="publicMenuUrl" style="background:#f0fdf4;border-color:#86efac;">
            <p class="muted" style="margin-top:-8px;font-size:13px;">This is what customers open from the QR code.</p>
        <?php endif; ?>

        <label>Views</label>
        <input type="text" readonly value="<?= (int) $menu['views'] ?>" style="background:#f3f4f6;">

        <label>Logo image</label>
        <?php if (!empty($menu['logo_image'])): ?>
            <p style="margin:6px 0;">
                <img src="<?= e(publicMediaUrl($menu['logo_image'])) ?>" alt="" style="max-height:80px;border-radius:10px;border:1px solid #e5e7eb;">
            </p>
            <label style="font-weight:normal;"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>
        <?php endif; ?>
        <input type="file" name="logo_image" accept="image/*">
        <p class="muted" style="margin-top:-8px;font-size:13px;">Leave empty to keep the current image (unless you check remove).</p>

        <?php if ($isSuper): ?>
            <label>Banner image</label>
            <?php if (!empty($menu['banner_image'])): ?>
                <p style="margin:6px 0;">
                    <img src="<?= e(publicMediaUrl($menu['banner_image'])) ?>" alt="" style="max-height:80px;border-radius:10px;border:1px solid #e5e7eb;">
                </p>
                <label style="font-weight:normal;"><input type="checkbox" name="remove_banner" value="1"> Remove current banner</label>
            <?php endif; ?>
            <input type="file" name="banner_image" accept="image/*">
            <p class="muted" style="margin-top:-8px;font-size:13px;">Banner is optional; the public menu may not display it depending on theme.</p>
        <?php endif; ?>

        <button class="btn" type="submit">Save changes</button>
        <a class="btn btn-outline" href="../menu.php?id=<?= e($menu['menu_code']) ?>" target="_blank">Preview menu</a>
    </form>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
