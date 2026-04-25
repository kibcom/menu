document.addEventListener('DOMContentLoaded', function () {
    var layout = document.querySelector('.admin-layout');
    var toggle = document.querySelector('.admin-mobile-toggle');
    var overlay = document.querySelector('.admin-sidebar-overlay');
    var sidebar = document.querySelector('.admin-sidebar');
    if (!layout || !toggle || !overlay || !sidebar) {
        return;
    }

    var setOpen = function (open) {
        layout.classList.toggle('admin-menu-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    };

    toggle.addEventListener('click', function () {
        var isOpen = layout.classList.contains('admin-menu-open');
        setOpen(!isOpen);
    });

    overlay.addEventListener('click', function () {
        setOpen(false);
    });

    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) {
                setOpen(false);
            }
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) {
            setOpen(false);
        }
    });
});
