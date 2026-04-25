<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout_top.php';

$message = '';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
}
$isSuper = isSuperAdmin();
$allowedIds = allowedMenuIds();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && $isSuper) {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM menus WHERE id = ?')->execute([$id]);
        setFlashMessage('Menu deleted.');
        header('Location: menus.php');
        exit;
    }
}

if ($isSuper) {
    $rows = $pdo->query('
        SELECT m.*, q.qr_path, q.qr_url
        FROM menus m
        LEFT JOIN qr_codes q ON q.menu_id = m.id
        ORDER BY m.id DESC
    ')->fetchAll();
} elseif (!empty($allowedIds)) {
    $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
    $stmt = $pdo->prepare("
        SELECT m.*, q.qr_path, q.qr_url
        FROM menus m
        LEFT JOIN qr_codes q ON q.menu_id = m.id
        WHERE m.id IN ($placeholders)
        ORDER BY m.id DESC
    ");
    $stmt->execute($allowedIds);
    $rows = $stmt->fetchAll();
} else {
    $rows = [];
}
?>
<div class="menus-modern">
    <style>
        .menus-modern {
            border: 1px solid #dbe3ef;
            border-radius: 20px;
            background: #ffffff;
            padding: 18px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
        }
        .menus-modern__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .menus-modern__title {
            margin: 0;
            font-size: 1.22rem;
            color: #0f172a;
            letter-spacing: -0.01em;
        }
        .menus-modern__subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 0.88rem;
        }
        .menus-modern__notice {
            margin: 0 0 14px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-size: 0.92rem;
        }
        .menus-modern__stats {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .menus-modern__filters {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(180px, 0.5fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        .menus-filter-empty {
            display: none;
            text-align: center;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            padding: 14px;
            margin-top: 8px;
        }
        .menus-stat-chip {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #334155;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .menus-modern__headrow {
            display: grid;
            grid-template-columns: 1.2fr 0.7fr 0.9fr;
            gap: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            padding: 0 12px 6px;
        }
        .menus-modern__headrow span:last-child {
            text-align: center;
        }
        .menus-modern__list {
            display: grid;
            gap: 8px;
            margin-top: 4px;
        }
        .menu-row {
            display: grid;
            grid-template-columns: 1.2fr 0.7fr 0.9fr;
            gap: 8px;
            align-items: center;
            border: 1px solid #dbe5f2;
            border-radius: 14px;
            background: #ffffff;
            padding: 9px 10px;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
            position: relative;
            overflow: hidden;
        }
        .menu-row::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #ef4444, #f59e0b);
            opacity: 0.9;
        }
        .menu-row:hover {
            border-color: #c5d5ea;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.09);
        }
        .menu-row__identity {
            display: grid;
            gap: 4px;
            padding-left: 6px;
        }
        .menu-row__name {
            margin: 0;
            color: #0f172a;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .menu-row__meta {
            margin: 0;
            color: #64748b;
            font-size: 0.74rem;
        }
        .menu-row__qr-url {
            margin-top: 6px;
            font-size: 0.78rem;
            color: #475569;
            word-break: break-all;
            line-height: 1.45;
        }
        .menu-row__qr-url a {
            color: #0c4a6e;
            text-decoration: none;
            border-bottom: 1px dashed #7dd3fc;
        }
        .menu-row__qr-url a:hover {
            color: #0369a1;
        }
        .menu-row__code {
            display: inline-block;
            font-family: Consolas, "Courier New", monospace;
            border: 1px solid #d8e2ef;
            border-radius: 8px;
            background: #f8fafc;
            padding: 3px 8px;
            color: #334155;
            font-size: 0.69rem;
        }
        .menu-row__stats {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            padding: 8px 6px;
            text-align: center;
        }
        .menu-row__qr-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            padding: 6px;
        }
        .menu-row__qr-label {
            margin: 0;
            color: #64748b;
            font-size: 0.71rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .menu-row__stats strong {
            font-size: 1rem;
            color: #0f172a;
        }
        .menu-row__stats small {
            display: block;
            color: #64748b;
            margin-top: 2px;
            font-size: 0.66rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .menu-row__actions {
            display: flex;
            grid-column: 1 / -1;
            justify-content: flex-start;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 1px;
            padding-top: 7px;
            border-top: 1px dashed #d7e2ee;
        }
        .menu-row__actions form {
            margin: 0;
        }
        .menu-row__group {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px;
            border: 1px solid #dde6f2;
            border-radius: 10px;
            background: #f8fbff;
        }
        .menu-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 8px;
            border: 1px solid #d3dce9;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 6px 8px;
            line-height: 1;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .menu-btn:hover {
            border-color: #9fb2cc;
            background: #f8fafc;
        }
        .menu-btn--primary {
            background: linear-gradient(135deg, #ef4444, #f59e0b);
            border-color: #ea580c;
            color: #fff;
        }
        .menu-btn--danger {
            border-color: #fecaca;
            color: #b91c1c;
            background: #fff5f5;
        }
        .menu-btn--danger:hover {
            border-color: #fca5a5;
        }
        .qr-preview-thumb {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            border: 1px solid #dbe5f2;
            background: #fff;
            object-fit: cover;
            cursor: zoom-in;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .qr-preview-thumb:hover {
            transform: scale(1.04);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.15);
        }
        .menus-modern__empty {
            text-align: center;
            color: #64748b;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 28px 14px;
            margin-top: 14px;
            background: #f8fafc;
        }
        .qr-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(2, 6, 23, 0.72);
            z-index: 1200;
            padding: 16px;
        }
        .qr-modal.open {
            display: flex;
        }
        .qr-modal img {
            max-width: min(450px, 92vw);
            max-height: 86vh;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
        }
        .qr-modal-close {
            position: absolute;
            top: 16px;
            right: 18px;
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 999px;
            font-size: 24px;
            line-height: 1;
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
            cursor: pointer;
        }
        @media (max-width: 920px) {
            .menus-modern__filters {
                grid-template-columns: 1fr;
            }
            .menus-modern__headrow {
                display: none;
            }
            .menu-row {
                grid-template-columns: 1fr;
                padding: 9px;
            }
            .menu-row__stats {
                text-align: left;
            }
            .menu-row__qr-panel {
                align-items: flex-start;
            }
            .menu-row__identity {
                padding-left: 2px;
            }
            .menu-row::before {
                width: 100%;
                height: 3px;
            }
        }
    </style>
    <div class="menus-modern__header">
        <div>
            <h3 class="menus-modern__title">Menu</h3>
            <p class="menus-modern__subtitle">Clean and simple list of all available menus.</p>
        </div>
        <?php if ($isSuper): ?>
            <a class="menu-btn menu-btn--primary" href="menu_add.php">+ Create Menu</a>
        <?php endif; ?>
    </div>
    <?php if ($message): ?><p class="menus-modern__notice"><?= e($message) ?></p><?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="menus-modern__empty">No menus found yet. Create your first menu to get started.</div>
    <?php else: ?>
        <div class="menus-modern__filters">
            <div>
                <label>Search menu</label>
                <input id="menuSearchFilter" type="text" placeholder="Type menu name or code">
            </div>
            <div>
                <label>QR filter</label>
                <select id="menuQrFilter">
                    <option value="">All</option>
                    <option value="has">Has QR</option>
                    <option value="missing">No QR</option>
                </select>
            </div>
        </div>
        <div class="menus-modern__stats">
            <span class="menus-stat-chip">Total menus: <?= count($rows) ?></span>
            <span class="menus-stat-chip">Total views: <?= (int) array_sum(array_map(static fn ($item) => (int) $item['views'], $rows)) ?></span>
        </div>
        <div class="menus-modern__headrow">
            <span>Menu</span>
            <span>Views</span>
            <span>QR</span>
        </div>
        <div class="menus-modern__list">
            <?php foreach ($rows as $row): ?>
                <?php $downloadName = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $row['name']), '-')) . '-qr.png'; ?>
                <article
                    class="menu-row"
                    data-name="<?= e(strtolower((string) $row['name'])) ?>"
                    data-code="<?= e(strtolower((string) $row['menu_code'])) ?>"
                    data-qr="<?= !empty($row['qr_path']) ? 'has' : 'missing' ?>"
                >
                    <div class="menu-row__identity">
                        <h4 class="menu-row__name"><?= e($row['name']) ?></h4>
                        <p class="menu-row__meta">Code: <span class="menu-row__code"><?= e($row['menu_code']) ?></span></p>
                        <?php if ($isSuper && !empty($row['qr_url'])): ?>
                            <p class="menu-row__qr-url">QR URL: <a href="<?= e($row['qr_url']) ?>" target="_blank"><?= e($row['qr_url']) ?></a></p>
                        <?php endif; ?>
                    </div>

                    <div class="menu-row__stats">
                        <strong><?= (int) $row['views'] ?></strong>
                        <small>total views</small>
                    </div>

                    <div class="menu-row__qr-panel">
                        <?php if (!empty($row['qr_path'])): ?>
                            <?php $qrUrl = publicMediaUrl($row['qr_path']); ?>
                            <img
                                class="qr-preview-thumb js-qr-preview"
                                src="<?= e($qrUrl) ?>"
                                alt="QR for <?= e($row['name']) ?>"
                                data-src="<?= e($qrUrl) ?>"
                            >
                            <p class="menu-row__qr-label">Tap to preview</p>
                        <?php else: ?>
                            <p class="menu-row__qr-label">No QR yet</p>
                        <?php endif; ?>
                    </div>

                    <div class="menu-row__actions">
                        <div class="menu-row__group">
                            <a class="menu-btn" target="_blank" href="../menu.php?id=<?= e($row['menu_code']) ?>">Open</a>
                            <a class="menu-btn" href="menu_edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                        </div>
                        <?php if (!empty($row['qr_path'])): ?>
                            <div class="menu-row__group">
                                <a class="menu-btn" href="<?= e($qrUrl) ?>" target="_blank">View QR</a>
                                <a class="menu-btn" href="<?= e($qrUrl) ?>" download="<?= e($downloadName) ?>">Download QR</a>
                            </div>
                        <?php endif; ?>
                        <?php if ($isSuper): ?>
                            <form method="post" onsubmit="return confirm('Delete this menu?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="menu-btn menu-btn--danger" type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div id="menuFilterEmpty" class="menus-filter-empty">No menus match your filter.</div>
    <?php endif; ?>
</div>
<div id="qrModal" class="qr-modal" aria-hidden="true">
    <button id="qrModalClose" class="qr-modal-close" type="button" aria-label="Close preview">&times;</button>
    <img id="qrModalImg" src="" alt="QR Preview">
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('qrModal');
    const modalImg = document.getElementById('qrModalImg');
    const closeBtn = document.getElementById('qrModalClose');
    const thumbs = document.querySelectorAll('.js-qr-preview');

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        modalImg.src = '';
    }

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            modalImg.src = this.dataset.src;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    const searchInput = document.getElementById('menuSearchFilter');
    const qrFilter = document.getElementById('menuQrFilter');
    const menuRows = document.querySelectorAll('.menu-row');
    const emptyFilter = document.getElementById('menuFilterEmpty');
    if (searchInput && qrFilter && menuRows.length > 0) {
        function applyMenuFilters() {
            const keyword = searchInput.value.trim().toLowerCase();
            const qrState = qrFilter.value;
            let visibleCount = 0;

            menuRows.forEach(function (row) {
                const rowName = row.getAttribute('data-name') || '';
                const rowCode = row.getAttribute('data-code') || '';
                const rowQr = row.getAttribute('data-qr') || 'missing';
                const keywordMatch = keyword === '' || rowName.includes(keyword) || rowCode.includes(keyword);
                const qrMatch = qrState === '' || rowQr === qrState;
                const show = keywordMatch && qrMatch;
                row.style.display = show ? '' : 'none';
                if (show) {
                    visibleCount++;
                }
            });

            if (emptyFilter) {
                emptyFilter.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        searchInput.addEventListener('input', applyMenuFilters);
        qrFilter.addEventListener('change', applyMenuFilters);
        applyMenuFilters();
    }

});
</script>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
