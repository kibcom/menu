/* Major fix: responsive navbar toggle for small screens */
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('.landing-nav__toggle');
    const menu = document.querySelector('.landing-menu');
    if (!toggle || !menu) {
        return;
    }

    const setOpen = function (open) {
        menu.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', function () {
        const isOpen = menu.classList.contains('is-open');
        setOpen(!isOpen);
    });

    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                setOpen(false);
            }
        });
    });

    document.addEventListener('click', function (event) {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }
        if (!menu.contains(target) && !toggle.contains(target) && menu.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768 && menu.classList.contains('is-open')) {
            setOpen(false);
        }
    });
});
