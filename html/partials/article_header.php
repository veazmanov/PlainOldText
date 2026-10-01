<!-- ./html/partials/article_header.php -->
<div class="source-panel sans">
    <p class="source-line">
        <em><?= htmlspecialchars($pub_name) ?></em>
        &middot; <?= htmlspecialchars($date_str) ?>
        <?php if (!empty($page_num)): ?>
            &middot; p.&nbsp;<?= htmlspecialchars($page_num) ?>
        <?php endif; ?>
    </p>

    <details class="source-tools" id="source-tools">
        <summary>Scans, plain text &amp; source</summary>
        <ul class="source-links">
            <li>
                <a href="<?= htmlspecialchars($photo_link) ?>" target="_blank" rel="noopener">Original scans</a>
            </li>
            <li>
                <a href="<?= htmlspecialchars($plaintext_url) ?>" target="_blank" rel="noopener">View/edit plain text</a>
            </li>
            <?php if (!empty($source_url)): ?>
                <li>
                    <a href="<?= htmlspecialchars($source_url) ?>" target="_blank" rel="noopener">Go to source</a>
                </li>
            <?php endif; ?>
        </ul>
    </details>

    <?php if (!empty($summary)): ?>
        <details class="source-summary">
            <summary>Summary</summary>
            <p><?= htmlspecialchars($summary) ?></p>
        </details>
    <?php endif; ?>
</div>

<script>
    // On desktop, show the links as a quiet inline row; on mobile, keep them collapsed.
    (function () {
        var tools = document.getElementById('source-tools');
        if (!tools || !window.matchMedia) return;
        var desktop = window.matchMedia('(min-width: 761px)');
        var sync = function () { tools.open = desktop.matches; };
        sync();
        if (desktop.addEventListener) desktop.addEventListener('change', sync);
    })();
</script>

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

    .source-panel .source-line {
        text-align: center;
    }

    .source-panel details {
        margin: 0.3rem 0;
    }

    .source-panel summary {
        cursor: pointer;
        list-style: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: #777;
    }

    .source-panel summary::-webkit-details-marker {
        display: none;
    }

    .source-panel summary::before {
        content: "\25B8";
        font-size: 0.7rem;
        display: inline-block;
        transition: transform 0.15s ease;
    }

    .source-panel details[open] > summary::before {
        transform: rotate(90deg);
    }

    .source-panel summary:hover {
        color: #111;
    }

    .source-links {
        list-style: none;
        margin: 0.4rem 0 0;
        padding: 0;
    }

    .source-links li {
        margin: 0.25rem 0;
        padding: 0;
    }

    .source-panel a {
        color: #555;
    }

    .source-panel a:hover {
        color: #111;
    }

    .source-summary p {
        color: #444;
        font-style: italic;
        margin-top: 0.4rem;
    }

    /* Mobile: centre the toggles under the centred title */
    @media (max-width: 760px) {
        .source-panel {
            text-align: center;
        }

        .source-links li {
            display: block;
        }
    }

    /* Desktop: links become one quiet inline row, no toggle */
    @media (min-width: 761px) {
        .source-panel .source-line {
            text-align: left;
        }

        .source-tools[open] > summary {
            display: none;
        }

        .source-links {
            margin: 0;
        }

        .source-links li {
            display: inline;
        }

        .source-links li + li::before {
            content: "\00B7";
            margin: 0 0.5rem;
            color: #bbb;
        }
    }
</style>
