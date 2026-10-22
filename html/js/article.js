// ./html/js/article.js
// The original scans sit beside the text on wide screens. They open there by default (body.scans-auto,
// at /stable/<id>), unless this reader closed them before; "Original scans" / "Hide scans" and the
// panel's Close button toggle them, and the URL follows (/stable/<id>/scans when opened by hand).
// The scans keep roughly in step with the text. On narrow screens /stable/<id>/scans is its own page.
(function () {
    var toggle = document.getElementById('scans-toggle');
    var panel = document.getElementById('scan-panel');
    var close = document.getElementById('scans-close');
    var body = document.body;
    var wide = window.matchMedia('(min-width: 1000px)');
    var offset = 0;
    var syncing = false;
    var syncTimer = null;
    var textUrl = location.pathname.replace(/\/scans\/?$/, '');

    function isOpen() {
        return body.classList.contains('show-scans') || (body.classList.contains('scans-auto') && wide.matches);
    }

    function remember(open) {
        try {
            if (open) localStorage.removeItem('pot-scans');
            else localStorage.setItem('pot-scans', 'hidden');
        } catch (e) {}
    }

    // Reflect the state in the button, the URL (unless opened by default) and the edition links
    function show(open, byDefault) {
        body.classList.remove('scans-auto');
        body.classList.toggle('show-scans', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.textContent = open ? 'Hide scans' : 'Original scans';
        toggle.setAttribute('href', open ? textUrl : textUrl + '/scans');
        var path = open && !byDefault ? textUrl + '/scans' : textUrl;
        history.replaceState(null, '', path + location.search + location.hash);
        document.querySelectorAll('.edition-switch a').forEach(function (a) {
            var url = new URL(a.href);
            url.pathname = path;
            a.href = url.toString();
        });
        place();
        if (open) {
            // Start the scans at the same point as the text, re-aligning as they load.
            offset = 0;
            align();
            panel.querySelectorAll('iframe[data-src]').forEach(function (frame) {
                frame.src = frame.getAttribute('data-src');
                frame.removeAttribute('data-src');
            });
            panel.querySelectorAll('img').forEach(function (img) {
                if (!img.complete) img.addEventListener('load', function () { if (offset === 0) align(); }, { once: true });
            });
        }
    }

    // On wide screens the panel starts a little above the text, below the title, and rises
    // with the page until it reaches the top of the window, at this scroll position
    // (the text is looked up each time: this script runs before it is parsed)
    function pinnedAt() {
        var text = document.querySelector('article > section:not(.article-head)');
        if (!text) return 0;
        return Math.max(0, text.getBoundingClientRect().top + window.scrollY - 2 * 96 / 2.54); // 2cm higher
    }

    function place() {
        var top = isOpen() && wide.matches ? Math.max(0, pinnedAt() - window.scrollY) : 0;
        body.style.setProperty('--scan-top', top + 'px');
    }

    function align() {
        syncing = true;
        panel.scrollTop = target();
        clearTimeout(syncTimer);
        syncTimer = setTimeout(function () { syncing = false; }, 100);
    }

    toggle.addEventListener('click', function (e) {
        if (!wide.matches) return; // narrow screens follow the link to the scans page
        e.preventDefault();
        var open = !isOpen();
        remember(open);
        show(open);
    });
    close.addEventListener('click', function () {
        if (wide.matches) remember(false);
        show(false);
    });

    // Open by default (scans-auto) or at /stable/<id>/scans on a wide screen: line the scans up with the text.
    if (wide.matches && body.classList.contains('scans-auto')) show(true, true);
    else if (wide.matches && body.classList.contains('show-scans')) show(true);

    // Coarse scroll sync: once the panel reaches the top of the window, the scans follow
    // the rest of the text proportionally, but only once they drift more than a third of
    // a screen from where they should be, so small scrolls in the text leave the scans
    // still. Scrolling the scans by hand sets an offset that later syncing keeps.

    function target() {
        var start = pinnedAt();
        var textMax = document.documentElement.scrollHeight - window.innerHeight - start;
        var panelMax = panel.scrollHeight - panel.clientHeight;
        var fraction = textMax > 0 ? Math.max(0, window.scrollY - start) / textMax : 0;
        return Math.min(panelMax, Math.max(0, fraction * panelMax + offset));
    }

    window.addEventListener('resize', place);
    window.addEventListener('load', place);

    window.addEventListener('scroll', function () {
        place();
        if (!isOpen() || !wide.matches) return;
        var to = target();
        if (Math.abs(panel.scrollTop - to) < panel.clientHeight / 3) return;
        syncing = true;
        panel.scrollTo({ top: to, behavior: 'smooth' });
        clearTimeout(syncTimer);
        syncTimer = setTimeout(function () { syncing = false; }, 800);
    }, { passive: true });

    panel.addEventListener('scroll', function () {
        if (syncing) return;
        offset += panel.scrollTop - target();
    }, { passive: true });
})();
