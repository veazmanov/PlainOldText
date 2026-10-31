<?php
// ./html/search.php
// Full-text search over the built transcriptions (/search?q=). Each passage (a paragraph or heading) of every
// edition and translation is matched word by word, after a normalisation that forgives early modern spelling
// (search_norm), so "milk" finds "milke" and "unto" finds "vnto". Results are grouped by article, and each passage
// links into the article with a text fragment (#:~:text=), which scrolls to it and highlights it.

// A word reduced for matching: lower case, no accents, long s as s, u/v, i/j and i/y alike, doubled letters single
// and a final e dropped ("Milke", "milk" and "milkes" all begin "milk"; "verie" and "very" are both "ueri").
function search_norm(string $word): string
{
    $w = mb_strtolower($word);
    $w = strtr($w, ['ſ' => 's', 'ß' => 'ss', 'æ' => 'ae', 'œ' => 'oe', '&' => 'and']);
    if (class_exists('Normalizer')) {
        $w = preg_replace('/\p{Mn}+/u', '', Normalizer::normalize($w, Normalizer::FORM_D));
    }
    $w = preg_replace('/[^a-z0-9]/', '', $w);
    $w = strtr($w, 'vjy', 'uii');
    $w = preg_replace('/(.)\1+/', '$1', $w);
    $w = preg_replace('/gue(s?)$/', 'ge$1', $w); // plague, plage; tongue, tonge
    return strlen($w) > 3 ? preg_replace('/e$/', '', $w) : $w;
}

// The words of a text, with their byte offsets: [[word, offset], …]
function search_words(string $text): array
{
    preg_match_all('/[\p{L}\p{M}\p{N}ſ]+/u', $text, $m, PREG_OFFSET_CAPTURE);
    return $m[0];
}

// A Markdown passage as a reader sees it: no footnotes, link targets, escapes or emphasis marks
function search_plain(string $md): string
{
    // Footnotes ^[…], which may hold brackets of their own
    while (($i = strpos($md, '^[')) !== false) {
        for ($j = $i + 2, $depth = 1; $j < strlen($md) && $depth; $j++) {
            $depth += $md[$j] === '[' && $md[$j - 1] !== '\\' ? 1 : ($md[$j] === ']' && $md[$j - 1] !== '\\' ? -1 : 0);
        }
        $md = substr($md, 0, $i) . substr($md, $j);
    }
    $md = preg_replace('/\[\[([^\]]*)\]\([^)]*\)\]/u', '[$1]', $md);   // page markers: [[f. 12r](…)]
    $md = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $md);         // links
    $md = preg_replace('/^(#+|-|\d+\.)\s+/u', '', $md);                 // heading, list item
    $md = preg_replace('/\{[.#][^}]*\}/u', '', $md);                   // pandoc attributes
    $md = preg_replace('/(?<!\\\\)[*_^~]/u', '', $md);                  // emphasis, superscript
    $md = preg_replace('/\\\\(.)/u', '$1', $md);                        // escapes
    return trim(preg_replace('/\s+/u', ' ', $md));
}

