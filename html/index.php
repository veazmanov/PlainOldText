<?php
// ./html/index.php
require_once('data.php');
require_once('citation.php');
require_once('card_scan.php');
require_once('notes.php');
require_once('search.php');

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Let PHP's built-in dev server serve static files (css, images) directly.
if (php_sapi_name() === 'cli-server' && $request_uri !== '/' && is_file(__DIR__ . $request_uri)) {
    return false;
}
$clean_uri = ltrim($request_uri, '/');
$clean_uri = str_replace(['../', '..\\'], '', $clean_uri);

// 1. Default Route (Redirect to Catalogue)
if ($clean_uri === "" || $clean_uri === "index.php") {
    header("Location: /catalogue");
    exit;
}

// 2. Catalogue Page Route
if ($clean_uri === "catalogue") {
    render_catalogue();
    exit;
}

// 2a. Full-text search (search.php)
if ($clean_uri === "search") {
    render_search();
    exit;
}

// 3. About Page Route
if ($clean_uri === "about") {
    $title = "About";
    $pub_name = $site_name;

    ob_start();
    $current_page = 'about';
    include(__DIR__ . '/about.php');
    $content = ob_get_clean();

    include(__DIR__ . '/layout.php');

    exit;
}

// 4. Contribute Page Route
if ($clean_uri === "contribute") {
    $title = "Contribute";
    $pub_name = $site_name;

    ob_start();
    $current_page = 'contribute';
    include(__DIR__ . '/contribute.php');
    $content = ob_get_clean();

    include(__DIR__ . '/layout.php');

    exit;
}

// 5a. A card-sized image of the article's first scan, for the catalogue (see card_scan.php)
if (preg_match('#^stable/([0-9a-hjkmnp-tv-z]{5})/card$#i', $clean_uri, $m)) {
    $slug = $stable_ids[strtolower($m[1])] ?? null;
    if ($slug === null) {
        http_response_code(404);
        exit;
    }
    serve_card_scan($slug);
}

// 5. Stable Route: /stable/<id>, /stable/<id>/scans with the original scans open, and
// /stable/<id>/markdown with the plain-text source (all take ?edition=<id>). This is each article's canonical URL.
// A collection's id leads to its contents page.
if (preg_match('#^stable/([0-9a-hjkmnp-tv-z]{5})(/scans|/markdown)?$#i', $clean_uri, $m)) {
    $collection_key = $collection_ids[strtolower($m[1])] ?? null;
    if ($collection_key !== null && empty($m[2])) {
        render_collection($collection_key);
        exit;
    }
    $slug = $stable_ids[strtolower($m[1])] ?? null;
    if ($slug === null) {
        http_response_code(404);
        render_404();
        exit;
    }
    $view = $m[2] ?? '';
    if ($view === '/markdown') {
        render_markdown($slug);
    } else {
        render_article($slug, $view === '/scans');
    }
    exit;
}

// 6. Old scans URL: /photocopy/<slug> now lives at /stable/<id>/scans
if (preg_match('#^photocopy/(.+)$#', $clean_uri, $m) && isset($metadata[$m[1]])) {
    redirect_to_stable($m[1], '/scans');
}

// 7. Old slug URLs: /<slug>, /<slug>/scans and /<slug>/markdown redirect to their stable URLs
if (preg_match('#^(.+?)(/scans|/markdown)?$#', $clean_uri, $m) && isset($metadata[$m[1]])) {
    redirect_to_stable($m[1], $m[2] ?? '');
}

// Fallback 404
http_response_code(404);
render_404();

// ---- Render Functions ----

