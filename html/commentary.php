<?php
// ./html/commentary.php
// Commentary: short essays on the articles, kept apart from them in commentary/<name>.md. Each names the
// articles it is about by their stable URLs (about:), so it is only loosely coupled to them: the articles
// know nothing of it, and an article page finds its commentary by its id. Built by build-articles.sh into
// _commentary/<name>.html, with the front matter as <name>.json. Drafts (status: draft) are shown only on
// the local dev server.

// Every commentary the site shows, newest first: [id => ['id', 'name', 'title' (HTML), 'author', 'date',
// 'draft', 'about' => [article ids, or URLs that aren't articles], 'html', 'md']]
function commentary_all(): array
{
    global $articles_base;
    static $all = null;
    if ($all !== null) {
        return $all;
    }
    $local = php_sapi_name() === 'cli-server';
    $all = [];
    foreach (glob($articles_base . '/_commentary/*.json') as $json) {
        $c = json_decode(file_get_contents($json), true);
        $base = substr($json, 0, -5);
        $id = strtolower(trim((string) ($c['id'] ?? '')));
        if (!is_array($c) || $id === '' || !is_file($base . '.html')) {
            error_log("Plain Old Text: skipping commentary '" . basename($base) . "': no id (run scripts/assign-ids.php)");
            continue;
        }
        if (isset($all[$id])) {
            error_log("Plain Old Text: skipping commentary '" . basename($base) . "': id '$id' is already used by '{$all[$id]['name']}'");
            continue;
        }
        $draft = ($c['status'] ?? 'draft') !== 'published';
        if ($draft && !$local) {
            continue;
        }
        $all[$id] = [
            'id'     => $id,
            'name'   => basename($base),
            'title'  => $c['title'] ?? 'Untitled',
            'author' => $c['author'] ?? null,
            'date'   => (string) ($c['date'] ?? ''),
            'draft'  => $draft,
            // A stable URL on either domain becomes the article's id
            'about'  => array_map(
                fn($url) => preg_match('#/stable/([0-9a-hjkmnp-tv-z]{5})/?(?:[?\#].*)?$#i', $url, $m) ? strtolower($m[1]) : $url,
                (array) ($c['about'] ?? [])
            ),
            'html'   => $base . '.html',
            'md'     => is_file($base . '.md') ? $base . '.md' : null,
        ];
    }
    uasort($all, fn($a, $b) => [$b['date'], $a['name']] <=> [$a['date'], $b['name']]);
    return $all;
}

// The commentary on an article, by its stable id
function commentary_on(string $article_id): array
{
    return array_filter(commentary_all(), fn($c) => in_array($article_id, $c['about'], true));
}

// "31 Oct, 2026", like the articles' dates
function commentary_date(string $date): string
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d ? $d->format('j M, Y') : $date;
}

// Links to what a commentary is about: an article by its title, anything else by its URL
function commentary_about_links(array $c): string
{
    global $metadata, $stable_ids;
    $links = [];
    foreach ($c['about'] as $about) {
        $slug = $stable_ids[$about] ?? null;
        $links[] = $slug
            ? '<a href="/stable/' . htmlspecialchars($about) . '">' . htmlspecialchars($metadata[$slug]['title']) . '</a>'
            : '<a href="' . htmlspecialchars($about) . '">' . htmlspecialchars($about) . '</a>';
    }
    return implode('; ', $links);
}

// The byline: the author, the date, and whether it is a draft
function commentary_byline(array $c): string
{
    return implode(' <span class="dateline-sep" aria-hidden="true">&middot;</span> ', array_filter([
        $c['author'] ? htmlspecialchars($c['author']) : null,
        $c['date'] !== '' ? htmlspecialchars(commentary_date($c['date'])) : null,
        $c['draft'] ? '<span class="commentary-draft">Draft (local only)</span>' : null,
    ]));
}

// /commentary/<id>
function render_commentary(string $id)
{
    global $site_name;
    $c = commentary_all()[$id] ?? null;
    if ($c === null) {
        http_response_code(404);
        render_404();
        return;
    }
    $title = strip_tags($c['title']);
    $pub_name = $site_name;
    $body_class = 'page-article page-commentary';
    $github_url = 'https://github.com/veazmanov/PlainOldText/blob/main/commentary/' . rawurlencode($c['name']) . '.md';
    $content = '<p class="commentary-kind sans"><a href="/commentary">Commentary</a></p>'
        . '<h1>' . $c['title'] . '</h1>'
        . '<p class="dateline sans">' . commentary_byline($c) . '</p>'
        . ($c['about'] ? '<p class="commentary-about sans">On ' . commentary_about_links($c) . '</p>' : '')
        . file_get_contents($c['html'])
        . '<p class="commentary-source sans"><a href="' . htmlspecialchars($github_url) . '">View on GitHub</a></p>';
    include(__DIR__ . '/layout.php');
}

// /commentary: every commentary, newest first
function render_commentary_list()
{
    global $site_name;
    $title = 'Commentary';
    $pub_name = $site_name;
    $body_class = 'page-article page-commentary';
    $items = '';
    foreach (commentary_all() as $c) {
        $items .= '<li><p class="commentary-entry"><a href="/commentary/' . htmlspecialchars($c['id']) . '">' . $c['title'] . '</a></p>'
            . '<p class="dateline sans">' . commentary_byline($c) . '</p>'
            . ($c['about'] ? '<p class="commentary-about sans">On ' . commentary_about_links($c) . '</p>' : '') . '</li>';
    }
    $content = '<h1>Commentary</h1>'
        . '<p>Essays on the sources in the archive, kept apart from the texts themselves.</p>'
        . ($items !== '' ? '<ul class="commentary-list">' . $items . '</ul>' : '<p>Nothing yet.</p>');
    include(__DIR__ . '/layout.php');
}
