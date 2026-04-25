<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        if (isset($admin['is_active']) && (int) $admin['is_active'] !== 1) {
            $error = 'Your account is deactivated. Contact super admin.';
        } else {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_username'] = $admin['username'] ?? '';
        $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
        header('Location: dashboard.php');
        exit;
        }
    }

    if ($error === '') {
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width:480px;padding-top:80px;">
        <div class="card">
            <h2 style="margin-top:0;">Admin Login</h2>
            <?php if ($error): ?><p style="color:#dc2626;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <form method="post">
                <label>Username</label>
                <input type="text" name="username" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button class="btn" type="submit">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
