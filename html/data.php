<?php
// ./html/data.php

$site_name = "Plain Old Text";
$site_url = "https://sources.vjbe.net";

// URL for a file under html/, versioned by its modification time so browsers refetch it after a deploy.
function asset($path)
{
    $file = __DIR__ . '/' . $path;
    return '/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

$articles_base = dirname(__DIR__) . '/oldnews-articles-only';

// Values for "category" in data.json, with their labels in the catalogue filter.
$categories = [
    'newspaper'    => 'Newspapers',
    'letter'       => 'Letters',
    'book-chapter' => 'Book chapters',
    'journal'      => 'Journals',
];

// A source folder may hold several transcriptions, named <edition>.md. These are listed first
// (the first present is the default); others follow alphabetically, labelled by file name.
$edition_labels = [
    'reading'    => 'Reading edition',
    'diplomatic' => 'Diplomatic transcription',
];

// The built editions of an article, default first: [id => ['label', 'html', 'md']].
// translation.md is an English translation shown beside the text (article_translation()), not an edition.
function article_editions($uri)
{
    global $articles_base, $edition_labels;
    $editions = [];
    foreach (glob($articles_base . '/' . $uri . '/*.html') as $html) {
        $id = basename($html, '.html');
        if ($id === 'translation') {
            continue;
        }
        $md = substr($html, 0, -5) . '.md';
        $editions[$id] = [
            'label' => $edition_labels[$id] ?? ucfirst(str_replace(['-', '_'], ' ', $id)),
            'html'  => $html,
            'md'    => is_file($md) ? $md : null,
        ];
    }
    $order = array_flip(array_keys($edition_labels));
    uksort($editions, fn($a, $b) => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);
    return $editions;
}

// The article's English translation (translation.md), ['html', 'md'], or null if it has none.
function article_translation($uri)
{
    global $articles_base;
    $html = $articles_base . '/' . $uri . '/translation.html';
    if (!is_file($html)) {
        return null;
    }
    $md = $articles_base . '/' . $uri . '/translation.md';
    return ['html' => $html, 'md' => is_file($md) ? $md : null];
}

// One record per article folder that has a data.json, keyed by folder name (the slug).
$metadata = [];
// Each article's permanent id (its "id" in data.json, used in /stable/<id>) => slug.
$stable_ids = [];

if (is_dir($articles_base)) {
    foreach (array_diff(scandir($articles_base), ['.', '..']) as $item) {
        $json_file = $articles_base . '/' . $item . '/data.json';
        $json_data = is_file($json_file) ? json_decode(file_get_contents($json_file), true) : null;
        if (!$json_data) {
            continue;
        }

        $id = strtolower(trim((string) ($json_data['id'] ?? '')));
        if ($id === '') {
            error_log("Plain Old Text: skipping article '$item': no \"id\" in data.json (run scripts/assign-ids.php)");
            continue;
        }
        if (isset($stable_ids[$id])) {
            error_log("Plain Old Text: skipping article '$item': id '$id' is already used by '{$stable_ids[$id]}'");
            continue;
        }
        $stable_ids[$id] = $item;

        // A full date, or just a year (e.g. a book)
        $year  = $json_data['year'] ?? '';
        $month = $json_data['month'] ?? '';
        $day   = $json_data['day'] ?? '';
        $date_obj = DateTime::createFromFormat('!Y-n-j', "$year-$month-$day");
        if (!$date_obj && preg_match('/^\d{3,4}$/', (string) $year)) {
            $date_obj = DateTime::createFromFormat('!Y', (string) $year);
            $date_format = 'Y';
        } else {
            $date_format = 'j M, Y';
        }

        $metadata[$item] = [
            'id'         => $id,
            'title'      => $json_data['title'] ?? 'Untitled Article',
            // "publication" for books and other non-newspaper sources
            'pub'        => ($json_data['publication'] ?? $json_data['newspaper'] ?? '') ?: 'Archive Entry',
            'category'   => $json_data['category'] ?? 'newspaper',
            'date'       => $date_obj ? $date_obj->format($date_format) : 'Undated',
            'sort_date'  => $date_obj ? $date_obj->format('Y-m-d') : '0000-00-00',
            'summary'    => $json_data['summary'] ?? '',
            // true, or "true"; "false" as a string is not featured
            'featured'   => filter_var($json_data['featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'source_url' => $json_data['source_url'] ?? null,
            'page'       => $json_data['page'] ?? null,
            'scans'      => $json_data['scans'] ?? [],
            'scans_pdf'  => $json_data['scans_pdf'] ?? null,
            // Which part of the first scan the catalogue card shows (a CSS background-position)
            'card_position' => $json_data['card_position'] ?? null,
            // For the citation (see citation.php)
            'year'       => $year,
            'month'      => $date_format === 'Y' ? '' : $month,
            'day'        => $date_format === 'Y' ? '' : $day,
            'author'     => $json_data['author'] ?? null,
            'place'      => $json_data['place'] ?? null,
            'full_title' => $json_data['full_title'] ?? null,
            'translator' => $json_data['translator'] ?? null,
            'printer'    => $json_data['printer'] ?? null,
            'volume'     => $json_data['volume'] ?? null,
            'issue'      => $json_data['issue'] ?? null,
            // The key of the collection (in collections.json) this article is a part of
            'collection' => $json_data['collection'] ?? null,
            // Editor's notes, shown as lettered sidenotes (see notes.php)
            'notes'      => $json_data['notes'] ?? [],
        ];
    }
}

// Collections: works in several parts (e.g. a book's chapters), shown as one catalogue card with a
// contents page at /stable/<id>. collections.json holds each one's id, title and summary; the members
// are the articles whose "collection" names it, in folder order. Keyed by collection key.
$collections = [];
// Each collection's permanent id => key. Ids come from the same pool as articles' (assign-ids.php).
$collection_ids = [];

$collections_file = $articles_base . '/collections.json';
$collections_data = is_file($collections_file) ? json_decode(file_get_contents($collections_file), true) : [];
foreach ($collections_data ?: [] as $key => $c) {
    $id = strtolower(trim((string) ($c['id'] ?? '')));
    if ($id === '' || isset($stable_ids[$id]) || isset($collection_ids[$id])) {
        error_log("Plain Old Text: skipping collection '$key': missing or duplicate id (run scripts/assign-ids.php)");
        continue;
    }
    $collection_ids[$id] = $key;
    foreach (['source', 'copyright'] as $field) {
        if (trim((string) ($c[$field] ?? '')) === '') {
            error_log("Plain Old Text: collection '$key' has no \"$field\" in collections.json");
        }
    }
    $collections[$key] = [
        'key'     => $key,
        'id'      => $id,
        'title'   => $c['title'] ?? $key,
        'summary' => $c['summary'] ?? '',
        // Shown in a note at the top of the contents page. "source": the Chicago bibliography entry of the
        // original edition; "transcribed_from": the copy actually transcribed (optional); "copyright": why the
        // texts are free to reproduce. *Italics* and [links](url) are allowed.
        'source'           => $c['source'] ?? '',
        'transcribed_from' => $c['transcribed_from'] ?? '',
        'copyright'        => $c['copyright'] ?? '',
        'members' => [],
    ];
}

foreach ($metadata as $slug => $meta) {
    $key = $meta['collection'];
    if ($key === null) {
        continue;
    }
    if (!isset($collections[$key])) {
        error_log("Plain Old Text: article '$slug' names unknown collection '$key'; showing it on its own");
        $metadata[$slug]['collection'] = null;
        continue;
    }
    $collections[$key]['members'][] = $slug;
}

foreach ($collections as $key => $c) {
    if (!$c['members']) {
        unset($collections[$key], $collection_ids[$c['id']]);
        continue;
    }
    // The byline, date and category are the first part's
    $first = $metadata[$c['members'][0]];
    $collections[$key] += [
        'pub'       => $first['pub'],
        'author'    => $first['author'],
        'date'      => $first['date'],
        'sort_date' => $first['sort_date'],
        'category'  => $first['category'],
    ];
}

// Tufte sidenote

function sn($html)
{
    static $n = 0;
    $id = 'sn-' . ++$n;
    return '<label for="' . $id . '" class="margin-toggle sidenote-number"></label>'
        . '<input type="checkbox" id="' . $id . '" class="margin-toggle"/>'
        . '<span class="sidenote">' . $html . '</span>';
}

// Tufte margin note: unnumbered, shown behind a ⊕ toggle on narrow screens
function mn($html)
{
    static $n = 0;
    $id = 'mn-' . ++$n;
    return '<label for="' . $id . '" class="margin-toggle">&#8853;</label>'
        . '<input type="checkbox" id="' . $id . '" class="margin-toggle"/>'
        . '<span class="marginnote">' . $html . '</span>';
}