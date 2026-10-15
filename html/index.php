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

// 5. Photocopy Viewer Route
if (strpos($clean_uri, 'photocopy/') === 0) {
    $uri = str_replace('photocopy/', '', $clean_uri);
    $uri = ltrim($uri, '/');
    $uri = str_replace(['../', '..\\'], '', $uri);

    if (!isset($metadata[$uri])) {
        http_response_code(404);
        render_404();
        exit;
    }

    $_GET['uri'] = $uri;
    include(__DIR__ . '/photocopy.php');
    exit;
}

// 6. Flat Article Route
if (array_key_exists($clean_uri, $metadata)) {
    $target_file = $articles_base . '/' . $clean_uri;
    $html_files = glob($target_file . '/*.html');
    $real_target = !empty($html_files) ? realpath($html_files[0]) : false;
    $real_base = realpath($articles_base);

    if ($real_target !== false && strpos($real_target, $real_base) === 0 && is_file($real_target)) {
        render_article($clean_uri, $real_target);
    } else {
        render_pending_transcription($clean_uri);
    }
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
    global $site_name;
    $links = get_compiled_links();
    $current_page = 'catalogue';
    include(__DIR__ . '/catalogue.php');
}

function render_article($uri, $full_path)
{
    global $metadata, $site_name;
    $meta = $metadata[$uri];

    $plaintext_url = "https://github.com/victorelgersma/old-news/blob/main/articles/" . rawurlencode($uri) . "/main.md";
    $summary = $meta['summary'];
    $title = $meta['title'];
    $pub_name = $meta['pub'];
    $date_str = $meta['date'];
    $photo_link = "/photocopy/" . $uri;
    $page_num = $meta['page'];
    $source_url = $meta['source_url'];
    $article_header_partial = __DIR__ . '/partials/article_header.php';

    $content = file_get_contents($full_path);

    // Lift the article's first heading out so it renders above the source panel.
    $article_title_html = null;
    if (preg_match('/<h([1-3])\b[^>]*>.*?<\/h\1>/is', $content, $m, PREG_OFFSET_CAPTURE)) {
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

function render_pending_transcription($uri)
{
    global $metadata, $site_name;

    $meta = $metadata[$uri];
    $title = $meta['title'];
    $pub_name = $meta['pub'];
    $photo_link = "/photocopy/" . rawurlencode($uri);

    $content = "
        <div style='padding: 1.5rem; border-radius: 4px;'>
            <h3>Transcription Pending</h3>
            <p>The text for this article is still processing in our digital humanities pipeline.</p>
            <p>In the meantime, you can read the <a href='$photo_link'>original scans</a>.</p>
        </div>";

    include(__DIR__ . '/layout.php');
}
