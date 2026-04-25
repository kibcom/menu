<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
}

function requireAdminLogin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    $adminId = currentAdminId();
    if ($adminId > 0) {
        global $pdo;
        try {
            $stmt = $pdo->prepare('SELECT is_active FROM admins WHERE id = ? LIMIT 1');
            $stmt->execute([$adminId]);
            $active = $stmt->fetchColumn();
            if ($active !== false && (int) $active !== 1) {
                adminLogout();
                header('Location: login.php');
                exit;
            }
        } catch (Throwable $e) {
            // If schema is old (column missing), skip this guard.
        }
    }
}

function adminLogout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function currentAdminId(): int
{
    return (int) ($_SESSION['admin_id'] ?? 0);
}

function currentAdminRole(): string
{
    // Backward compatibility: legacy default admin account is full access.
    if (strtolower((string) ($_SESSION['admin_username'] ?? '')) === 'admin') {
        $_SESSION['admin_role'] = 'super_admin';
        return 'super_admin';
    }

    $role = (string) ($_SESSION['admin_role'] ?? '');
    if ($role === 'super_admin' || $role === 'admin') {
        return $role;
    }

    $adminId = currentAdminId();
    if ($adminId <= 0) {
        return 'admin';
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare('SELECT role, username FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $dbRole = (string) ($row['role'] ?? '');
        $username = strtolower((string) ($row['username'] ?? ''));
        if ($username === 'admin' && $dbRole !== 'super_admin') {
            $dbRole = 'super_admin';
        }
        if ($dbRole === 'super_admin' || $dbRole === 'admin') {
            $_SESSION['admin_role'] = $dbRole;
            $_SESSION['admin_username'] = (string) ($row['username'] ?? '');
            return $dbRole;
        }
    } catch (Throwable $e) {
        // Ignore during pre-migration state (e.g., role column not added yet).
    }

    return 'admin';
}

function isSuperAdmin(): bool
{
    return currentAdminRole() === 'super_admin';
}

function requireSuperAdmin(): void
{
    requireAdminLogin();
    if (!isSuperAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

/** @return int[] */
function assignedMenuIdsForAdmin(int $adminId): array
{
    global $pdo;
    $ids = [];
    try {
        $stmt = $pdo->prepare('SELECT menu_id FROM admin_menu_assignments WHERE admin_id = ?');
        $stmt->execute([$adminId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $ids[] = (int) $id;
        }
    } catch (Throwable $e) {
        // Table may not exist before migration page is run.
    }
    return $ids;
}

/** @return int[] */
function allowedMenuIds(): array
{
    if (isSuperAdmin()) {
        return [];
    }
    $adminId = currentAdminId();
    if ($adminId <= 0) {
        return [];
    }
    return assignedMenuIdsForAdmin($adminId);
}

function canAccessMenu(int $menuId): bool
{
    if ($menuId <= 0) {
        return false;
    }
    if (isSuperAdmin()) {
        return true;
    }
    $allowed = allowedMenuIds();
    return in_array($menuId, $allowed, true);
}
