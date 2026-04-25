<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$message = '';
$steps = [];
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
}

function runStep(PDO $pdo, string $label, callable $callback, array &$steps): void
{
    try {
        $callback($pdo);
        $steps[] = ['ok' => true, 'label' => $label];
    } catch (Throwable $e) {
        $steps[] = ['ok' => false, 'label' => $label . ': ' . $e->getMessage()];
    }
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$tableName, $columnName]);
    return (int) $stmt->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    runStep($pdo, 'Create admin_menu_assignments table', function (PDO $pdo): void {
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
    }, $steps);

    runStep($pdo, 'Add admins.role column', function (PDO $pdo): void {
        if (!columnExists($pdo, 'admins', 'role')) {
            $pdo->exec("ALTER TABLE admins ADD COLUMN role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER password");
        }
    }, $steps);

    runStep($pdo, 'Set admin user as super_admin', function (PDO $pdo): void {
        $stmt = $pdo->prepare("UPDATE admins SET role = 'super_admin' WHERE username = 'admin'");
        $stmt->execute();
    }, $steps);

    runStep($pdo, 'Add menus.menu_type column', function (PDO $pdo): void {
        if (!columnExists($pdo, 'menus', 'menu_type')) {
            $pdo->exec("ALTER TABLE menus ADD COLUMN menu_type VARCHAR(30) NOT NULL DEFAULT 'other' AFTER description");
        }
    }, $steps);

    runStep($pdo, 'Add menus.banner_image_docked column', function (PDO $pdo): void {
        if (!columnExists($pdo, 'menus', 'banner_image_docked')) {
            $pdo->exec('ALTER TABLE menus ADD COLUMN banner_image_docked VARCHAR(255) NULL AFTER banner_image');
        }
    }, $steps);

    runStep($pdo, 'Normalize empty roles to admin', function (PDO $pdo): void {
        $stmt = $pdo->prepare("UPDATE admins SET role = 'admin' WHERE role IS NULL OR role = ''");
        $stmt->execute();
    }, $steps);

    // Refresh role in current session after migration.
    try {
        $stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([(int) ($_SESSION['admin_id'] ?? 0)]);
        $role = (string) ($stmt->fetchColumn() ?: '');
        if ($role !== '') {
            $_SESSION['admin_role'] = $role;
        }
    } catch (Throwable $e) {
        // ignore
    }

    $hasError = false;
    foreach ($steps as $step) {
        if ($step['ok'] !== true) {
            $hasError = true;
            break;
        }
    }
    $message = $hasError ? 'Some updates failed. Check details below.' : 'Database updates completed successfully.';
    setFlashMessage($message, $hasError ? 'error' : 'success');
    header('Location: db_update.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Updates</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container" style="max-width:900px;padding-top:30px;">
    <div class="card">
        <h2 style="margin-top:0;">Database Updates</h2>
        <p class="muted">Run this once to apply admin roles and menu assignment tables.</p>
        <?php if ($message): ?>
            <p style="color:<?= strpos($message, 'failed') !== false ? '#dc2626' : '#059669' ?>;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="post">
            <button class="btn" type="submit">Run DB Updates</button>
            <a class="btn btn-outline" href="dashboard.php">Back to Dashboard</a>
        </form>
    </div>

</div>
</body>
</html>
