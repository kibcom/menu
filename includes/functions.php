<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/phpqrcode/qrlib.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function setFlashMessage(string $message, string $type = 'success'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['flash_message'] = [
        'text' => $message,
        'type' => $type,
    ];
}

function consumeFlashMessage(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $flash = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return is_array($flash) ? $flash : null;
}

function normalizeFsPath(string $path): string
{
    return str_replace('\\', '/', $path);
}

/** Whether $path starts with $prefix (case-insensitive on Windows). */
function fsPathHasPrefix(string $path, string $prefix): bool
{
    if ($prefix === '') {
        return false;
    }
    if (PHP_OS_FAMILY === 'Windows') {
        return strlen($path) >= strlen($prefix)
            && strcasecmp(substr($path, 0, strlen($prefix)), $prefix) === 0;
    }
    return strpos($path, $prefix) === 0;
}

/**
 * URL path from web root to app root, using DOCUMENT_ROOT + a filesystem path.
 * Uses the path as reported by the server (not realpath) so symlinks under public_html still match.
 */
function webRootPathFromFsUnderDocroot(string $absolutePath): ?string
{
    $doc = rtrim(normalizeFsPath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $abs = normalizeFsPath($absolutePath);
    if ($doc === '' || $abs === '' || !fsPathHasPrefix($abs, $doc)) {
        return null;
    }

    $rel = substr($abs, strlen($doc));
    $rel = '/' . ltrim($rel, '/');
    $dir = dirname($rel);
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $path = '';
    } else {
        $path = (string) preg_replace('#/admin$#', '', $dir);
        $path = rtrim($path, '/');
    }

    return $path;
}

function webRootPathFromEntryScript(): ?string
{
    $scriptRaw = (string) ($_SERVER['SCRIPT_FILENAME'] ?? '');
    if ($scriptRaw === '') {
        return null;
    }

    return webRootPathFromFsUnderDocroot($scriptRaw);
}

/** App root on disk (folder that contains menu.php, admin/, includes/). */
function webRootPathFromInstallDir(): ?string
{
    $appRoot = dirname(__DIR__);
    if ($appRoot === '' || $appRoot === '.') {
        return null;
    }

    return webRootPathFromFsUnderDocroot($appRoot);
}

function baseUrl(): string
{
    if (defined('APP_BASE_URL')) {
        $manual = trim((string) constant('APP_BASE_URL'));
        if ($manual !== '') {
            return rtrim($manual, '/');
        }
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isHttps = $forwardedProto === 'https'
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $scheme = $isHttps ? 'https' : 'http';

    $host = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
    if ($host === '') {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    }
    if ($host === '') {
        $serverName = trim((string) ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $serverPort = (int) ($_SERVER['SERVER_PORT'] ?? 80);
        $defaultPort = $isHttps ? 443 : 80;
        $host = $serverName . ($serverPort > 0 && $serverPort !== $defaultPort ? ':' . $serverPort : '');
    }
    if (strpos($host, ',') !== false) {
        $host = trim(explode(',', $host)[0]);
    }

    $root = webRootPathFromEntryScript();
    if ($root === null) {
        $root = webRootPathFromInstallDir();
    }
    if ($root === null) {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir = rtrim(dirname($scriptName), '/');
        if ($dir === '.' || $dir === '\\') {
            $dir = '';
        }
        $root = (string) preg_replace('#/admin$#', '', $dir);
    }

    return $scheme . '://' . $host . ($root === '' ? '' : $root);
}

/**
 * Absolute URL for a stored media path (uploads, assets) or external image URL.
 * Fixes paths saved as "/uploads/..." on subfolder installs (e.g. /Menus) where the browser
 * would otherwise request the host root and 404.
 */
function publicMediaUrl(?string $stored): string
{
    $raw = trim((string) $stored);
    $base = rtrim(baseUrl(), '/');
    $placeholder = $base . '/assets/images/placeholder.svg';

    if ($raw === '') {
        return $placeholder;
    }

    if (preg_match('#^https?://#i', $raw)) {
        return $raw;
    }

    if (strpos($raw, '..') !== false) {
        return $placeholder;
    }

    if (preg_match('#^[a-zA-Z]:[/\\\\]#', $raw)) {
        return $placeholder;
    }

    if (preg_match('#^//#', $raw)) {
        return 'https:' . $raw;
    }

    $path = str_replace('\\', '/', $raw);
    $path = preg_replace('#^\./+#', '', $path) ?? '';

    if ($path === '' || $path === '/') {
        return $placeholder;
    }

    if ($path[0] === '/') {
        return $base . $path;
    }

    return $base . '/' . $path;
}

function randomSlug(int $length = 10): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $result;
}

function uploadImage(array $file, string $folder): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        return null;
    }

    if ((int) $file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $ext = $allowed[$mime];
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/' . trim($folder, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $targetPath = $targetDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return 'uploads/' . trim($folder, '/') . '/' . $filename;
}

function generateMenuQr(string $menuCode): ?string
{
    $fullUrl = baseUrl() . '/menu.php?id=' . urlencode($menuCode);
    $dir = __DIR__ . '/../qrcodes';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = 'qr_' . $menuCode . '.png';
    $path = $dir . '/' . $filename;
    $ok = QRcode::png($fullUrl, $path, 'M', 8, 2);
    return $ok ? 'qrcodes/' . $filename : null;
}

function formatPrice($price): string
{
    return number_format((float) $price, 2, '.', '');
}

/** Formatted amount with thousands separators + ETB (Ethiopian Birr). */
function formatPriceEtb($price): string
{
    return number_format((float) $price, 2, '.', ',') . ' ETB';
}

function safeDeleteUpload(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }
    $relativePath = str_replace('\\', '/', $relativePath);
    if (strpos($relativePath, '..') !== false) {
        return;
    }
    $base = realpath(__DIR__ . '/..');
    if ($base === false) {
        return;
    }
    $full = realpath($base . '/' . $relativePath);
    if ($full !== false && strpos($full, $base) === 0 && is_file($full)) {
        @unlink($full);
    }
}
