// ./html/js/theme.js
// The light/dark toggle, pinned to the bottom-left corner of the window. The page follows the system setting until the reader picks a theme,
// which is then kept in <html data-theme>, a cookie for the whole of vjbe.net (so both of the site's domains share it)
// and localStorage, and read back in head.php before the page draws.
(function () {
    var button = document.getElementById('theme-toggle');
    var root = document.documentElement;
    var system = window.matchMedia('(prefers-color-scheme: dark)');

    function current() {
        return root.dataset.theme || (system.matches ? 'dark' : 'light');
    }

    function label() {
        var next = current() === 'dark' ? 'light' : 'dark';
        button.setAttribute('aria-label', 'Switch to ' + next + ' mode');
        button.title = 'Switch to ' + next + ' mode';
    }

    function remember(theme) {
        var domain = /(^|\.)vjbe\.net$/.test(location.hostname) ? '; domain=vjbe.net' : '';
        document.cookie = 'pot-theme=' + theme + '; path=/; max-age=31536000; SameSite=Lax' + domain;
        try {
            localStorage.setItem('pot-theme', theme);
        } catch (e) {}
    }

    button.addEventListener('click', function () {
        var next = current() === 'dark' ? 'light' : 'dark';
        root.dataset.theme = next;
        remember(next);
        label();
    });

    system.addEventListener('change', label);
    label();
    document.getElementById('theme-toggle-item').hidden = false;
})();