// Permanently redirect to an article's stable URL, keeping the query string (?edition=).
function redirect_to_stable($slug, $suffix = '')
{
    global $metadata;
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: /stable/' . $metadata[$slug]['id'] . $suffix . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

// The plain-text source of an article's edition (?edition=<id>), or a 404 if it has none.
function render_markdown($slug)
{
    $edition = pick_edition(article_editions($slug));
    if (!$edition || !$edition['md']) {
        http_response_code(404);
        render_404();
        return;
    }
    header('Content-Type: text/plain; charset=utf-8');
    readfile($edition['md']);
}

// The catalogue cards: one per article, except that the parts of a collection share one card.
function get_compiled_links()
{
    global $metadata, $collections;
    $links = [];
    $parts = [];

    foreach ($metadata as $uri => $meta) {
        $article = get_article_preview($uri);
        $link = $meta + [
            'uri' => $uri,
            'id' => $meta['id'],
            'card_scan' => card_scan_url($uri),
            'excerpt' => $article['excerpt'],
            'words' => $article['words']
        ];
        if ($meta['collection'] !== null) {
            $parts[$meta['collection']][] = $link;
        } else {
            $links[] = $link;
        }
    }

    foreach ($collections as $key => $c) {
        $links[] = [
            // The byline names the author, as the publication is the card's title
            'pub' => $c['author'] ?: $c['pub'],
            'is_collection' => true,
            'parts' => count($parts[$key]),
            'card_scan' => null,
            'excerpt' => null,
            'words' => array_sum(array_column($parts[$key], 'words')),
            // Searching for a part's title or summary finds its collection
            'search_extra' => implode(' ', array_merge(
                array_column($parts[$key], 'title'),
                array_column($parts[$key], 'summary')
            )),
        ] + $c;
    }

    return $links;
}

// The edition named by ?edition=, or the default (first) one; null if there are none.
function pick_edition($editions)
{
    $id = $_GET['edition'] ?? '';
    return $editions[$id] ?? (reset($editions) ?: null);
}

// Opening text and word count of an article's default edition, for catalogue cards.
function get_article_preview($uri)
{
    $editions = article_editions($uri);
    if (empty($editions)) {
        return ['excerpt' => null, 'words' => 0];
    }

    // Editorial notes (sources, transcription conventions) aren't part of the text.
    $html = preg_replace('#<div class="editorial-note">.*?</div>#s', '', file_get_contents(reset($editions)['html']));
    $text = function ($fragment) {
        $plain = html_entity_decode(strip_tags($fragment), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $plain));
    };

    // Skip short lines (bylines, book titles) and take the first real paragraph.
    $excerpt = null;
    if (preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $matches)) {
        foreach ($matches[1] as $p) {
            $candidate = $text($p);
            if (mb_strlen($candidate) >= 200) {
                $excerpt = $candidate;
                break;
            }
        }
    }
    if ($excerpt !== null && mb_strlen($excerpt) > 420) {
        $excerpt = preg_replace('/[\s,;:]+\S*$/u', '', mb_substr($excerpt, 0, 420)) . '…';
    }

    return [
        'excerpt' => $excerpt,
        'words' => str_word_count($text($html))
    ];
}

function render_catalogue()
{
    global $site_name, $categories, $last_edited, $stable_ids, $metadata;
    $links = get_compiled_links();
    $last_edited_article = $metadata[$stable_ids[$last_edited] ?? ''] ?? null;
    $current_page = 'catalogue';
    include(__DIR__ . '/catalogue.php');
}

// HTML for a short string with Markdown *italics* and [links](url), escaped otherwise.
function inline_markdown($s)
{
    $s = htmlspecialchars((string) $s);
    $s = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $s);
    return preg_replace('/\[([^\]]+)\]\((https?:[^)\s]+)\)/', '<a href="$2">$1</a>', $s);
}

// The note at the top of a collection's contents page: its source edition, the copy transcribed and why the
// texts are in the public domain.
function collection_source_note($c)
{
    $paras = '';
    foreach (['source' => 'Source', 'transcribed_from' => 'Transcribed from', 'copyright' => 'Copyright'] as $field => $label) {
        if (trim($c[$field]) !== '') {
            $paras .= '<p><span class="sans">' . $label . ':</span> ' . inline_markdown($c[$field]) . '</p>';
        }
    }
    return $paras ? '<div class="editorial-note">' . $paras . '</div>' : '';
}

// A collection's contents page: its source note, summary, then each part with its pages and summary.
function render_collection($key)
{
    global $metadata, $collections, $site_name, $site_url;
    $c = $collections[$key];
    $title = $c['title'];
    $pub_name = $site_name;
    $body_class = 'page-article page-collection';
    $h = fn($s) => htmlspecialchars((string) $s);

    $items = '';
    foreach ($c['members'] as $slug) {
        $part = $metadata[$slug];
        $items .= '<li><p class="contents-entry"><a href="/stable/' . $h($part['id']) . '">' . $h($part['title']) . '</a>'
            . ($part['page'] ? ' <span class="contents-page sans">pp.&nbsp;' . $h($part['page']) . '</span>' : '')
            . '</p>'
            . ($part['summary'] ? '<p class="contents-summary">' . inline_markdown($part['summary']) . '</p>' : '') . '</li>';
    }

    $content = '<h1>' . $h($c['title']) . '</h1>'
        . '<p class="dateline sans"><span class="dateline-pub">' . $h($c['author'] ?: $c['pub']) . '</span>'
        . '<span class="dateline-sep" aria-hidden="true">&middot;</span><span>' . $h($c['date']) . '</span></p>'
        . collection_source_note($c)
        . ($c['summary'] ? '<p>' . $h($c['summary']) . '</p>' : '')
        . '<h2>Contents</h2><ol class="collection-contents">' . $items . '</ol>';

    include(__DIR__ . '/layout.php');
}

