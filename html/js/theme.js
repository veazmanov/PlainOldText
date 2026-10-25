// ./html/js/theme.js
// The light/dark toggle at the end of the top nav. The page follows the system setting until the reader picks the other theme,
// which is kept in <html data-theme> and localStorage (read back in head.php); picking the system's own theme
// again forgets the choice, so the page follows the system once more.
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

    button.addEventListener('click', function () {
        var next = current() === 'dark' ? 'light' : 'dark';
        try {
            if (next === (system.matches ? 'dark' : 'light')) {
                delete root.dataset.theme;
                localStorage.removeItem('pot-theme');
            } else {
                root.dataset.theme = next;
                localStorage.setItem('pot-theme', next);
            }
        } catch (e) {
            root.dataset.theme = next;
        }
        label();
    });

    system.addEventListener('change', label);
    label();
    document.getElementById('theme-toggle-item').hidden = false;
})();
