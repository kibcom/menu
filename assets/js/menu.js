document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const catPills = document.querySelectorAll('.cat-pill[data-category]');
    const items = document.querySelectorAll('.item-card');
    const noResults = document.getElementById('noResults');
    const categorySections = document.querySelectorAll('[data-category-section]');

    function applyFilter() {
        const q = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visibleCount = 0;
        const categoryTitleMatchMap = {};

        categorySections.forEach((section) => {
            const sectionCategory = section.dataset.categorySection || '';
            const sectionTitle = (section.dataset.categoryTitle || '').toLowerCase();
            categoryTitleMatchMap[sectionCategory] = q !== '' && sectionTitle.includes(q);
        });

        items.forEach((item) => {
            const itemCategory = item.dataset.category || '';
            const itemName = (item.dataset.name || '').toLowerCase();
            const itemDesc = (item.dataset.description || '').toLowerCase();
            const titleMatch = !!categoryTitleMatchMap[itemCategory];
            const bySearch = q === '' || titleMatch || itemName.includes(q) || itemDesc.includes(q);
            item.style.display = bySearch ? 'flex' : 'none';
            if (bySearch) {
                visibleCount += 1;
            }
        });

        categorySections.forEach((section) => {
            const sectionItems = section.querySelectorAll('.item-card');
            let sectionHasVisible = false;

            sectionItems.forEach((it) => {
                if (it.style.display !== 'none') {
                    sectionHasVisible = true;
                }
            });

            section.style.display = sectionHasVisible ? '' : 'none';
        });

        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        syncFilterPillsToScroll();
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilter);
    }

    function getStickyBottomY() {
        const wrap = document.querySelector('.menu-sticky-wrap');
        if (wrap) {
            return wrap.getBoundingClientRect().bottom;
        }
        const tb = document.querySelector('.topbar');
        return tb ? tb.getBoundingClientRect().bottom : 72;
    }

    let lastActiveScrollCategory = null;

    function syncFilterPillsToScroll() {
        if (!catPills.length) return;

        const threshold = getStickyBottomY() + 12;
        let active = 'all';
        let anyVisible = false;

        categorySections.forEach(function (section) {
            if (section.style.display === 'none') return;
            anyVisible = true;
            const top = section.getBoundingClientRect().top;
            if (top <= threshold) {
                active = section.dataset.categorySection || 'all';
            }
        });

        if (!anyVisible) {
            active = 'all';
        }

        if (active === lastActiveScrollCategory) return;
        lastActiveScrollCategory = active;

        let matchedPill = null;
        catPills.forEach(function (pill) {
            const isActive = pill.dataset.category === active;
            pill.classList.toggle('is-active', isActive);
            pill.setAttribute('aria-selected', isActive ? 'true' : 'false');
            if (isActive) {
                matchedPill = pill;
            }
        });

        if (matchedPill) {
            scrollFilterRailToPill(matchedPill);
        }
    }

    const menuPageTopbar = document.body.classList.contains('page-menu')
        ? document.querySelector('header.topbar')
        : null;

    function syncMenuTopbarHeight() {
        if (!menuPageTopbar) return;
        /* offsetHeight matches layout box (incl. border) and avoids a 1–2px hairline gap vs getBoundingClientRect */
        const h = Math.max(44, menuPageTopbar.offsetHeight);
        document.body.style.setProperty('--menu-topbar-height', String(h) + 'px');
    }

    if (menuPageTopbar && typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(function () {
            syncMenuTopbarHeight();
        }).observe(menuPageTopbar);
    }

    const menuHeroTrack = document.getElementById('menuHeroTrack');
    const menuHeroPanel = document.getElementById('menuHero');
    const heroBanner = menuHeroPanel ? menuHeroPanel.querySelector('.hero-banner') : null;

    function hasHeroBannerSwapPair() {
        return !!(heroBanner && heroBanner.dataset && heroBanner.dataset.bannerDocked);
    }

    /** When a scrolled banner exists, crossfade between layers (see .hero-banner--dual) instead of swapping one bg. */
    function syncHeroBannerSwap(visualH) {
        if (!heroBanner || !hasHeroBannerSwapPair()) {
            return;
        }
        const full = heroBanner.dataset.bannerFull || '';
        const docked = heroBanner.dataset.bannerDocked || '';
        if (!full || !docked) {
            return;
        }
        const useFull = visualH > MENU_DOCK_H + 2;
        if (heroBanner.classList.contains('hero-banner--dual')) {
            heroBanner.classList.toggle('is-hero-banner-alt-visible', !useFull);
            return;
        }
        const url = useFull ? full : docked;
        heroBanner.style.backgroundImage = 'url(' + JSON.stringify(url) + ')';
    }

    function resetHeroBannerSwapToFull() {
        if (!heroBanner || !hasHeroBannerSwapPair()) {
            return;
        }
        if (heroBanner.classList.contains('hero-banner--dual')) {
            heroBanner.classList.remove('is-hero-banner-alt-visible');
            return;
        }
        const full = heroBanner.dataset.bannerFull || '';
        if (!full) {
            return;
        }
        heroBanner.style.backgroundImage = 'url(' + JSON.stringify(full) + ')';
    }
    const menuStickyWrap = document.querySelector('.menu-sticky-wrap');
    const MENU_DOCK_H = 64;
    /** While scrollY is within this many px of the top, banner stays full height */
    const MENU_NEAR_TOP = 2;
    /** When scrolling down: shrink full → docked over this many px (also when reversing a near-top expand) */
    const MENU_SHRINK_RANGE = 10;
    /** When scrolling up: stay docked until scrollY is below this, then grow toward full (smaller = faster expand) */
    const MENU_EXPAND_HOLD = 38;
    /** When scrolling down: keep banner full until this scrollY (hero + toolbar + ~first row of cards) */
    const MENU_SHRINK_GATE_SCROLL = 240;
    /** At/below this scrollY, clear the gate so the next scroll down keeps the banner full until gate scroll */
    const MENU_SHRINK_GATE_RESET_BELOW = 165;

    let menuHeroFullH = 0;
    let menuHeroVisualH = 0;
    let menuHeroAnimRaf = 0;
    let menuHeroPrevScrollY = -1;
    let menuShrinkGatePassed = false;

    function syncMenuFiltersReserve() {
        if (!menuStickyWrap || !document.body.classList.contains('page-menu')) return;
        const cs = window.getComputedStyle(menuStickyWrap);
        const mt = parseFloat(cs.marginTop) || 0;
        const mb = parseFloat(cs.marginBottom) || 0;
        const h = Math.ceil(menuStickyWrap.offsetHeight + mt + mb);
        document.body.style.setProperty('--menu-filters-reserve', String(Math.max(0, h)) + 'px');
    }

    function measureMenuHeroFull() {
        if (!menuHeroTrack || !menuHeroPanel || !heroBanner) return;
        document.body.classList.remove('is-menu-hero-docked');
        if (heroBanner.classList.contains('hero-banner--dual')) {
            heroBanner.classList.remove('is-hero-banner-alt-visible');
        }
        heroBanner.style.minHeight = '';
        heroBanner.style.maxHeight = '';
        menuHeroTrack.style.height = '';
        void menuHeroPanel.offsetHeight;
        menuHeroFullH = Math.max(MENU_DOCK_H, Math.ceil(menuHeroPanel.getBoundingClientRect().height));
        menuHeroVisualH = menuHeroFullH;
        syncMenuFiltersReserve();
    }

    function getTargetBannerH(scrollY, scrollingUp) {
        if (!menuHeroFullH) return MENU_DOCK_H;
        if (scrollY <= MENU_NEAR_TOP) {
            return menuHeroFullH;
        }

        if (!scrollingUp && !menuShrinkGatePassed) {
            return menuHeroFullH;
        }

        if (hasHeroBannerSwapPair()) {
            if (scrollingUp) {
                if (scrollY >= MENU_EXPAND_HOLD) {
                    return MENU_DOCK_H;
                }
                return menuHeroFullH;
            }
            return scrollY >= MENU_SHRINK_GATE_SCROLL + MENU_SHRINK_RANGE ? MENU_DOCK_H : menuHeroFullH;
        }

        if (scrollingUp) {
            if (scrollY >= MENU_EXPAND_HOLD) {
                return MENU_DOCK_H;
            }
            const span = Math.max(1, MENU_EXPAND_HOLD - MENU_NEAR_TOP);
            const t = (scrollY - MENU_NEAR_TOP) / span;
            const clamped = Math.min(1, Math.max(0, t));
            return menuHeroFullH - clamped * (menuHeroFullH - MENU_DOCK_H);
        }

        const rawT = (scrollY - MENU_SHRINK_GATE_SCROLL) / MENU_SHRINK_RANGE;
        const t = Math.min(1, Math.max(0, rawT));
        return menuHeroFullH - t * (menuHeroFullH - MENU_DOCK_H);
    }

    function applyBannerHeight(roundedVisual, scrollingUp) {
        if (!menuHeroTrack || !menuHeroPanel || !heroBanner || !menuHeroFullH) return;
        const y = window.scrollY;
        const targetEnd = getTargetBannerH(y, scrollingUp);
        const h = Math.max(MENU_DOCK_H, Math.min(menuHeroFullH, roundedVisual));
        /* Match sticky strip to current banner height (not capped at dock height) so no body “gap” while shrinking */
        document.body.style.setProperty('--menu-sticky-extra', String(Math.round(h)) + 'px');

        const wantDock = h <= MENU_DOCK_H + 0.5 && targetEnd <= MENU_DOCK_H + 1;
        const forceUndock = targetEnd > MENU_DOCK_H + 14 || h > MENU_DOCK_H + 8;

        if (wantDock && !forceUndock) {
            if (!document.body.classList.contains('is-menu-hero-docked')) {
                syncMenuFiltersReserve();
            }
            document.body.classList.add('is-menu-hero-docked');
            heroBanner.style.minHeight = '';
            heroBanner.style.maxHeight = '';
            menuHeroTrack.style.height = MENU_DOCK_H + 'px';
        } else {
            document.body.classList.remove('is-menu-hero-docked');
            heroBanner.style.minHeight = h + 'px';
            heroBanner.style.maxHeight = h + 'px';
            menuHeroTrack.style.height = h + 'px';
            syncMenuFiltersReserve();
        }
        syncHeroBannerSwap(h);
    }

    function tickMenuHeroShrink() {
        menuHeroAnimRaf = 0;
        if (!menuHeroTrack || !menuHeroPanel || !heroBanner) return;

        if (!menuHeroFullH) {
            measureMenuHeroFull();
        }
        if (!menuHeroFullH) return;

        const scrollY = window.scrollY;
        if (scrollY <= MENU_SHRINK_GATE_RESET_BELOW) {
            menuShrinkGatePassed = false;
        }
        if (scrollY >= MENU_SHRINK_GATE_SCROLL) {
            menuShrinkGatePassed = true;
        }

        const scrollingUp = menuHeroPrevScrollY >= 0 && scrollY < menuHeroPrevScrollY;
        menuHeroPrevScrollY = scrollY;

        const target = getTargetBannerH(scrollY, scrollingUp);
        menuHeroVisualH = target;
        applyBannerHeight(Math.round(menuHeroVisualH), scrollingUp);
    }

    function scheduleMenuHeroShrink() {
        if (!document.body.classList.contains('page-menu')) return;
        if (!menuHeroTrack || !heroBanner) return;
        if (menuHeroAnimRaf) {
            cancelAnimationFrame(menuHeroAnimRaf);
        }
        menuHeroAnimRaf = requestAnimationFrame(tickMenuHeroShrink);
    }

    /** Clears dock state and inline sizes (e.g. after resize); caller should measure + schedule again. */
    function resetMenuHeroLayout() {
        if (menuHeroAnimRaf) {
            cancelAnimationFrame(menuHeroAnimRaf);
            menuHeroAnimRaf = 0;
        }
        if (!menuHeroTrack || !menuHeroPanel || !heroBanner) return;
        document.body.classList.remove('is-menu-hero-docked');
        resetHeroBannerSwapToFull();
        if (document.body.classList.contains('page-menu')) {
            document.body.style.removeProperty('--menu-sticky-extra');
        }
        heroBanner.style.minHeight = '';
        heroBanner.style.maxHeight = '';
        menuHeroTrack.style.height = '';
        menuHeroTrack.style.transition = '';
        menuHeroFullH = 0;
        menuHeroVisualH = 0;
        menuHeroPrevScrollY = -1;
        menuShrinkGatePassed = false;
    }

    let scrollSpyTick = false;
    window.addEventListener(
        'scroll',
        function () {
            if (scrollSpyTick) return;
            scrollSpyTick = true;
            window.requestAnimationFrame(function () {
                scrollSpyTick = false;
                syncMenuTopbarHeight();
                scheduleMenuHeroShrink();
                syncFilterPillsToScroll();
            });
        },
        { passive: true }
    );

    window.addEventListener('resize', function () {
        syncMenuTopbarHeight();
        resetMenuHeroLayout();
        measureMenuHeroFull();
        scheduleMenuHeroShrink();
        syncFilterPillsToScroll();
    });

    window.addEventListener('load', function () {
        syncMenuTopbarHeight();
        measureMenuHeroFull();
        scheduleMenuHeroShrink();
    });

    window.addEventListener('orientationchange', function () {
        requestAnimationFrame(function () {
            syncMenuTopbarHeight();
            resetMenuHeroLayout();
            measureMenuHeroFull();
            scheduleMenuHeroShrink();
        });
    });

    if (menuStickyWrap && typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(function () {
            syncMenuFiltersReserve();
        }).observe(menuStickyWrap);
    }

    syncMenuTopbarHeight();
    measureMenuHeroFull();
    scheduleMenuHeroShrink();

    function scrollFilterRailToPill(pill) {
        const rail = pill.closest('.filter-rail');
        if (!rail) return;
        const railRect = rail.getBoundingClientRect();
        const pillRect = pill.getBoundingClientRect();
        const nextLeft =
            rail.scrollLeft +
            (pillRect.left - railRect.left) -
            railRect.width / 2 +
            pillRect.width / 2;
        const maxScroll = Math.max(0, rail.scrollWidth - rail.clientWidth);
        rail.scrollTo({
            left: Math.max(0, Math.min(nextLeft, maxScroll)),
            behavior: 'auto',
        });
    }

    catPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            catPills.forEach(function (p) {
                p.classList.remove('is-active');
                p.setAttribute('aria-selected', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-selected', 'true');

            scrollFilterRailToPill(this);

            const targetId = this.dataset.target || '';
            if (targetId) {
                const targetEl = document.getElementById(targetId);
                if (targetEl) {
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    applyFilter();

    function copyUrlViaExecCommand(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;left:-9999px;top:0';
        document.body.appendChild(ta);
        ta.select();
        ta.setSelectionRange(0, text.length);
        let ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }
        document.body.removeChild(ta);
        return ok;
    }

    async function copyMenuLink(url) {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(url);
                return true;
            } catch (e) {
                /* fall through */
            }
        }
        return copyUrlViaExecCommand(url);
    }

    const shareSheet = document.getElementById('shareSheet');
    const shareSheetBackdrop = document.getElementById('shareSheetBackdrop');
    const shareSheetClose = document.getElementById('shareSheetClose');
    const shareSheetCopied = document.getElementById('shareSheetCopied');
    const shareLinkWhatsApp = document.getElementById('shareLinkWhatsApp');
    const shareLinkFacebook = document.getElementById('shareLinkFacebook');
    const shareLinkX = document.getElementById('shareLinkX');
    const shareLinkTelegram = document.getElementById('shareLinkTelegram');
    const shareLinkEmail = document.getElementById('shareLinkEmail');
    const shareNativeBtn = document.getElementById('shareNativeBtn');
    const shareCopyAgain = document.getElementById('shareCopyAgain');
    const shareBtn = document.getElementById('shareBtn');

    function closeShareSheet() {
        if (!shareSheet) return;
        shareSheet.classList.remove('open');
        shareSheet.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function openShareSheet(wasCopied) {
        if (!shareSheet || !shareLinkWhatsApp) return;
        const url = window.location.href;
        const title = document.title;
        const encUrl = encodeURIComponent(url);
        const encTitle = encodeURIComponent(title);
        const msg = encodeURIComponent(title + '\n' + url);

        shareLinkWhatsApp.href = 'https://wa.me/?text=' + msg;
        shareLinkFacebook.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encUrl;
        shareLinkX.href = 'https://twitter.com/intent/tweet?url=' + encUrl + '&text=' + encTitle;
        shareLinkTelegram.href = 'https://t.me/share/url?url=' + encUrl + '&text=' + encTitle;
        shareLinkEmail.href =
            'mailto:?subject=' + encTitle + '&body=' + encodeURIComponent(url + '\n\n' + title);

        if (shareSheetCopied) {
            if (wasCopied) {
                shareSheetCopied.textContent = '✓ Copied! Link is in your clipboard.';
                shareSheetCopied.classList.remove('share-sheet__copied--warn');
            } else {
                shareSheetCopied.textContent =
                    'Could not copy automatically — choose an app below or tap “Copy link again”.';
                shareSheetCopied.classList.add('share-sheet__copied--warn');
            }
        }

        if (shareNativeBtn) {
            shareNativeBtn.style.display = typeof navigator.share === 'function' ? 'block' : 'none';
        }

        shareSheet.classList.add('open');
        shareSheet.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    if (shareBtn && shareSheet) {
        shareBtn.addEventListener('click', async function (event) {
            event.preventDefault();
            const copied = await copyMenuLink(window.location.href);
            openShareSheet(copied);
        });
    }

    if (shareSheetBackdrop) {
        shareSheetBackdrop.addEventListener('click', closeShareSheet);
    }

    if (shareSheetClose) {
        shareSheetClose.addEventListener('click', closeShareSheet);
    }

    if (shareCopyAgain) {
        shareCopyAgain.addEventListener('click', async function () {
            const ok = await copyMenuLink(window.location.href);
            if (shareSheetCopied) {
                shareSheetCopied.textContent = ok
                    ? '✓ Copied again! Link is in your clipboard.'
                    : 'Copy failed — open a link above or copy the URL from the address bar.';
                shareSheetCopied.classList.toggle('share-sheet__copied--warn', !ok);
                shareSheetCopied.classList.add('share-sheet__copied--pulse');
                setTimeout(function () {
                    shareSheetCopied.classList.remove('share-sheet__copied--pulse');
                }, 400);
            }
        });
    }

    if (shareNativeBtn) {
        shareNativeBtn.addEventListener('click', async function () {
            if (typeof navigator.share !== 'function') return;
            try {
                await navigator.share({
                    title: document.title,
                    text: document.title,
                    url: window.location.href,
                });
                closeShareSheet();
            } catch (e) {
                if (e && e.name !== 'AbortError') {
                    /* ignore */
                }
            }
        });
    }

    const itemModal = document.getElementById('itemModal');
    const itemModalImg = document.getElementById('itemModalImg');
    const itemModalTitle = document.getElementById('itemModalTitle');
    const itemModalDesc = document.getElementById('itemModalDesc');
    const itemModalPrice = document.getElementById('itemModalPrice');
    const itemModalClose = document.getElementById('itemModalClose');
    const itemModalBack = document.getElementById('itemModalBack');
    const itemModalStage = document.getElementById('itemModalStage');
    const itemCards = document.querySelectorAll('.item-card');
    const ITEM_MODAL_STATE_KEY = 'menuItemModal';

    function closeItemModalUiOnly() {
        if (!itemModal) return;
        itemModal.classList.remove('open');
        itemModal.setAttribute('aria-hidden', 'true');
        if (itemModalImg) {
            itemModalImg.src = '';
            itemModalImg.removeAttribute('src');
        }
    }

    function closeItemModalWithHistory() {
        if (history.state && history.state[ITEM_MODAL_STATE_KEY]) {
            history.back();
        } else {
            closeItemModalUiOnly();
        }
    }

    function openItemModal(card) {
        if (!itemModal || !itemModalImg || !itemModalTitle || !itemModalDesc || !itemModalPrice) return;
        const img = card.dataset.itemImage || '';
        const name = card.dataset.name || '';
        const desc = (card.dataset.description || '').trim();
        const priceDisplay = card.dataset.priceDisplay || '0.00 ETB';

        itemModalImg.src = img;
        itemModalImg.alt = name;
        itemModalTitle.textContent = name;
        itemModalDesc.textContent = desc;
        itemModalDesc.hidden = !desc;
        itemModalPrice.textContent = priceDisplay;

        itemModal.classList.add('open');
        itemModal.setAttribute('aria-hidden', 'false');
        history.pushState({ [ITEM_MODAL_STATE_KEY]: true }, '', window.location.href);
    }

    window.addEventListener('popstate', function () {
        if (itemModal && itemModal.classList.contains('open')) {
            closeItemModalUiOnly();
        }
    });

    itemCards.forEach(function (card) {
        card.addEventListener('click', function () {
            openItemModal(this);
        });
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openItemModal(this);
            }
        });
    });

    if (itemModalClose) {
        itemModalClose.addEventListener('click', function (event) {
            event.stopPropagation();
            closeItemModalWithHistory();
        });
    }

    if (itemModalBack) {
        itemModalBack.addEventListener('click', function (event) {
            event.stopPropagation();
            closeItemModalWithHistory();
        });
    }

    if (itemModalStage) {
        itemModalStage.addEventListener('click', function (event) {
            if (event.target === itemModalStage) {
                closeItemModalWithHistory();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (shareSheet && shareSheet.classList.contains('open')) {
            event.preventDefault();
            closeShareSheet();
            return;
        }
        if (itemModal && itemModal.classList.contains('open')) {
            event.preventDefault();
            closeItemModalWithHistory();
        }
    });
});
