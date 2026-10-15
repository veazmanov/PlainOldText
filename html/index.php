<?php
// ./html/index.php
require_once('data.php');

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

// 5. Old scans URL: /photocopy/<slug> now lives at /<slug>/scans
if (preg_match('#^photocopy/(.+)$#', $clean_uri, $m) && isset($metadata[$m[1]])) {
    header('Location: /' . rawurlencode($m[1]) . '/scans', true, 301);
    exit;
}

// 6. Markdown Route: /<slug>/markdown serves the article's plain-text source
if (preg_match('#^(.+)/markdown$#', $clean_uri, $m) && isset($metadata[$m[1]])) {
    $md_files = glob($articles_base . '/' . $m[1] . '/*.md');
    if (empty($md_files)) {
        http_response_code(404);
        render_404();
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    readfile($md_files[0]);
    exit;
}

// 7. Article Route: /<slug>, and /<slug>/scans with the original scans open
if (preg_match('#^(.+?)(/scans)?$#', $clean_uri, $m) && isset($metadata[$m[1]])) {
    render_article($m[1], !empty($m[2]));
    exit;
}

// Fallback 404
http_response_code(404);
render_404();

// ---- Render Functions ----

function get_compiled_links()
{
    global $metadata, $articles_base;
    $links = [];

    foreach ($metadata as $uri => $meta) {
        $article = get_article_preview($articles_base . '/' . $uri);
        $links[] = $meta + [
            'uri' => $uri,
            'excerpt' => $article['excerpt'],
            'words' => $article['words']
        ];
    }

    return $links;
}

// Opening text and word count of an article's transcription, for catalogue cards.
function get_article_preview($dir)
{
    $html_files = glob($dir . '/*.html');
    if (empty($html_files)) {
        return ['excerpt' => null, 'words' => 0];
    }

    $html = file_get_contents($html_files[0]);
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
    global $site_name, $categories;
    $links = get_compiled_links();
    $current_page = 'catalogue';
    include(__DIR__ . '/catalogue.php');
}

function render_article($uri, $scans_open = false)
{
    global $metadata, $articles_base, $site_name;
    $meta = $metadata[$uri];
    $slug_url = '/' . rawurlencode($uri);

    $scans = array_map(
        fn($file) => 'https://oldnews-photos.vjbe.net/' . rawurlencode($uri) . '/' . rawurlencode($file),
        $meta['scans']
    );
    if ($scans_open && empty($scans)) {
        header('Location: ' . $slug_url);
        exit;
    }

    $html_files = glob($articles_base . '/' . $uri . '/*.html');
    $md_files = glob($articles_base . '/' . $uri . '/*.md');
    $markdown_url = !empty($md_files) ? $slug_url . '/markdown' : null;
    $github_url = "https://github.com/victorelgersma/old-news/blob/main/articles/" . rawurlencode($uri) . '/'
        . rawurlencode(!empty($md_files) ? basename($md_files[0]) : 'main.md');
    $summary = $meta['summary'];
    $title = $meta['title'];
    $pub_name = $meta['pub'];
    $date_str = $meta['date'];
    // "Original scans" goes to the scans view, or back to the text when the scans are open
    $photo_link = $scans_open ? $slug_url : $slug_url . '/scans';
    $page_num = $meta['page'];
    $source_url = $meta['source_url'];
    $article_header_partial = __DIR__ . '/partials/article_header.php';

    if (!empty($html_files)) {
        $content = file_get_contents($html_files[0]);
    } else {
        $content = "
        <div style='padding: 1.5rem; border-radius: 4px;'>
            <h3>Transcription Pending</h3>
            <p>The text for this article is still processing in our digital humanities pipeline.</p>"
            . (!empty($scans) ? "
            <p>In the meantime, you can read the <a href='$slug_url/scans'>original scans</a>.</p>" : '') . "
        </div>";
    }

    // Lift the article's first heading out so it renders above the source panel.
    $article_title_html = null;
    if (!empty($html_files) && preg_match('/<h([1-3])\b[^>]*>.*?<\/h\1>/is', $content, $m, PREG_OFFSET_CAPTURE)) {
        $before = trim(strip_tags(substr($content, 0, $m[0][1])));
        if ($before === '') {
            $article_title_html = $m[0][0];
            $content = substr($content, $m[0][1] + strlen($m[0][0]));
        }
    }
    if ($article_title_html === null) {
        $article_title_html = '<h1>' . htmlspecialchars($title) . '</h1>';
    }

    include(__DIR__ . '/layout.php');
    exit();
}

function render_404()
{
    global $site_name;
    $title = "404 Not Found";
    $pub_name = $site_name;
    $content = "<p>We are sorry, but that piece doesn't exist in the archive yet.</p>";
    include(__DIR__ . '/layout.php');
}
