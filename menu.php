<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$menuCode = trim($_GET['id'] ?? '');
if ($menuCode === '') {
    http_response_code(404);
    exit('Menu not found.');
}

$menuStmt = $pdo->prepare('SELECT * FROM menus WHERE menu_code = ? LIMIT 1');
$menuStmt->execute([$menuCode]);
$menu = $menuStmt->fetch();

if (!$menu) {
    http_response_code(404);
    exit('Menu not found.');
}

$pdo->prepare('UPDATE menus SET views = views + 1 WHERE id = ?')->execute([$menu['id']]);

$catStmt = $pdo->prepare('SELECT * FROM categories WHERE menu_id = ? ORDER BY sort_order ASC, id ASC');
$catStmt->execute([$menu['id']]);
$categories = $catStmt->fetchAll();

$itemStmt = $pdo->prepare('SELECT * FROM items WHERE menu_id = ? AND is_visible = 1 ORDER BY sort_order ASC, id ASC');
$itemStmt->execute([$menu['id']]);
$allItems = $itemStmt->fetchAll();

$itemsByCategory = [];
foreach ($allItems as $item) {
    $itemsByCategory[$item['category_id']][] = $item;
}

$bannerUrl = !empty($menu['banner_image']) ? publicMediaUrl($menu['banner_image']) : '';
$bannerDockedUrl = !empty($menu['banner_image_docked'] ?? null) ? publicMediaUrl($menu['banner_image_docked']) : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($menu['name']) ?> - Menu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Yuji+Syuku&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-menu">
    <header class="topbar">
        <div class="container topbar-inner">
            <div class="topbar-brand">
                <?php if (!empty($menu['logo_image'])): ?>
                    <img class="topbar-logo" src="<?= e(publicMediaUrl($menu['logo_image'])) ?>" alt="" width="44" height="44" decoding="async">
                <?php endif; ?>
                <div class="topbar-copy">
                    <span class="topbar-kicker">Menu</span>
                    <strong class="topbar-title"><?= e($menu['name']) ?></strong>
                    <p class="topbar-desc"><?= e((string) ($menu['description'] ?? 'Explore our menu items.')) ?></p>
                </div>
            </div>
            <button id="shareBtn" class="btn btn-outline btn-share-menu" type="button" aria-label="Share this menu">
                <span class="btn-share-menu__stack" aria-hidden="true">
                    <span class="btn-share-menu__icon">
                        <svg class="btn-share-menu__svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <circle cx="18" cy="5" r="3" />
                            <circle cx="6" cy="12" r="3" />
                            <circle cx="18" cy="19" r="3" />
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                        </svg>
                    </span>
                    <span class="btn-share-menu__label">Share</span>
                </span>
            </button>
        </div>
    </header>

    <div class="menu-hero-track" id="menuHeroTrack">
        <?php if ($bannerUrl !== ''): ?>
            <section id="menuHero" class="hero hero--edge hero--menu" aria-label="Menu">
                <?php if ($bannerDockedUrl !== ''): ?>
                    <div
                        class="hero-banner hero-banner--photo hero-banner--dual"
                        data-banner-full="<?= e($bannerUrl) ?>"
                        data-banner-docked="<?= e($bannerDockedUrl) ?>"
                    >
                        <div class="hero-banner__layer hero-banner__layer--base" style="background-image:url('<?= e($bannerUrl) ?>');"></div>
                        <div class="hero-banner__layer hero-banner__layer--alt" style="background-image:url('<?= e($bannerDockedUrl) ?>');" aria-hidden="true"></div>
                    </div>
                <?php else: ?>
                    <div
                        class="hero-banner hero-banner--photo"
                        data-banner-full="<?= e($bannerUrl) ?>"
                        style="background-image:url('<?= e($bannerUrl) ?>');"
                    ></div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section id="menuHero" class="hero hero--edge hero--menu" aria-label="Menu">
                <div class="hero-banner hero-banner--gradient hero-banner--photo"></div>
            </section>
        <?php endif; ?>
    </div>

    <main class="container">
        <div class="menu-sticky-wrap">
            <div class="menu-filters menu-filters--toolbar" aria-label="Search and filter menu">
                <div class="filter-rail" role="group" aria-label="Search and categories">
                    <label class="search-pill" title="Search menu">
                        <span class="search-pill__icon" aria-hidden="true"></span>
                        <input
                            id="searchInput"
                            class="search-pill__input"
                            type="search"
                            inputmode="search"
                            autocomplete="off"
                            placeholder="Search…"
                            aria-label="Search menu"
                        >
                    </label>
                    <div class="filter-rail__tablist" role="tablist" aria-label="Jump to category">
                        <button type="button" class="cat-pill is-active" data-category="all" data-target="menuListTop" role="tab" aria-selected="true">All</button>
                        <?php foreach ($categories as $cat): ?>
                            <button
                                type="button"
                                class="cat-pill"
                                data-category="<?= (int) $cat['id'] ?>"
                                data-target="cat-<?= (int) $cat['id'] ?>"
                                role="tab"
                                aria-selected="false"
                            ><?= e($cat['name']) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="menuListTop" class="menu-list-top" aria-hidden="true"></div>

        <?php foreach ($categories as $cat): ?>
            <section id="cat-<?= (int) $cat['id'] ?>" class="category-block" data-category-section="<?= (int) $cat['id'] ?>" data-category-title="<?= e($cat['name']) ?>">
                <h3 class="category-title"><?= e($cat['name']) ?></h3>
                <div class="items-grid">
                    <?php foreach ($itemsByCategory[$cat['id']] ?? [] as $item): ?>
                        <?php $itemImg = publicMediaUrl($item['image'] ?? null); ?>
                        <article
                            class="item-card"
                            tabindex="0"
                            data-category="<?= (int) $cat['id'] ?>"
                            data-name="<?= e($item['name']) ?>"
                            data-description="<?= e((string) $item['description']) ?>"
                            data-price-display="<?= e(formatPriceEtb($item['price'])) ?>"
                            data-item-image="<?= e($itemImg) ?>"
                        >
                            <div class="item-card__head" aria-hidden="true">
                                <div class="item-card__img-wrap">
                                    <img
                                        class="item-card__img"
                                        loading="lazy"
                                        src="<?= e($itemImg) ?>"
                                        alt=""
                                        role="presentation"
                                        width="160"
                                        height="160"
                                        decoding="async"
                                    >
                                </div>
                            </div>
                            <div class="item-card__body">
                                <h4 class="item-card__title"><?= e($item['name']) ?></h4>
                                <div class="item-card__meta">
                                    <p class="item-card__desc muted"><?= e((string) $item['description']) ?></p>
                                </div>
                                <div class="item-card__price-row">
                                    <span class="item-card__price"><?= e(formatPriceEtb($item['price'])) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <div id="noResults" class="no-results">No matching items found. Try another keyword or category.</div>
    </main>

    <div id="itemModal" class="item-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="itemModalTitle">
        <div class="image-modal__toolbar">
            <button id="itemModalBack" class="image-modal-back" type="button">← Back to menu</button>
            <button id="itemModalClose" class="image-modal-close" type="button" aria-label="Close details">&times;</button>
        </div>
        <div class="item-modal__stage" id="itemModalStage">
            <div class="item-modal__panel">
                <div class="item-modal__media">
                    <img id="itemModalImg" class="item-modal__img" src="" alt="">
                </div>
                <div class="item-modal__info">
                    <h2 id="itemModalTitle" class="item-modal__title"></h2>
                    <p id="itemModalDesc" class="item-modal__desc muted"></p>
                    <p id="itemModalPrice" class="item-modal__price price"></p>
                </div>
            </div>
        </div>
    </div>

    <div id="shareSheet" class="share-sheet" aria-hidden="true">
        <div class="share-sheet__backdrop" id="shareSheetBackdrop"></div>
        <div class="share-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="shareSheetTitle">
            <div class="share-sheet__grab" aria-hidden="true"></div>
            <div class="share-sheet__header">
                <h2 id="shareSheetTitle" class="share-sheet__title">Share this menu</h2>
                <button type="button" class="share-sheet__x" id="shareSheetClose" aria-label="Close">&times;</button>
            </div>
            <p class="share-sheet__copied" id="shareSheetCopied">Link copied!</p>
            <p class="share-sheet__sub">Send it on:</p>
            <ul class="share-sheet__list">
                <li>
                    <a id="shareLinkWhatsApp" class="share-sheet__link" href="#" target="_blank" rel="noopener noreferrer">
                        <span class="share-sheet__ico" aria-hidden="true">💬</span>
                        <span>WhatsApp</span>
                    </a>
                </li>
                <li>
                    <a id="shareLinkFacebook" class="share-sheet__link" href="#" target="_blank" rel="noopener noreferrer">
                        <span class="share-sheet__ico" aria-hidden="true">📘</span>
                        <span>Facebook</span>
                    </a>
                </li>
                <li>
                    <a id="shareLinkX" class="share-sheet__link" href="#" target="_blank" rel="noopener noreferrer">
                        <span class="share-sheet__ico" aria-hidden="true">𝕏</span>
                        <span>X (Twitter)</span>
                    </a>
                </li>
                <li>
                    <a id="shareLinkTelegram" class="share-sheet__link" href="#" target="_blank" rel="noopener noreferrer">
                        <span class="share-sheet__ico" aria-hidden="true">✈️</span>
                        <span>Telegram</span>
                    </a>
                </li>
                <li>
                    <a id="shareLinkEmail" class="share-sheet__link" href="#">
                        <span class="share-sheet__ico" aria-hidden="true">✉️</span>
                        <span>Email</span>
                    </a>
                </li>
            </ul>
            <button type="button" class="share-sheet__native btn" id="shareNativeBtn">Share using device…</button>
            <button type="button" class="share-sheet__copyagain btn btn-outline" id="shareCopyAgain">Copy link again</button>
        </div>
    </div>

    <script src="assets/js/menu.js"></script>
</body>
</html>