// The searchable passages of one built .md file, each with the id of the heading it falls under (the ids pandoc
// gave the headings, read from the built HTML in order): [['text', 'norms' => its normalised words, space-separated, 'heading', 'anchor']]
function search_passages(string $md_file): array
{
    $html_file = substr($md_file, 0, -3) . '.html';
    preg_match_all('/<h[1-6][^>]*\sid="([^"]*)"/', is_file($html_file) ? file_get_contents($html_file) : '', $ids);
    $ids = $ids[1];

    $passages = [];
    $heading = null;
    $anchor = null;
    $in_note = false;
    $h = 0;
    foreach (preg_split('/\n\s*\n/', file_get_contents($md_file)) as $block) {
        $block = trim($block);
        // The editorial note at the top (::: {.editorial-note} … :::) is about the text, not part of it
        if ($in_note) {
            $in_note = !str_ends_with($block, ':::');
            continue;
        }
        if (str_starts_with($block, ':::')) {
            $in_note = !(substr_count($block, ':::') >= 2 && str_ends_with($block, ':::'));
            continue;
        }
        if ($block === '') {
            continue;
        }
        $text = search_plain($block);
        if (preg_match('/^#+\s/', $block)) {
            $anchor = $ids[$h++] ?? null;
            // The title (h1) is the article's own, not a section of it
            $heading = str_starts_with($block, '# ') ? null : $text;
            $anchor = $heading === null ? null : $anchor;
        }
        if ($text === '') {
            continue;
        }
        // The normalised words as one string, word for word with search_words($text): arrays of every word would
        // take far more memory, and the words are only needed again for the passages that match
        $passages[] = [
            'is_heading' => (bool) preg_match('/^#+\s/', $block),
            'text'    => $text,
            'norms'   => implode(' ', array_map(fn($w) => search_norm($w[0]), search_words($text))),
            'heading' => $heading,
            'anchor'  => $anchor,
        ];
    }
    return $passages;
}

// Every searchable text: [['slug', 'edition' (null for the default), 'translation' (bool), 'passages'], …].
// Parsing all of them takes a moment, so the result is cached in the temp directory.
function search_corpus(): array
{
    global $metadata, $articles_base;
    $files = [];
    foreach (array_keys($metadata) as $slug) {
        $editions = article_editions($slug);
        $default = array_key_first($editions);
        foreach ($editions as $id => $e) {
            if ($e['md']) {
                $files[] = [$slug, $id === $default ? null : $id, false, $e['md']];
            }
        }
        $tr = article_translation($slug);
        if ($tr && $tr['md']) {
            $files[] = [$slug, null, true, $tr['md']];
        }
    }

    // Rebuilt when a text or this file changes. The stamp is in the file name, so a stale cache is never loaded
    $stamp = md5(filemtime(__FILE__) . '|' . implode('|', array_map(fn($f) => $f[3] . ':' . filemtime($f[3]), $files)));
    $prefix = sys_get_temp_dir() . '/plainoldtext-search-' . md5($articles_base);
    $cache = $prefix . '-' . $stamp . '.ser';
    $cached = is_file($cache) ? @unserialize(file_get_contents($cache)) : null;
    if (is_array($cached)) {
        return $cached;
    }

    $corpus = [];
    foreach ($files as [$slug, $edition, $translation, $md]) {
        $corpus[] = ['slug' => $slug, 'edition' => $edition, 'translation' => $translation,
            'passages' => search_passages($md)];
    }
    foreach (glob($prefix . '*.ser') as $old) {
        @unlink($old);
    }
    @file_put_contents($cache, serialize($corpus), LOCK_EX);
    return $corpus;
}

// The normalised terms of a query; words of one or two letters are dropped unless they are all there is
function search_terms(string $q): array
{
    $terms = array_values(array_filter(array_map(fn($w) => search_norm($w[0]), search_words($q))));
    $long = array_values(array_filter($terms, fn($t) => strlen($t) > 2));
    return array_slice(array_unique($long ?: $terms), 0, 8);
}

// Whether a normalised word matches a term: the whole word for short terms, its beginning for longer ones
function search_hit(string $norm, string $term): bool
{
    return strlen($term) < 4 ? $norm === $term : str_starts_with($norm, $term);
}

