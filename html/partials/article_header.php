<!-- ./html/partials/article_header.php -->
<div class="source-panel sans">
    <p class="source-line">
        <em><?= htmlspecialchars($pub_name) ?></em>
        &ndash; <?= htmlspecialchars($date_str) ?>
        <?php if (!empty($page_num)): ?>
            &middot; p.&nbsp;<?= htmlspecialchars($page_num) ?>
        <?php endif; ?>
    </p>

    <?php if (!empty($summary)): ?>
        <p class="source-summary"><?= htmlspecialchars($summary) ?></p>
    <?php endif; ?>

    <ul class="source-links">
        <li>
            <a href="<?= htmlspecialchars($photo_link) ?>" id="scans-toggle" target="_blank" rel="noopener"
                aria-controls="scan-panel" aria-expanded="false">Original scans</a>
        </li>
        <?php if (!empty($markdown_url)): ?>
            <li>
                <a href="<?= htmlspecialchars($markdown_url) ?>">View as markdown</a>
            </li>
        <?php endif; ?>
        <li>
            <a href="<?= htmlspecialchars($github_url) ?>" target="_blank" rel="noopener">Edit on GitHub</a>
        </li>
        <?php if (!empty($source_url)): ?>
            <li>
                <a href="<?= htmlspecialchars($source_url) ?>" target="_blank" rel="noopener">Go to source</a>
            </li>
        <?php endif; ?>
    </ul>
</div>

<?php if (!empty($scans)): ?>
    <aside class="scan-panel sans" id="scan-panel" hidden aria-label="Original scans">
        <p class="scan-panel-bar">
            <a href="<?= htmlspecialchars($photo_link) ?>" target="_blank" rel="noopener">Open in new tab</a>
            <button type="button" id="scans-close">Close &times;</button>
        </p>
        <?php foreach ($scans as $i => $scan): ?>
            <img src="<?= htmlspecialchars($scan) ?>" alt="Original scan, page <?= $i + 1 ?>" loading="lazy" decoding="async">
        <?php endforeach; ?>
    </aside>

    <script>
        // On wide screens, "Original scans" opens the scans beside the text instead of a new tab.
        (function () {
            var toggle = document.getElementById('scans-toggle');
            var panel = document.getElementById('scan-panel');
            var close = document.getElementById('scans-close');
            var wide = window.matchMedia('(min-width: 1000px)');

            function show(open) {
                panel.hidden = !open;
                document.body.classList.toggle('show-scans', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.textContent = open ? 'Hide scans' : 'Original scans';
            }

            toggle.addEventListener('click', function (e) {
                if (!wide.matches) return; // narrow screens follow the link to the scans page
                e.preventDefault();
                show(panel.hidden);
            });
            close.addEventListener('click', function () { show(false); });
            wide.addEventListener('change', function (mq) { if (!mq.matches) show(false); });
        })();
    </script>
<?php endif; ?>

<style>
    /* ---- Newspaper styling for the article body ---- */
    article p {
        text-indent: 1.5em;
    }

    .centered {
        text-align: center;
    }

    section>h1,
    section>h2,
    section>h3 {
        max-width: 500px;
        width: 100%;
        text-align: center;
        font-style: normal;
    }

    h1 { font-size: 2rem; }
    h2 { font-size: 1.8rem; }
    h3 { font-size: 1.6rem; }

    p {
        max-width: 500px;
        width: 100%;
        text-align: justify;
        font-kerning: none;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }

    section>p {
        width: 100%;
    }

    @media (max-width: 760px) {
        section>h1,
        section>h2,
        section>h3 {
            max-width: 100%;
        }
    }

    /* ---- Quieter site header on article pages, so the article title leads ---- */
    body > header > h1 {
        font-size: 1.3rem;
        margin-top: 2rem;
        margin-bottom: 0;
    }

    body > header > .subtitle {
        font-size: 0.95rem;
        margin-top: 0.2rem;
    }

    body > header .site-nav {
        margin-bottom: 1.5rem;
    }

    /* Keep the source panel close under the nav */
    body > article {
        padding-top: 0.5rem;
    }

    /* ---- Article title ---- */
    .article-head > h1,
    .article-head > h2,
    .article-head > h3 {
        font-size: 2.3rem;
        line-height: 1.15;
        margin-top: 0;
        margin-bottom: 2rem;
    }

    /* ---- Source panel: small, muted, sits above the title ---- */
    .source-panel {
        max-width: 500px;
        margin-bottom: 2.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #eee;
        font-size: 0.85rem;
        color: #777;
    }

    .source-panel p {
        text-indent: 0;
        text-align: left;
        font-size: 0.85rem;
        line-height: 1.5;
        letter-spacing: 0;
        font-kerning: auto;
        margin: 0.3rem 0;
    }

    .source-panel .source-summary {
        color: #444;
        font-style: italic;
        margin: 0.5rem 0;
    }

    .source-links {
        list-style: none;
        width: auto;
        margin: 0.5rem 0 0;
        padding: 0;
        font-size: 0.85rem;
        line-height: 1.5;
    }

    .source-links li {
        display: inline;
        margin: 0;
        padding: 0;
        font-size: inherit;
    }

    .source-links li + li::before {
        content: "\00B7";
        margin: 0 0.5rem;
        color: #bbb;
    }

    .source-panel a {
        color: #555;
    }

    .source-panel a:hover {
        color: #111;
    }

    @media (max-width: 760px) {
        .source-panel,
        .source-panel p {
            text-align: center;
        }
    }

    /* ---- Scans beside the text (wide screens, toggled by "Original scans") ---- */
    body.show-scans {
        width: 46vw;
        max-width: none;
        margin-left: 0;
        padding-left: 3vw;
    }

    .scan-panel {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: 48vw;
        overflow-y: auto;
        padding: 0 1.5rem 2rem;
        box-sizing: border-box;
        background: #f4f1e8;
        border-left: 1px solid #ddd;
        z-index: 10;
    }

    .scan-panel-bar {
        position: sticky;
        top: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: none;
        margin: 0 -1.5rem 1rem;
        padding: 0.6rem 1.5rem;
        background: #f4f1e8;
        border-bottom: 1px solid #ddd;
        font-size: 0.85rem;
        text-indent: 0;
    }

    .scan-panel-bar button {
        font: inherit;
        color: #555;
        background: none;
        border: 0;
        cursor: pointer;
    }

    .scan-panel-bar button:hover {
        color: #111;
    }

    .scan-panel img {
        display: block;
        width: 100%;
        margin: 0 0 1.5rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
    }
</style>
