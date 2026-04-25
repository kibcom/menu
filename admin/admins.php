<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();
require_once __DIR__ . '/_layout_top.php';

function adminsColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$tableName, $columnName]);
    return (int) $stmt->fetchColumn() > 0;
}

function adminsEnsureSchema(PDO $pdo): void
{
    if (!adminsColumnExists($pdo, 'admins', 'role')) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER password");
    }
    if (!adminsColumnExists($pdo, 'admins', 'is_active')) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
    }

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

adminsEnsureSchema($pdo);

$message = '';
$messageType = 'success';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
    $messageType = (string) ($flash['type'] ?? 'success');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $menuId = (int) ($_POST['menu_id'] ?? 0);

        if ($name === '' || $username === '' || $password === '') {
            setFlashMessage('Name, username and password are required.', 'error');
            header('Location: admins.php');
            exit;
        }

        $existsStmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
        $existsStmt->execute([$username]);
        if ($existsStmt->fetchColumn()) {
            setFlashMessage('Username already exists.', 'error');
            header('Location: admins.php');
            exit;
        }

        if ($menuId > 0) {
            $takenStmt = $pdo->prepare('SELECT admin_id FROM admin_menu_assignments WHERE menu_id = ? LIMIT 1');
            $takenStmt->execute([$menuId]);
            if ($takenStmt->fetchColumn()) {
                setFlashMessage('This menu is already assigned to another admin.', 'error');
                header('Location: admins.php');
                exit;
            }
        }

        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO admins (name, username, password, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
            $ins->execute([$name, $username, $hash, 'admin']);
            $adminId = (int) $pdo->lastInsertId();

            if ($menuId > 0) {
                $assignStmt = $pdo->prepare('INSERT INTO admin_menu_assignments (admin_id, menu_id, created_at) VALUES (?, ?, NOW())');
                $assignStmt->execute([$adminId, $menuId]);
            }

            $pdo->commit();
            setFlashMessage('Admin account created and menu assigned.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlashMessage('Failed to create admin account.', 'error');
        }
        header('Location: admins.php');
        exit;
    }

    if ($action === 'update_menu') {
        $adminId = (int) ($_POST['admin_id'] ?? 0);
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        if ($adminId > 0) {
            $roleStmt = $pdo->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
            $roleStmt->execute([$adminId]);
            $role = (string) ($roleStmt->fetchColumn() ?: '');
            if ($role === 'admin') {
                if ($menuId > 0) {
                    $takenStmt = $pdo->prepare('SELECT admin_id FROM admin_menu_assignments WHERE menu_id = ? AND admin_id <> ? LIMIT 1');
                    $takenStmt->execute([$menuId, $adminId]);
                    if ($takenStmt->fetchColumn()) {
                        setFlashMessage('Selected menu is already assigned to another admin.', 'error');
                        header('Location: admins.php');
                        exit;
                    }
                }

                $pdo->beginTransaction();
                try {
                    $pdo->prepare('DELETE FROM admin_menu_assignments WHERE admin_id = ?')->execute([$adminId]);
                    if ($menuId > 0) {
                        $assignStmt = $pdo->prepare('INSERT INTO admin_menu_assignments (admin_id, menu_id, created_at) VALUES (?, ?, NOW())');
                        $assignStmt->execute([$adminId, $menuId]);
                    }
                    $pdo->commit();
                    setFlashMessage('Assigned menu updated.');
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    setFlashMessage('Failed to update menu assignment.', 'error');
                }
            }
        }
        header('Location: admins.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $adminId = (int) ($_POST['admin_id'] ?? 0);
        if ($adminId > 0 && $adminId !== currentAdminId()) {
            $rowStmt = $pdo->prepare('SELECT role, is_active FROM admins WHERE id = ? LIMIT 1');
            $rowStmt->execute([$adminId]);
            $row = $rowStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            if (($row['role'] ?? '') === 'admin') {
                $next = ((int) ($row['is_active'] ?? 1) === 1) ? 0 : 1;
                $upd = $pdo->prepare('UPDATE admins SET is_active = ? WHERE id = ?');
                $upd->execute([$next, $adminId]);
                setFlashMessage($next === 1 ? 'Admin activated.' : 'Admin deactivated.');
            }
        }
        header('Location: admins.php');
        exit;
    }
}

$menus = $pdo->query('SELECT id, name FROM menus ORDER BY name ASC')->fetchAll();
$admins = $pdo->query("SELECT id, name, username, role, is_active, created_at FROM admins ORDER BY role DESC, id ASC")->fetchAll();