// Articles with passages holding every term, best first: [['slug', 'edition', 'translation', 'hits' => [passage
// with 'matched' => word indexes], 'count'], …]
function search_run(array $terms): array
{
    $results = [];
    foreach (search_corpus() as $doc) {
        $hits = [];
        foreach ($doc['passages'] as $p) {
            // Every term starts a word somewhere in the passage, or it can't match
            foreach ($terms as $term) {
                if (!str_contains(' ' . $p['norms'], ' ' . $term)) {
                    continue 2;
                }
            }
            $norms = explode(' ', $p['norms']);
            $matched = [];
            foreach ($terms as $term) {
                $found = array_keys(array_filter($norms, fn($n) => search_hit($n, $term)));
                if (!$found) {
                    continue 2;
                }
                $matched = array_merge($matched, $found);
            }
            sort($matched);
            $hits[] = $p + ['words' => search_words($p['text']), 'matched' => array_values(array_unique($matched))];
        }
        if ($hits) {
            // A matching heading, such as a recipe's title, before the passages that only mention the words
            usort($hits, fn($a, $b) => $b['is_heading'] <=> $a['is_heading']);
            $results[] = $doc + ['hits' => $hits, 'count' => count($hits)];
        }
    }
    // Texts with a matching heading first, then those with the most matching passages
    usort($results, fn($a, $b) => [$b['hits'][0]['is_heading'], $b['count']] <=> [$a['hits'][0]['is_heading'], $a['count']]);
    return $results;
}

// A passage cut down to the words around its first match, with every match marked, as HTML
function search_snippet(array $hit, int $before = 14, int $after = 26): string
{
    $words = $hit['words'];
    $text = $hit['text'];
    $first = $hit['matched'][0];
    $from = max(0, $first - $before);
    $to = min(count($words) - 1, $first + $after);
    $start = $from === 0 ? 0 : $words[$from][1];
    $end = $to === count($words) - 1 ? strlen($text) : $words[$to][1] + strlen($words[$to][0]);

    $out = $from > 0 ? '… ' : '';
    $pos = $start;
    foreach ($hit['matched'] as $i) {
        if ($i < $from || $i > $to) {
            continue;
        }
        [$w, $at] = $words[$i];
        $out .= htmlspecialchars(substr($text, $pos, $at - $pos)) . '<mark>' . htmlspecialchars($w) . '</mark>';
        $pos = $at + strlen($w);
    }
    $out .= htmlspecialchars(substr($text, $pos, $end - $pos));
    return $out . ($end < strlen($text) ? ' …' : '');
}

// A link to a passage: the article (its edition, or the text beside its translation), scrolled to a run of words around
// the first match by a text fragment, which browsers that don't support it ignore
function search_link(array $result, array $hit): string
{
    global $metadata;
    $url = '/stable/' . $metadata[$result['slug']]['id'];
    $query = [];
    if ($result['edition'] !== null) {
        $query['edition'] = $result['edition'];
    }
    if (!$result['translation'] && article_translation($result['slug'])) {
        $query['original'] = '1'; // the translation is shown by default; this puts the text beside it
    }
    $url .= $query ? '?' . http_build_query($query) : '';

    // Up to three words either side of the match, joined only by spaces and plain punctuation: pandoc turns
    // quotes, apostrophes and dashes into typographic ones, and page markers aren't part of the sentence
    $words = $hit['words'];
    $text = $hit['text'];
    $plain_gap = function (int $k) use ($words, $text): bool {
        $gap = substr($text, $words[$k][1] + strlen($words[$k][0]), $words[$k + 1][1] - $words[$k][1] - strlen($words[$k][0]));
        return (bool) preg_match('/^[\s,.;:!?()]+$/u', $gap);
    };
    $i = $hit['matched'][0];
    $from = $i;
    $to = $i;
    while ($from > 0 && $i - $from < 3 && $plain_gap($from - 1)) {
        $from--;
    }
    while ($to < count($words) - 1 && $to - $i < 3 && $plain_gap($to)) {
        $to++;
    }
    $phrase = substr($text, $words[$from][1], $words[$to][1] + strlen($words[$to][0]) - $words[$from][1]);
    // In a text fragment, these characters are syntax
    $phrase = str_replace(['-', ',', '&'], ['%2D', '%2C', '%26'], rawurlencode($phrase));
    return $url . '#:~:text=' . $phrase;
}

