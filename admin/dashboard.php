<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout_top.php';

$isSuper = isSuperAdmin();
$allowedIds = allowedMenuIds();

if ($isSuper) {
    $menusCount = (int) $pdo->query('SELECT COUNT(*) FROM menus')->fetchColumn();
    $itemsCount = (int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
    $catCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $views = (int) $pdo->query('SELECT COALESCE(SUM(views),0) FROM menus')->fetchColumn();
    $visibleItems = (int) $pdo->query('SELECT COUNT(*) FROM items WHERE is_visible = 1')->fetchColumn();
    $hiddenItems = (int) $pdo->query('SELECT COUNT(*) FROM items WHERE is_visible = 0')->fetchColumn();
    $qrCount = (int) $pdo->query('SELECT COUNT(*) FROM qr_codes')->fetchColumn();
    $topMenuStmt = $pdo->query('SELECT name, views FROM menus ORDER BY views DESC, id DESC LIMIT 1');
    $topMenu = $topMenuStmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'N/A', 'views' => 0];
    $menuStatsRows = $pdo->query('
        SELECT
            m.id,
            m.name,
            m.views,
            COUNT(DISTINCT c.id) AS categories_count,
            COUNT(DISTINCT i.id) AS items_count,
            SUM(CASE WHEN i.is_visible = 1 THEN 1 ELSE 0 END) AS visible_items_count,
            SUM(CASE WHEN i.is_visible = 0 THEN 1 ELSE 0 END) AS hidden_items_count,
            COUNT(DISTINCT q.id) AS qr_count
        FROM menus m
        LEFT JOIN categories c ON c.menu_id = m.id
        LEFT JOIN items i ON i.menu_id = m.id
        LEFT JOIN qr_codes q ON q.menu_id = m.id
        GROUP BY m.id, m.name, m.views
        ORDER BY m.views DESC, m.id DESC
        LIMIT 8
    ')->fetchAll();
} elseif (!empty($allowedIds)) {
    $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));

    $menuStmt = $pdo->prepare("SELECT COUNT(*) FROM menus WHERE id IN ($placeholders)");
    $menuStmt->execute($allowedIds);
    $menusCount = (int) $menuStmt->fetchColumn();

    $itemStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE menu_id IN ($placeholders)");
    $itemStmt->execute($allowedIds);
    $itemsCount = (int) $itemStmt->fetchColumn();

    $catStmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE menu_id IN ($placeholders)");
    $catStmt->execute($allowedIds);
    $catCount = (int) $catStmt->fetchColumn();

    $viewStmt = $pdo->prepare("SELECT COALESCE(SUM(views),0) FROM menus WHERE id IN ($placeholders)");
    $viewStmt->execute($allowedIds);
    $views = (int) $viewStmt->fetchColumn();

    $visibleStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE menu_id IN ($placeholders) AND is_visible = 1");
    $visibleStmt->execute($allowedIds);
    $visibleItems = (int) $visibleStmt->fetchColumn();

    $hiddenStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE menu_id IN ($placeholders) AND is_visible = 0");
    $hiddenStmt->execute($allowedIds);
    $hiddenItems = (int) $hiddenStmt->fetchColumn();

    $qrStmt = $pdo->prepare("SELECT COUNT(*) FROM qr_codes WHERE menu_id IN ($placeholders)");
    $qrStmt->execute($allowedIds);
    $qrCount = (int) $qrStmt->fetchColumn();

    $topMenuStmt = $pdo->prepare("SELECT name, views FROM menus WHERE id IN ($placeholders) ORDER BY views DESC, id DESC LIMIT 1");
    $topMenuStmt->execute($allowedIds);
    $topMenu = $topMenuStmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'N/A', 'views' => 0];

    $menuStatsStmt = $pdo->prepare("
        SELECT
            m.id,
            m.name,
            m.views,
            COUNT(DISTINCT c.id) AS categories_count,
            COUNT(DISTINCT i.id) AS items_count,
            SUM(CASE WHEN i.is_visible = 1 THEN 1 ELSE 0 END) AS visible_items_count,
            SUM(CASE WHEN i.is_visible = 0 THEN 1 ELSE 0 END) AS hidden_items_count,
            COUNT(DISTINCT q.id) AS qr_count
        FROM menus m
        LEFT JOIN categories c ON c.menu_id = m.id
        LEFT JOIN items i ON i.menu_id = m.id
        LEFT JOIN qr_codes q ON q.menu_id = m.id
        WHERE m.id IN ($placeholders)
        GROUP BY m.id, m.name, m.views
        ORDER BY m.views DESC, m.id DESC
        LIMIT 8
    ");
    $menuStatsStmt->execute($allowedIds);
    $menuStatsRows = $menuStatsStmt->fetchAll();
} else {
    $menusCount = 0;
    $itemsCount = 0;
    $catCount = 0;
    $views = 0;
    $visibleItems = 0;
    $hiddenItems = 0;
    $qrCount = 0;
    $topMenu = ['name' => 'N/A', 'views' => 0];
    $menuStatsRows = [];
}

