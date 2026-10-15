<?php
$sort = $_GET['sort'] ?? 'newest';
$query = trim($_GET['q'] ?? '');
usort($links, function ($a, $b) use ($sort) {
    $timeA = strtotime($a['sort_date']);
    $timeB = strtotime($b['sort_date']);
    return $sort === 'oldest' ? $timeA <=> $timeB : $timeB <=> $timeA;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archive Catalogue | <?= htmlspecialchars($site_name) ?></title>
    <link rel="stylesheet" href="https://oldnews.vjbe.net/style/tufte.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --paper: #fdfaef;
            --paper-edge: #e7dfc9;
            --ink: #1a1712;
            --ink-muted: #6b6455;
            --accent: #8a2b1d;
        }

        /* ---- Toolbar: search, sort, count ---- */
        .catalogue-tools {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem 2rem;
            margin-bottom: 2rem;
            padding-right: 12.5%;
        }
        .search-field {
            position: relative;
            flex: 1 1 22rem;
            max-width: 32rem;
        }
        .search-field label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--ink-muted);
            margin-bottom: 0.35rem;
        }
        .search-field input {
            width: 100%;
            font: inherit;
            font-family: Palatino, "Palatino Linotype", "Book Antiqua", Georgia, serif;
            font-size: 1.25rem;
            color: var(--ink);
            background: transparent;
            border: 0;
            border-bottom: 2px solid var(--ink);
            border-radius: 0;
            padding: 0.35rem 2rem 0.35rem 0;
            outline: none;
            -webkit-appearance: none;
            appearance: none;
        }
        .search-field input::placeholder {
            color: #aaa294;
            font-style: italic;
        }
        .search-field input:focus {
            border-bottom-color: var(--accent);
        }
        .search-field input::-webkit-search-cancel-button {
            cursor: pointer;
        }
        .search-field kbd {
            position: absolute;
            right: 0;
            bottom: 0.55rem;
            font-family: inherit;
            font-size: 0.75rem;
            color: var(--ink-muted);
            border: 1px solid var(--paper-edge);
            border-radius: 3px;
            padding: 0 0.35rem;
            pointer-events: none;
        }
        .search-field input:focus ~ kbd,
        .search-field input:not(:placeholder-shown) ~ kbd {
            display: none;
        }
        .sort-controls {
            font-size: 0.9rem;
            color: var(--ink-muted);
            white-space: nowrap;
        }
        .sort-link {
            text-decoration: none;
            color: #0066cc;
        }
        .sort-link.active {
            color: var(--ink);
            font-weight: bold;
            pointer-events: none;
        }
        .result-count {
            flex-basis: 100%;
            margin: 0;
            font-size: 0.85rem;
            color: var(--ink-muted);
        }

        /* ---- Card grid ---- */
        section > ul.clip-grid {
            width: auto;
            padding: 0;
            margin: 0 12.5% 0 0;
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 19rem), 1fr));
            grid-auto-flow: dense;
            gap: 2rem 1.75rem;
        }
        .clip-grid > li {
            margin: 0;
        }
        .clip-grid > li[hidden] {
            display: none;
        }

        /* ---- A card is a newspaper clipping ---- */
        .clip {
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 1.1rem 1.3rem 0.9rem;
            background: var(--paper);
            border: 1px solid var(--paper-edge);
            box-shadow:
                0 1px 0 rgba(60, 45, 10, 0.05),
                0 10px 24px -18px rgba(60, 45, 10, 0.45);
            color: var(--ink);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .clip:hover,
        .clip:focus-within {
            transform: translateY(-3px);
            box-shadow:
                0 1px 0 rgba(60, 45, 10, 0.05),
                0 18px 30px -18px rgba(60, 45, 10, 0.55);
        }
        .clip:focus-within {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }

        .clip-masthead {
            margin: 0;
            padding: 0.3rem 0 0.25rem;
            border-top: 1px solid var(--ink);
            border-bottom: 3px double var(--ink);
            text-align: center;
            font-size: 1.05rem;
            line-height: 1.3;
            font-variant: small-caps;
            letter-spacing: 0.08em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .clip-dateline {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin: 0;
            padding: 0.25rem 0;
            border-bottom: 1px solid var(--ink);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--ink-muted);
        }

        .clip-headline {
            margin: 1rem 0 0;
            font-size: 1.45rem;
            font-weight: 700;
            font-style: normal;
            line-height: 1.15;
            text-align: center;
            text-wrap: balance;
        }
        .clip-headline a {
            text-decoration: none;
            color: inherit;
            outline: none;
        }
        /* Whole card is clickable via the headline link */
        .clip-headline a::after {
            content: "";
            position: absolute;
            inset: 0;
        }
        .clip:hover .clip-headline a {
            text-decoration: underline;
            text-decoration-thickness: 1px;
            text-underline-offset: 0.15em;
        }

        .clip-dinkus {
            width: 2.5rem;
            height: 0;
            margin: 0.75rem auto 0.85rem;
            border: 0;
            border-top: 1px solid var(--ink);
        }

        .clip-body {
            flex: 1;
            margin: 0;
            max-height: 11.5em;
            overflow: hidden;
            font-size: 0.98rem;
            line-height: 1.45;
            text-align: justify;
            hyphens: auto;
            -webkit-mask-image: linear-gradient(to bottom, #000 65%, transparent);
            mask-image: linear-gradient(to bottom, #000 65%, transparent);
        }
        .clip-body::first-letter {
            float: left;
            font-size: 3.1em;
            line-height: 0.85;
            padding: 0.08em 0.08em 0 0;
            font-weight: 700;
            color: var(--accent);
        }
        .clip-body.is-summary {
            font-style: italic;
            color: #4a4438;
        }
        .clip-body.is-summary::first-letter {
            float: none;
            font-size: inherit;
            line-height: inherit;
            padding: 0;
            font-weight: inherit;
            color: inherit;
        }

        .clip-foot {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 1rem;
            margin: 0.75rem 0 0;
            padding-top: 0.5rem;
            border-top: 1px solid var(--paper-edge);
            font-size: 0.78rem;
            color: var(--ink-muted);
        }
        .clip-foot .read-more {
            color: var(--accent);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .pending-tag {
            font-style: italic;
        }

        /* Featured pieces get a double-width front-page slot */
        @media (min-width: 900px) {
            .clip-grid > li.is-featured {
                grid-column: span 2;
            }
            .is-featured .clip-headline {
                font-size: 1.9rem;
            }
            .is-featured .clip-body {
                columns: 2;
                column-gap: 1.75rem;
                column-rule: 1px solid var(--paper-edge);
                max-height: 13em;
            }
        }

        .no-results {
            width: auto;
            margin: 3rem 12.5% 0 0;
            text-align: center;
            font-style: italic;
            color: var(--ink-muted);
        }

        @media (max-width: 760px) {
            .catalogue-tools,
            section > ul.clip-grid,
            .no-results {
                padding-right: 0;
                margin-right: 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .clip {
                transition: none;
            }
            .clip:hover,
            .clip:focus-within {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <?php include __DIR__ . '/partials/topnav.php'; ?>
    </header>
    <article>
        <section>
            <form class="sans catalogue-tools" role="search" action="/catalogue" method="get" onsubmit="return false;">
                <div class="search-field">
                    <label for="catalogue-search">Search the archive</label>
                    <input id="catalogue-search" type="search" name="q" autocomplete="off"
                        placeholder="Titles, newspapers, dates, text…"
                        value="<?= htmlspecialchars($query) ?>">
                    <kbd aria-hidden="true">/</kbd>
                </div>
                <div class="sort-controls">
                    Sort by:
                    <a href="?sort=newest" class="sort-link <?= $sort === 'newest' ? 'active' : '' ?>" data-sort="newest">Newest</a> |
                    <a href="?sort=oldest" class="sort-link <?= $sort === 'oldest' ? 'active' : '' ?>" data-sort="oldest">Oldest</a>
                </div>
                <p class="result-count" id="result-count" aria-live="polite">
                    <?= number_format(count($links)) ?> articles
                </p>
            </form>

            <ul class="clip-grid" id="clip-grid">
                <?php foreach ($links as $article):
                    $search_text = implode(' ', [
                        $article['title'],
                        $article['pub'],
                        $article['date'],
                        $article['summary'] ?? '',
                        $article['excerpt'] ?? '',
                    ]);
                    $minutes = max(1, (int) round($article['words'] / 220));
                    $body = $article['excerpt'] ?: $article['summary'];
                ?>
                    <li class="<?= !empty($article['featured']) ? 'is-featured' : '' ?>"
                        data-search="<?= htmlspecialchars($search_text) ?>">
                        <div class="clip">
                            <p class="clip-masthead"><?= htmlspecialchars($article['pub']) ?></p>
                            <p class="clip-dateline sans">
                                <span><?= htmlspecialchars($article['date']) ?></span>
                                <?php if ($article['words'] > 0): ?>
                                    <span><?= number_format($article['words']) ?> words</span>
                                <?php endif; ?>
                            </p>

                            <h2 class="clip-headline">
                                <a href="/<?= htmlspecialchars($article['uri']) ?>"
                                    <?php if (!empty($article['summary'])): ?>title="<?= htmlspecialchars($article['summary']) ?>"<?php endif; ?>>
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h2>
                            <hr class="clip-dinkus">

                            <?php if (!empty($body)): ?>
                                <p class="clip-body <?= empty($article['excerpt']) ? 'is-summary' : '' ?>">
                                    <?= htmlspecialchars($body) ?>
                                </p>
                            <?php else: ?>
                                <div class="clip-body"></div>
                            <?php endif; ?>

                            <p class="clip-foot sans">
                                <?php if ($article['words'] > 0): ?>
                                    <span><?= $minutes ?> min read</span>
                                <?php else: ?>
                                    <span class="pending-tag">Transcription pending</span>
                                <?php endif; ?>
                                <span class="read-more" aria-hidden="true">Read &rarr;</span>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="no-results" id="no-results" hidden>No articles match your search.</p>
        </section>
    </article>

    <script>
        (function () {
            var input = document.getElementById('catalogue-search');
            var items = Array.prototype.slice.call(document.querySelectorAll('#clip-grid > li'));
            var count = document.getElementById('result-count');
            var empty = document.getElementById('no-results');
            var sortLinks = document.querySelectorAll('.sort-link');
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
                    var match = terms.every(function (t) { return li._haystack.indexOf(t) !== -1; });
                    li.hidden = !match;
                    if (match) shown++;
                });

                count.textContent = terms.length
                    ? shown + ' of ' + total + ' articles'
                    : total + ' articles';
                empty.hidden = shown !== 0;

                // Keep the query in the URL and across sort changes
                var params = new URLSearchParams(location.search);
                if (q) params.set('q', q); else params.delete('q');
                var qs = params.toString();
                history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));

                sortLinks.forEach(function (a) {
                    var p = new URLSearchParams({ sort: a.getAttribute('data-sort') });
                    if (q) p.set('q', q);
                    a.setAttribute('href', '?' + p.toString());
                });
            }

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
    </script>
</body>
</html>
