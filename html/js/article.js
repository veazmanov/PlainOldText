// ./html/js/article.js
// On wide screens, "Original scans" opens the scans beside the text, and keeps them roughly in step with it.
// The URL follows along (/<slug> and /<slug>/scans). On narrow screens /<slug>/scans is its own page.
(function () {
    var toggle = document.getElementById('scans-toggle');
    var panel = document.getElementById('scan-panel');
    var close = document.getElementById('scans-close');
    var wide = window.matchMedia('(min-width: 1000px)');
    var offset = 0;
    var syncing = false;
    var syncTimer = null;
    var textUrl = location.pathname.replace(/\/scans\/?$/, '');

    function show(open) {
        panel.hidden = !open;
        document.body.classList.toggle('show-scans', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.textContent = open ? 'Hide scans' : 'Original scans';
        toggle.setAttribute('href', open ? textUrl : textUrl + '/scans');
        history.replaceState(null, '', (open ? textUrl + '/scans' : textUrl) + location.search + location.hash);
        if (open) {
            // Start the scans at the same point as the text, re-aligning as they load.
            offset = 0;
            align();
            panel.querySelectorAll('img').forEach(function (img) {
                img.loading = 'eager';
                if (!img.complete) img.addEventListener('load', function () { if (offset === 0) align(); }, { once: true });
            });
        }
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
        show(panel.hidden);
    });
    close.addEventListener('click', function () { show(false); });

    // Opened at /<slug>/scans on a wide screen: line the scans up with the text.
    if (!panel.hidden && wide.matches) show(true);

    // Coarse scroll sync: the scans follow the text proportionally, but only once they
    // drift more than a third of a screen from where they should be, so small scrolls
    // in the text leave the scans still. Scrolling the scans by hand sets an offset
    // that later syncing keeps.

    function target() {
        var textMax = document.documentElement.scrollHeight - window.innerHeight;
        var panelMax = panel.scrollHeight - panel.clientHeight;
        var fraction = textMax > 0 ? window.scrollY / textMax : 0;
        return Math.min(panelMax, Math.max(0, fraction * panelMax + offset));
    }

    window.addEventListener('scroll', function () {
        if (panel.hidden || !wide.matches) return;
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
