// ./html/js/figures.js
// Diagrams in the text: a paragraph holding nothing but an image. On wide screens with room to the right of the text,
// the diagram for the passage being read is shown in a panel fixed beside the text (body.figures-docked), so it stays
// in view while the text about it is read; each diagram takes over from the one before once its place in the text
// scrolls up past the middle of the window, and in the text it leaves a small marker. Where there is no room, or the
// scans or the side-by-side translation fill the page, diagrams stay in the text; on phones each is folded behind a
// button that shows it. Photographs of printed tables (in a .figure-pair beside their transcription) are left alone.
(function () {
    var figures = Array.prototype.filter.call(
        document.querySelectorAll('article > section:not(.article-head) p > img:only-child'),
        function (img) { return !img.closest('.scan-panel, .figure-pair') && img.parentNode.textContent.trim() === ''; }
    ).map(function (img) { return img.parentNode; });
    if (!figures.length) return;

    var body = document.body;
    var wide = window.matchMedia('(min-width: 1000px)');
    var phone = window.matchMedia('(max-width: 760px)');
    var current = null;
    var ticking = false;

    // "Schema XIV" from an alt text such as "Schema XIV: a tall rectangle…", or plain "Diagram"
    function label(img) {
        var m = (img.getAttribute('alt') || '').match(/^Schema [IVXLC]+/);
        return m ? m[0] : 'Diagram';
    }

    // The dock, and in the text a marker and (on phones) a button for each diagram
    var dock = document.createElement('aside');
    dock.className = 'figure-dock sans';
    dock.setAttribute('aria-hidden', 'true'); // the same images are in the text for screen readers
    dock.innerHTML = '<a class="figure-dock-link" target="_blank" rel="noopener"><img alt=""></a><p class="figure-dock-label"></p>';
    body.appendChild(dock);
    var dockLink = dock.querySelector('a');
    var dockImg = dock.querySelector('img');
    var dockLabel = dock.querySelector('.figure-dock-label');

    figures.forEach(function (p) {
        var img = p.querySelector('img');
        var name = label(img);
        p.classList.add('figure');

        var marker = document.createElement('span');
        marker.className = 'figure-marker sans';
        marker.textContent = name + ' →'; // "Schema I →": shown beside the text
        p.insertBefore(marker, img);

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'figure-toggle sans';
        button.setAttribute('aria-expanded', 'false');
        button.textContent = 'Show ' + name;
        button.addEventListener('click', function () {
            var open = !p.classList.contains('figure-open');
            p.classList.toggle('figure-open', open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            button.textContent = (open ? 'Hide ' : 'Show ') + name;
        });
        p.insertBefore(button, img);
    });

    function text() {
        return document.querySelector('article > section:not(.article-head) > p:not(.figure)') ||
            document.querySelector('article > section:not(.article-head) p:not(.figure)');
    }

    // Dock where the screen is wide, nothing else fills the right of the page, and there is room beside the text
    function dockable() {
        if (!wide.matches) return false;
        if (body.classList.contains('show-translation')) return false;
        if (body.classList.contains('show-scans')) return false;
        if (body.classList.contains('scans-auto') && document.getElementById('scan-panel')) return false;
        if (document.querySelector('article .sidenote')) return false; // the margin is taken by the editor's notes
        var t = text();
        return !!t && window.innerWidth - t.getBoundingClientRect().right >= 360;
    }

    function layout() {
        var on = dockable();
        body.classList.toggle('figures-docked', on);
        body.classList.toggle('figures-folded', !on && phone.matches);
        if (on) {
            var right = text().getBoundingClientRect().right;
            var gap = 48;
            body.style.setProperty('--figure-dock-left', (right + gap) + 'px');
            body.style.setProperty('--figure-dock-width', Math.min(window.innerWidth - right - 2 * gap, 640) + 'px');
        }
        update();
    }

    // The current diagram: the last whose place in the text is above the middle of the window
    function update() {
        ticking = false;
        if (!body.classList.contains('figures-docked')) return;
        var line = window.innerHeight / 2;
        var next = null;
        figures.forEach(function (p) {
            if (p.getBoundingClientRect().top < line) next = p;
        });
        if (next === current) return;
        if (current) current.classList.remove('figure-current');
        current = next;
        dock.classList.toggle('figure-dock-empty', !current);
        if (!current) return;
        current.classList.add('figure-current');
        var img = current.querySelector('img');
        dockImg.src = img.currentSrc || img.src;
        dockLink.href = img.src;
        dockLabel.textContent = label(img);
        dock.title = img.getAttribute('alt') || '';
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });
    window.addEventListener('resize', layout);
    window.addEventListener('load', layout);
    new MutationObserver(layout).observe(body, { attributes: true, attributeFilter: ['class'] });
    layout();
})();