$assignRows = $pdo->query('
    SELECT a.admin_id, a.menu_id, m.name AS menu_name
    FROM admin_menu_assignments a
    JOIN menus m ON m.id = a.menu_id
')->fetchAll();
$assignedByAdmin = [];
$assignedMenuIds = [];
foreach ($assignRows as $row) {
    $aid = (int) $row['admin_id'];
    $mid = (int) $row['menu_id'];
    $assignedByAdmin[$aid] = ['id' => $mid, 'name' => (string) ($row['menu_name'] ?? '')];
    $assignedMenuIds[$mid] = true;
}

$availableMenusForCreate = array_values(array_filter($menus, static function (array $menu) use ($assignedMenuIds): bool {
    return !isset($assignedMenuIds[(int) $menu['id']]);
}));
?>
<div class="card">
    <style>
        .admins-modern {
            display: grid;
            gap: 12px;
        }
        .admin-row {
            border: 1px solid #dbe5f2;
            border-radius: 14px;
            padding: 12px;
            background: #fff;
            display: grid;
            gap: 8px;
        }
        .admin-row__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .admin-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            color: #64748b;
            font-size: 12px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            color: #334155;
        }
        .pill--active {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }
        .pill--inactive {
            border-color: #fecaca;
            background: #fff1f2;
            color: #b91c1c;
        }
        .admin-row__edit {
            display: none;
            margin-top: 6px;
            border-top: 1px dashed #dbe3ef;
            padding-top: 8px;
        }
        .admin-row__edit.is-open {
            display: block;
        }
    </style>
    <h2 style="margin-top:0;">Create Admin Account</h2>
    <?php if ($message): ?><p style="color:<?= $messageType === 'error' ? '#dc2626' : '#059669' ?>;"><?= e($message) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <div class="responsive-two-col">
            <div>
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div>
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
        </div>
        <div class="responsive-two-col">
            <div>
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>Assign Menu (optional)</label>
                <select name="menu_id">
                    <option value="">No menu now</option>
                    <?php foreach ($availableMenusForCreate as $menu): ?>
                        <option value="<?= (int) $menu['id'] ?>"><?= e($menu['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <p class="muted" style="margin-top:-8px;font-size:13px;">A menu can be assigned to one admin only.</p>
        <button class="btn" type="submit">Create Admin</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;">Admin Accounts</h3>
    <div class="admins-modern">
        <?php foreach ($admins as $admin): ?>
            <?php
            $adminId = (int) $admin['id'];
            $isRowSuper = (string) $admin['role'] === 'super_admin';
            $isActive = (int) ($admin['is_active'] ?? 1) === 1;
            $assignedMenu = $assignedByAdmin[$adminId]['name'] ?? '';
            $assignedMenuId = (int) ($assignedByAdmin[$adminId]['id'] ?? 0);
            ?>
            <article class="admin-row">
                <div class="admin-row__top">
                    <div>
                        <h4 style="margin:0;"><?= e($admin['name']) ?></h4>
                        <div class="admin-meta">
                            <span>@<?= e($admin['username']) ?></span>
                            <span class="pill"><?= $isRowSuper ? 'Super Admin' : 'Admin' ?></span>
                            <span class="pill <?= $isActive ? 'pill--active' : 'pill--inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                        </div>
                    </div>
                    <?php if (!$isRowSuper): ?>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-outline btn-compact js-edit-menu-toggle">Edit Menu</button>
                            <form method="post">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="admin_id" value="<?= $adminId ?>">
                                <button class="btn btn-outline btn-compact" type="submit"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <p style="margin:0;font-size:13px;color:#334155;">
                    <strong>Assigned Menu:</strong>
                    <?php if ($isRowSuper): ?>
                        All menus
                    <?php elseif ($assignedMenu !== ''): ?>
                        <?= e($assignedMenu) ?>
                    <?php else: ?>
                        <span class="muted">Not assigned</span>
                    <?php endif; ?>
                </p>
                <?php if (!$isRowSuper): ?>
                    <form method="post" class="admin-row__edit">
                        <input type="hidden" name="action" value="update_menu">
                        <input type="hidden" name="admin_id" value="<?= $adminId ?>">
                        <label>Assign Menu (dropdown)</label>
                        <select name="menu_id">
                            <option value="">No menu</option>
                            <?php foreach ($menus as $menu): ?>
                                <?php
                                $mid = (int) $menu['id'];
                                $menuTakenByAnother = isset($assignedMenuIds[$mid]) && $assignedMenuId !== $mid;
                                if ($menuTakenByAnother) {
                                    continue;
                                }
                                ?>
                                <option value="<?= $mid ?>" <?= $assignedMenuId === $mid ? 'selected' : '' ?>><?= e($menu['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button class="btn btn-compact" type="submit">Save</button>
                            <button type="button" class="btn btn-outline btn-compact js-edit-menu-cancel">Cancel</button>
                        </div>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleButtons = document.querySelectorAll('.js-edit-menu-toggle');
    toggleButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row = this.closest('.admin-row');
            if (!row) {
                return;
            }
            const editForm = row.querySelector('.admin-row__edit');
            if (editForm) {
                editForm.classList.add('is-open');
            }
        });
    });

    const cancelButtons = document.querySelectorAll('.js-edit-menu-cancel');
    cancelButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const editForm = this.closest('.admin-row__edit');
            if (editForm) {
                editForm.classList.remove('is-open');
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
