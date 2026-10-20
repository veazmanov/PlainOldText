// ./html/js/catalogue.js
// Catalogue search, the Filters toggle, category filter and sort links; keeps the query and category in the URL.
(function () {
    var input = document.getElementById('catalogue-search');
    var items = Array.prototype.slice.call(document.querySelectorAll('#clip-grid > li'));
    var count = document.getElementById('result-count');
    var empty = document.getElementById('no-results');
    var sortLinks = document.querySelectorAll('.sort-link');
    var categoryButtons = Array.prototype.slice.call(document.querySelectorAll('.category-filter button'));
    var filterToggle = document.getElementById('filter-toggle');
    var filterPanel = document.getElementById('filter-panel');
    var filterBadge = document.getElementById('filter-badge');
    var sort = new URLSearchParams(location.search).get('sort') || 'newest';
    var category = new URLSearchParams(location.search).get('category') || '';
    var total = items.length;

    var normalise = function (s) {
        return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    };
    items.forEach(function (li) {
        li._haystack = normalise(li.getAttribute('data-search') || '');
    });

    function apply() {
        var q = input.value.trim();
        var terms = normalise(q).split(/\s+/).filter(Boolean);
        var shown = 0;

        items.forEach(function (li) {
            var match = (!category || li.getAttribute('data-category') === category)
                && terms.every(function (t) { return li._haystack.indexOf(t) !== -1; });
            li.hidden = !match;
            if (match) shown++;
        });

        categoryButtons.forEach(function (b) {
            b.setAttribute('aria-pressed', b.getAttribute('data-category') === category ? 'true' : 'false');
        });

        // Badge on "Filters": how many filters differ from the default, so they show while collapsed
        var active = (category ? 1 : 0) + (sort === 'oldest' ? 1 : 0);
        filterBadge.textContent = active;
        filterBadge.hidden = active === 0;
        filterToggle.setAttribute('aria-label', active ? 'Filters, ' + active + ' active' : 'Filters');

        count.textContent = (terms.length || category)
            ? shown + ' of ' + total + ' items'
            : total + ' items';
        empty.hidden = shown !== 0;

        // Keep the query and category in the URL and across sort changes
        var params = new URLSearchParams(location.search);
        if (q) params.set('q', q); else params.delete('q');
        if (category) params.set('category', category); else params.delete('category');
        var qs = params.toString();
        history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));

        sortLinks.forEach(function (a) {
            var p = new URLSearchParams({ sort: a.getAttribute('data-sort') });
            if (q) p.set('q', q);
            if (category) p.set('category', category);
            a.setAttribute('href', '?' + p.toString());
        });
    }

    filterToggle.addEventListener('click', function () {
        var open = filterToggle.getAttribute('aria-expanded') !== 'true';
        filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        filterPanel.hidden = !open;
    });

    categoryButtons.forEach(function (b) {
        b.addEventListener('click', function () {
            category = b.getAttribute('data-category');
            apply();
        });
    });

    input.addEventListener('input', apply);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { input.value = ''; apply(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && document.activeElement !== input && !e.metaKey && !e.ctrlKey) {
            e.preventDefault();
            input.focus();
        }
    });

    apply();
})();
