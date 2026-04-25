<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();
require_once __DIR__ . '/_layout_top.php';

$message = '';
$error = '';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
}
$menuType = 'other';

function menuAddColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$tableName, $columnName]);
    return (int) $stmt->fetchColumn() > 0;
}

function menuAddTableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$tableName]);
    return (int) $stmt->fetchColumn() > 0;
}

function menuAddSlugify(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'menu';
    }
    return substr($slug, 0, 48);
}

function menuAddUniqueMenuCode(PDO $pdo, string $menuName): string
{
    $base = menuAddSlugify($menuName);
    $candidate = $base;
    $checkStmt = $pdo->prepare('SELECT id FROM menus WHERE menu_code = ? LIMIT 1');

    $checkStmt->execute([$candidate]);
    if (!$checkStmt->fetchColumn()) {
        return $candidate;
    }

    for ($i = 0; $i < 50; $i++) {
        $suffix = '-' . randomSlug(4);
        $candidate = substr($base, 0, max(1, 60 - strlen($suffix))) . $suffix;
        $checkStmt->execute([$candidate]);
        if (!$checkStmt->fetchColumn()) {
            return $candidate;
        }
    }

    return substr($base, 0, 52) . '-' . randomSlug(7);
}

$menuTypeOptions = [
    'hotel' => 'Hotel Menu',
    'cafe' => 'Cafe Menu',
    'restaurant' => 'Restaurant Menu',
    'bar' => 'Bar Menu',
    'bakery' => 'Bakery Menu',
    'other' => 'Other',
];

$hasMenuTypeColumn = menuAddColumnExists($pdo, 'menus', 'menu_type');
if ($hasMenuTypeColumn) {
    $menuRows = $pdo->query('
        SELECT m.id, m.name, m.menu_code, m.menu_type, m.views, q.qr_path
        FROM menus m
        LEFT JOIN qr_codes q ON q.menu_id = m.id
        ORDER BY m.id DESC
    ')->fetchAll();
} else {
    $menuRows = $pdo->query("
        SELECT m.id, m.name, m.menu_code, 'other' AS menu_type, m.views, q.qr_path
        FROM menus m
        LEFT JOIN qr_codes q ON q.menu_id = m.id
        ORDER BY m.id DESC
    ")->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $adminName = trim((string) ($_POST['admin_name'] ?? ''));
    $adminUsername = trim((string) ($_POST['admin_username'] ?? ''));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');
    $menuType = trim((string) ($_POST['menu_type'] ?? 'other'));
    $otherMenuType = trim((string) ($_POST['other_menu_type'] ?? ''));
    if (!array_key_exists($menuType, $menuTypeOptions)) {
        $menuType = 'other';
    }
    if ($menuType === 'other' && $otherMenuType !== '') {
        $menuType = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $otherMenuType));
        $menuType = trim($menuType, '_');
        $menuType = substr($menuType, 0, 30);
        if ($menuType === '') {
            $menuType = 'other';
        }
    }

    if ($name === '') {
        $error = 'Menu name is required.';
    } elseif ($adminName === '' || $adminUsername === '' || $adminPassword === '') {
        $error = 'Admin full name, username, and password are required.';
    } else {
        $existsStmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
        $existsStmt->execute([$adminUsername]);
        if ($existsStmt->fetchColumn()) {
            $error = 'Admin username already exists.';
        } else {
            $logo = uploadImage($_FILES['logo_image'] ?? [], 'menus');
            $banner = uploadImage($_FILES['banner_image'] ?? [], 'menus');
            $menuCode = menuAddUniqueMenuCode($pdo, $name);

            // Auto-upgrade requirements.
            if (!menuAddColumnExists($pdo, 'menus', 'menu_type')) {
                $pdo->exec("ALTER TABLE menus ADD COLUMN menu_type VARCHAR(30) NOT NULL DEFAULT 'other' AFTER description");
            }
            if (!menuAddColumnExists($pdo, 'admins', 'role')) {
                $pdo->exec("ALTER TABLE admins ADD COLUMN role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER password");
            }
            if (!menuAddTableExists($pdo, 'admin_menu_assignments')) {
                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS admin_menu_assignments (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        admin_id INT UNSIGNED NOT NULL,
                        menu_id INT UNSIGNED NOT NULL,
                        created_at DATETIME NOT NULL,
                        UNIQUE KEY uniq_admin_menu (admin_id, menu_id),
                        CONSTRAINT fk_admin_menu_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                        CONSTRAINT fk_admin_menu_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO menus (name, menu_code, description, menu_type, logo_image, banner_image, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$name, $menuCode, $description, $menuType, $logo, $banner]);
                $menuId = (int) $pdo->lastInsertId();

                $qrPath = generateMenuQr($menuCode);
                if ($qrPath) {
                    $pdo->prepare('INSERT INTO qr_codes (menu_id, qr_path, qr_url, created_at) VALUES (?, ?, ?, NOW())')
                        ->execute([$menuId, $qrPath, baseUrl() . '/menu.php?id=' . $menuCode]);
                }

                $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
                $createAdminStmt = $pdo->prepare('INSERT INTO admins (name, username, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
                $createAdminStmt->execute([$adminName, $adminUsername, $passwordHash, 'admin']);
                $newAdminId = (int) $pdo->lastInsertId();

                $assignStmt = $pdo->prepare('INSERT INTO admin_menu_assignments (admin_id, menu_id, created_at) VALUES (?, ?, NOW())');
                $assignStmt->execute([$newAdminId, $menuId]);

                $pdo->commit();
                setFlashMessage('Menu and admin account created successfully.');
                header('Location: menu_add.php');
                exit;
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Failed to create menu/admin. Please try again.';
            }
        }
    }
}
?>
<style>
    .menu-add-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(320px, 0.9fr);
        gap: 12px;
        align-items: start;
        width: 100%;
    }
    .menu-add-side-card {
        position: sticky;
        top: 14px;
    }
    @media (max-width: 980px) {
        .menu-add-layout {
            grid-template-columns: 1fr;
        }
        .menu-add-side-card {
            position: static;
        }
    }