$avgItemsPerMenu = $menusCount > 0 ? round($itemsCount / $menusCount, 2) : 0;
?>
<style>
    .dashboard-welcome {
        background: linear-gradient(140deg, #ffffff, #f8fbff);
        border: 1px solid #dbe5f2;
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }
    .dashboard-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.25rem;
        letter-spacing: -0.01em;
    }
    .dashboard-subtitle {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 0.9rem;
    }
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }
    .dashboard-stat {
        border: 1px solid #dbe5f2;
        border-radius: 14px;
        padding: 13px 12px;
        background: linear-gradient(150deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .dashboard-stat::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, #ef4444, #f59e0b);
        opacity: 0.9;
    }
    .dashboard-stat:hover {
        transform: translateY(-2px);
        border-color: #c6d5ea;
        box-shadow: 0 14px 24px rgba(15, 23, 42, 0.1);
    }
    .dashboard-stat strong {
        display: block;
        font-size: 1.42rem;
        color: #0f172a;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .dashboard-stat .muted {
        margin-top: 4px;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }
    .dashboard-highlight {
        margin-top: 12px;
        border: 1px solid #dbe5f2;
        border-radius: 14px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        padding: 14px;
        color: #334155;
        font-size: 0.9rem;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
    }
    .dashboard-highlight strong {
        color: #0f172a;
    }
    .dashboard-table-wrap {
        margin-top: 12px;
        border: 1px solid #dbe5f2;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
    }
    .dashboard-table-title {
        margin: 0;
        padding: 12px 14px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.95rem;
        color: #0f172a;
    }
    .dashboard-menu-table {
        width: 100%;
        border-collapse: collapse;
    }
    .dashboard-menu-table th,
    .dashboard-menu-table td {
        padding: 9px 10px;
        border-bottom: 1px solid #eef2f7;
        text-align: left;
        font-size: 0.82rem;
    }
    .dashboard-menu-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .dashboard-menu-table tbody tr:last-child td {
        border-bottom: 0;
    }
</style>

<div class="dashboard-welcome">
    <h2 class="dashboard-title">Welcome, <?= e($_SESSION['admin_name'] ?? 'Admin') ?></h2>
    <p class="dashboard-subtitle">Manage menus, categories, items, and QR links from one panel.</p>
</div>
<div class="dashboard-grid">
    <article class="dashboard-stat"><strong><?= $menusCount ?></strong><div class="muted">Menus</div></article>
    <article class="dashboard-stat"><strong><?= $catCount ?></strong><div class="muted">Categories</div></article>
    <article class="dashboard-stat"><strong><?= $itemsCount ?></strong><div class="muted">Items</div></article>
    <article class="dashboard-stat"><strong><?= $views ?></strong><div class="muted">Menu Views</div></article>
    <article class="dashboard-stat"><strong><?= $visibleItems ?></strong><div class="muted">Visible Items</div></article>
    <article class="dashboard-stat"><strong><?= $hiddenItems ?></strong><div class="muted">Hidden Items</div></article>
    <article class="dashboard-stat"><strong><?= $qrCount ?></strong><div class="muted">QR Codes</div></article>
    <article class="dashboard-stat"><strong><?= e((string) $avgItemsPerMenu) ?></strong><div class="muted">Avg Items / Menu</div></article>
</div>
<div class="dashboard-highlight">
    <strong>Top Menu:</strong> <?= e((string) ($topMenu['name'] ?? 'N/A')) ?> (<?= (int) ($topMenu['views'] ?? 0) ?> views)
</div>

<div class="dashboard-table-wrap">
    <h3 class="dashboard-table-title">Top Menus - Key Stats</h3>
    <?php if (empty($menuStatsRows)): ?>
        <p class="muted" style="padding:12px 14px;margin:0;">No menu stats available.</p>
    <?php else: ?>
        <table class="dashboard-menu-table">
            <thead>
                <tr>
                    <th>Menu Name</th>
                    <th>Views</th>
                    <th>Categories</th>
                    <th>Items</th>
                    <th>Visible</th>
                    <th>Hidden</th>
                    <th>QR</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menuStatsRows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['name']) ?></td>
                        <td><?= (int) $row['views'] ?></td>
                        <td><?= (int) $row['categories_count'] ?></td>
                        <td><?= (int) $row['items_count'] ?></td>
                        <td><?= (int) $row['visible_items_count'] ?></td>
                        <td><?= (int) $row['hidden_items_count'] ?></td>
                        <td><?= (int) $row['qr_count'] > 0 ? 'Yes' : 'No' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