// One matching passage: the heading it falls under (unless it is that heading), and the words around the match
function search_render_hit(array $r, array $hit)
{
    ?>
    <p class="search-snippet">
        <?php if ($hit['heading'] !== null && !$hit['is_heading']): ?>
            <span class="search-heading sans"><?= htmlspecialchars(mb_strimwidth($hit['heading'], 0, 90, '…')) ?></span>
        <?php endif; ?>
        <a href="<?= htmlspecialchars(search_link($r, $hit)) ?>"><?= search_snippet($hit) ?></a>
    </p>
    <?php
}

function render_search()
{
    global $metadata, $collections, $site_name;
    $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
    $terms = search_terms($q);
    $results = $terms ? search_run($terms) : [];
    $total = array_sum(array_column($results, 'count'));

    $head_title = ($q !== '' ? $q . ' | ' : '') . 'Search | ' . $site_name;
    $body_class = 'page-catalogue page-search';
    include __DIR__ . '/partials/head.php';
    ?>
    <header>
        <?php include __DIR__ . '/partials/topnav.php'; ?>
    </header>
    <article>
        <section>
            <form class="sans catalogue-tools" role="search" action="/search" method="get">
                <div class="tools-row">
                    <div class="search-field">
                        <label for="full-text-search">Search the full text</label>
                        <input id="full-text-search" type="search" name="q" autocomplete="off"
                            placeholder="Words in the texts, in any spelling…" value="<?= htmlspecialchars($q) ?>"
                            <?= $q === '' ? 'autofocus' : '' ?>>
                    </div>
                    <button type="submit" class="filter-toggle search-submit">Search</button>
                </div>
                <?php if ($q !== ''): ?>
                    <p class="result-count">
                        <?= $total ? number_format($total) . ' passage' . ($total === 1 ? '' : 's') . ' in '
                            . count($results) . ' text' . (count($results) === 1 ? '' : 's') : 'No passages match' ?>
                        &middot; <a href="/catalogue?q=<?= rawurlencode($q) ?>">Search titles and summaries instead</a>
                    </p>
                <?php endif; ?>
            </form>

            <?php if ($q !== '' && !$results): ?>
                <p class="no-results">Every word must appear in the same paragraph. Spelling is forgiven: <em>milk</em>
                    finds <em>milke</em>, and <em>unto</em> finds <em>vnto</em>.</p>
            <?php endif; ?>

            <ol class="search-results">
                <?php foreach ($results as $r):
                    $meta = $metadata[$r['slug']];
                    $collection = $meta['collection'] ? $collections[$meta['collection']] : null;
                    $context = array_filter([
                        $collection ? $collection['title'] : $meta['pub'],
                        $meta['date'],
                        !$r['translation'] && article_translation($r['slug'])
                            ? ($meta['language'] ? 'Original (' . $meta['language'] . ') text' : 'Original text') : null,
                        $r['edition'] !== null ? (article_editions($r['slug'])[$r['edition']]['label'] ?? null) : null,
                    ]); ?>
                    <li>
                        <p class="search-byline sans"><?= htmlspecialchars(implode(' · ', $context)) ?></p>
                        <h2 class="search-title">
                            <a href="<?= htmlspecialchars(search_link($r, $r['hits'][0])) ?>"><?= htmlspecialchars($meta['title']) ?></a>
                        </h2>
                        <?php foreach (array_slice($r['hits'], 0, 3) as $hit) {
                            search_render_hit($r, $hit);
                        } ?>
                        <?php if ($r['count'] > 3): ?>
                            <details class="search-more">
                                <summary class="sans"><?= $r['count'] - 3 ?> more in this text</summary>
                                <?php foreach (array_slice($r['hits'], 3) as $hit) {
                                    search_render_hit($r, $hit);
                                } ?>
                            </details>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    </article>
    <script src="<?= asset('js/theme.js') ?>"></script>
</body>

</html>
<?php
}