// "Part of <collection>" under an article's dateline, and links to the previous and next parts after its text.
function collection_nav($uri)
{
    global $metadata, $collections;
    $key = $metadata[$uri]['collection'];
    if ($key === null) {
        return [null, ''];
    }
    $c = $collections[$key];
    $h = fn($s) => htmlspecialchars((string) $s);
    $i = array_search($uri, $c['members'], true);
    $prev = $c['members'][$i - 1] ?? null;
    $next = $c['members'][$i + 1] ?? null;

    $nav = '<nav class="part-nav sans" aria-label="' . $h($c['title']) . '">'
        . '<span class="part-prev">' . ($prev ? '<a rel="prev" href="/stable/' . $h($metadata[$prev]['id']) . '">&larr; ' . $h($metadata[$prev]['title']) . '</a>' : '') . '</span>'
        . '<a class="part-contents" href="/stable/' . $h($c['id']) . '">Contents</a>'
        . '<span class="part-next">' . ($next ? '<a rel="next" href="/stable/' . $h($metadata[$next]['id']) . '">' . $h($metadata[$next]['title']) . ' &rarr;</a>' : '') . '</span>'
        . '</nav>';
    return [$c, $nav];
}

function render_article($uri, $scans_open = false)
{
    global $metadata, $articles_base, $site_name, $site_url;
    $meta = $metadata[$uri];
    // The article's stable URL, which its citation, links and canonical tag use
    $slug_url = '/stable/' . $meta['id'];
    $canonical_url = $site_url . $slug_url;

    $scans = array_map(
        fn($file) => 'https://oldnews-photos.vjbe.net/' . rawurlencode($uri) . '/' . rawurlencode($file),
        $meta['scans']
    );
    // Scans are page images on the photo host, or one PDF (scans_pdf)
    $scans_pdf = $meta['scans_pdf'];
    $has_scans = !empty($scans) || $scans_pdf;
    if ($scans_open && !$has_scans) {
        header('Location: ' . $slug_url);
        exit;
    }

    $editions = article_editions($uri);
    $edition = pick_edition($editions);
    $edition_id = $edition ? array_search($edition, $editions, true) : null;
    // Keep a non-default edition in the article's links (?edition=<id>)
    $edition_query = ($edition_id !== null && $edition_id !== array_key_first($editions))
        ? '?edition=' . rawurlencode($edition_id) : '';
    // An English translation is shown instead of the text, which ?original=1 puts beside it
    $translation = article_translation($uri);
    if ($translation && !empty($_GET['translation'])) {
        // The old ?translation=1 showed the two side by side, as ?original=1 now does
        header('Location: ' . ($scans_open ? $slug_url . '/scans' : $slug_url) . $edition_query . ($edition_query ? '&' : '?') . 'original=1', true, 301);
        exit;
    }
    $translation_shown = $translation && $edition && !empty($_GET['original']);
    $translation_only = $translation && $edition && !$translation_shown;
    // The edition and original, kept in the page's own links (the citation keeps only the edition)
    $page_query = $edition_query . ($translation_shown ? ($edition_query ? '&' : '?') . 'original=1' : '');
    $citation = chicago_citation($meta, $site_url . $slug_url . $edition_query);
    $markdown_url = ($edition && $edition['md']) ? $slug_url . '/markdown' . $edition_query : null;
    $github_url = "https://github.com/veazmanov/PlainOldText/blob/main/articles/" . rawurlencode($uri) . '/'
        . rawurlencode($translation_only ? 'translation.md' : ($edition && $edition['md'] ? basename($edition['md']) : 'main.md'));
    $summary = $meta['summary'];
    $title = $meta['title'];
    $pub_name = $meta['pub'];
    $date_str = $meta['date'];
    // "Original scans" goes to the scans view, or back to the text when the scans are open
    $photo_link = ($scans_open ? $slug_url : $slug_url . '/scans') . $page_query;
    // "Original (Latin) text" puts it beside the translation, "Hide original" goes back to the translation alone
    $translation_link = ($scans_open ? $slug_url . '/scans' : $slug_url)
        . ($translation_shown ? $edition_query : $edition_query . ($edition_query ? '&' : '?') . 'original=1');
    $page_num = $meta['page'];
    $source_url = $meta['source_url'];
    $article_header_partial = __DIR__ . '/partials/article_header.php';

    if ($edition) {
        $content = file_get_contents($translation_only ? $translation['html'] : $edition['html']);
    } else {
        $content = "
        <div style='padding: 1.5rem; border-radius: 4px;'>
            <h3>Transcription Pending</h3>
            <p>The text for this article is still processing in our digital humanities pipeline.</p>"
            . ($has_scans ? "
            <p>In the meantime, you can read the <a href='$slug_url/scans'>original scans</a>.</p>" : '') . "
        </div>";
    }

    // Lift the article's first heading out so it renders above the source panel.
    $article_title_html = null;
    if ($edition && preg_match('/<h([1-3])\b[^>]*>.*?<\/h\1>/is', $content, $m, PREG_OFFSET_CAPTURE)) {
        $before = trim(strip_tags(substr($content, 0, $m[0][1])));
        if ($before === '') {
            $article_title_html = $m[0][0];
            $content = substr($content, $m[0][1] + strlen($m[0][0]));
        }
    }
    if ($article_title_html === null) {
        $article_title_html = '<h1>' . htmlspecialchars($title) . '</h1>';
    }

    if ($edition) {
        $content = apply_editor_notes($content, $meta['notes'], $translation_only ? 'translation' : $edition_id);
    }
    if ($translation_shown) {
        $content = parallel_translation($content, file_get_contents($translation['html']), $meta['notes']);
    } elseif ($translation_only) {
        // The text's editorial note, which describes the source, goes before the translation's
        $content = parallel_blocks(file_get_contents($edition['html']), false)['note'] . $content;
    }

    [$collection, $part_nav] = collection_nav($uri);
    $content .= $part_nav;

    include(__DIR__ . '/layout.php');
    exit();
}

// The text and its translation side by side: the editorial notes first, then a row per block (paragraph, heading,
// list…) with the text's block beside the translation's, then both sets of footnotes. The translation gets its
// editor's notes (edition "translation") and its ids prefixed with tr-, so they don't clash with the text's.
function parallel_translation($html, $translation_html, $notes)
{
    $translation_html = apply_editor_notes($translation_html, $notes, 'translation');
    $translation_html = preg_replace(['/(?<=\s)(id|for)="/', '/(?<=\s)href="#/'], ['$1="tr-', 'href="#tr-'], $translation_html);

    $text = parallel_blocks($html, false);
    $tr = parallel_blocks($translation_html, true);

    $rows = '';
    for ($i = 0, $n = max(count($text['blocks']), count($tr['blocks'])); $i < $n; $i++) {
        $rows .= '<div class="parallel-row"><div class="parallel-text">' . ($text['blocks'][$i] ?? '') . '</div>'
            . '<div class="parallel-translation" lang="en">' . ($tr['blocks'][$i] ?? '') . '</div></div>';
    }

    return $text['note']
        . ($tr['note'] !== '' ? '<div class="translation-note" lang="en">' . $tr['note'] . '</div>' : '')
        . '<div class="parallel">' . $rows . '</div>'
        . $text['footnotes'] . $tr['footnotes'];
}

// An article's HTML split into its top-level blocks: ['note' => the editorial note, 'blocks' => [html…],
// 'footnotes' => the footnotes section]. With $drop_title, its first h1–h3 (a translation's repeated title) is left out.
function parallel_blocks($html, $drop_title)
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="parallel-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $out = ['note' => '', 'blocks' => [], 'footnotes' => ''];
    foreach ($doc->getElementById('parallel-root')->childNodes as $child) {
        if ($child instanceof DOMText && trim($child->data) === '') {
            continue;
        }
        $class = $child instanceof DOMElement ? ' ' . $child->getAttribute('class') . ' ' : '';
        if (str_contains($class, ' editorial-note ')) {
            $out['note'] .= $doc->saveHTML($child);
        } elseif ($child->nodeName === 'nav' || ($child->nodeName === 'section' && str_contains($class, ' footnotes '))) {
            $out['footnotes'] .= $doc->saveHTML($child);
        } elseif ($drop_title && preg_match('/^h[1-3]$/', $child->nodeName)) {
            $drop_title = false;
        } else {
            $out['blocks'][] = $doc->saveHTML($child);
        }
    }
    return $out;
}

function render_404()
{
    global $site_name;
    $title = "404 Not Found";
    $pub_name = $site_name;
    $content = "<p>We are sorry, but that piece doesn't exist in the archive yet.</p>";
    include(__DIR__ . '/layout.php');
}