</style>
<?php if ($message): ?><p style="color:#059669;margin:0 0 10px;"><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p style="color:#dc2626;margin:0 0 10px;"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <div class="menu-add-layout">
        <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                <h2 style="margin:0;">Create Menu</h2>
                <a class="btn btn-outline" href="menus.php">View My Menu</a>
            </div>
            <div class="responsive-two-col">
                <div>
                    <label>Menu Name</label>
                    <input name="name" value="<?= e((string) ($_POST['name'] ?? '')) ?>" required>
                </div>
                <div>
                    <label>This menu is for</label>
                    <select name="menu_type" required>
                        <?php foreach ($menuTypeOptions as $value => $label): ?>
                            <?php $selectedType = array_key_exists($menuType, $menuTypeOptions) ? $menuType : 'other'; ?>
                            <option value="<?= e($value) ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div id="otherMenuTypeWrap" style="display:none;">
                <label>Other menu type</label>
                <input name="other_menu_type" id="otherMenuTypeInput" value="<?= e((string) ($_POST['other_menu_type'] ?? '')) ?>" placeholder="Example: Lounge, Food Truck, Resort">
            </div>

            <label>Description</label>
            <textarea name="description"><?= e((string) ($_POST['description'] ?? '')) ?></textarea>

            <div class="responsive-two-col">
                <div>
                    <label>Logo Image</label>
                    <input type="file" name="logo_image" accept="image/*">
                </div>
                <div>
                    <label>Banner Image</label>
                    <input type="file" name="banner_image" accept="image/*">
                </div>
            </div>
        </div>

        <div class="card menu-add-side-card">
            <h4 style="margin:0 0 8px;">Create Admin For This Menu</h4>
            <label>Admin Full Name</label>
            <input name="admin_name" value="<?= e((string) ($_POST['admin_name'] ?? '')) ?>" required>

            <label>Admin Username</label>
            <input name="admin_username" value="<?= e((string) ($_POST['admin_username'] ?? '')) ?>" required>

            <label>Admin Password</label>
            <input name="admin_password" type="text" required>
            <p class="muted" style="margin-top:-8px;font-size:13px;">The new admin will be assigned only to this newly created menu.</p>

            <button class="btn" type="submit">Create Menu + Admin + Generate QR</button>
        </div>
    </div>
</form>
<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
        <h3 style="margin:0;">Recent Menus</h3>
        <a class="btn btn-outline" href="menus.php">See more</a>
    </div>
    <div class="responsive-two-col" style="margin-top:10px;">
        <div>
            <label style="margin:0;">Filter by name / code</label>
            <input id="menuQuickFilter" type="text" placeholder="Search menu">
        </div>
        <div>
            <label style="margin:0;">Filter by type</label>
            <select id="menuTypeFilter">
                <option value="">All types</option>
                <?php foreach ($menuTypeOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (empty($menuRows)): ?>
        <p class="muted" style="margin-top:10px;">No menus available yet.</p>
    <?php else: ?>
        <div id="menuQuickList" style="display:grid;gap:8px;margin-top:8px;">
            <?php foreach ($menuRows as $row): ?>
                <article
                    class="menu-quick-row"
                    data-name="<?= e(strtolower((string) $row['name'])) ?>"
                    data-code="<?= e(strtolower((string) $row['menu_code'])) ?>"
                    data-type="<?= e(strtolower((string) $row['menu_type'])) ?>"
                    style="border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;"
                >
                    <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1 1 340px;">
                        <?php if (!empty($row['qr_path'])): ?>
                            <img
                                src="<?= e(publicMediaUrl($row['qr_path'])) ?>"
                                alt="QR for <?= e($row['name']) ?>"
                                style="width:46px;height:46px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;object-fit:cover;flex-shrink:0;"
                            >
                        <?php else: ?>
                            <div style="width:46px;height:46px;border-radius:8px;border:1px dashed #d1d5db;background:#f8fafc;color:#94a3b8;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">No QR</div>
                        <?php endif; ?>
                        <div style="min-width:0;">
                        <p style="margin:0;font-weight:700;color:#0f172a;"><?= e($row['name']) ?></p>
                        <p class="muted" style="margin:4px 0 0;font-size:12px;">
                            Code: <?= e($row['menu_code']) ?> |
                            Type: <?= e(ucwords(str_replace('_', ' ', (string) $row['menu_type']))) ?> |
                            Views: <?= (int) $row['views'] ?>
                        </p>
                        </div>
                    </div>
                    <a class="btn btn-outline btn-compact" href="menu_edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                </article>
            <?php endforeach; ?>
        </div>
        <p id="menuQuickEmpty" class="muted" style="display:none;margin-top:10px;">No matching menus found.</p>
        <p class="muted" style="margin-top:10px;font-size:13px;">
            Showing first <span id="menuQuickLimitCount">6</span> matches here.
            Click <a href="menus.php">See more</a> to view full list.
        </p>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.querySelector('select[name="menu_type"]');
    const otherWrap = document.getElementById('otherMenuTypeWrap');
    const otherInput = document.getElementById('otherMenuTypeInput');
    if (!typeSelect || !otherWrap || !otherInput) {
        return;
    }

    function syncOtherField() {
        const isOther = typeSelect.value === 'other';
        otherWrap.style.display = isOther ? 'block' : 'none';
        otherInput.required = isOther;
        if (!isOther) {
            otherInput.value = '';
        }
    }

    syncOtherField();
    typeSelect.addEventListener('change', syncOtherField);

    const quickFilter = document.getElementById('menuQuickFilter');
    const typeFilter = document.getElementById('menuTypeFilter');
    const list = document.getElementById('menuQuickList');
    if (!quickFilter || !typeFilter || !list) {
        return;
    }

    const emptyState = document.getElementById('menuQuickEmpty');
    const limitLabel = document.getElementById('menuQuickLimitCount');
    const limit = 6;
    const rows = Array.from(list.querySelectorAll('.menu-quick-row'));

    function applyQuickFilters() {
        const keyword = quickFilter.value.trim().toLowerCase();
        const type = typeFilter.value.trim().toLowerCase();
        let shown = 0;
        let totalMatch = 0;

        rows.forEach(function (row) {
            const name = row.getAttribute('data-name') || '';
            const code = row.getAttribute('data-code') || '';
            const menuType = row.getAttribute('data-type') || '';
            const keywordMatch = keyword === '' || name.includes(keyword) || code.includes(keyword);
            const typeMatch = type === '' || menuType === type;
            const match = keywordMatch && typeMatch;

            if (!match) {
                row.style.display = 'none';
                return;
            }

            totalMatch++;
            if (shown < limit) {
                row.style.display = 'flex';
                shown++;
            } else {
                row.style.display = 'none';
            }
        });

        if (emptyState) {
            emptyState.style.display = totalMatch === 0 ? 'block' : 'none';
        }
        if (limitLabel) {
            limitLabel.textContent = String(Math.min(limit, totalMatch));
        }
    }

    applyQuickFilters();
    quickFilter.addEventListener('input', applyQuickFilters);
    typeFilter.addEventListener('change', applyQuickFilters);
});
</script>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
