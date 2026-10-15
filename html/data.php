<?php
// ./html/data.php

$site_name = "Plain Old Text";

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
];

// A source folder may hold several transcriptions, named <edition>.md. These are listed first
// (the first present is the default); others follow alphabetically, labelled by file name.
$edition_labels = [
    'reading'    => 'Reading edition',
    'diplomatic' => 'Diplomatic transcription',
];

// The built editions of an article, default first: [id => ['label', 'html', 'md']].
function article_editions($uri)
{
    global $articles_base, $edition_labels;
    $editions = [];
    foreach (glob($articles_base . '/' . $uri . '/*.html') as $html) {
        $id = basename($html, '.html');
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

// One record per article folder that has a data.json, keyed by folder name (the URL slug).
$metadata = [];

if (is_dir($articles_base)) {
    foreach (array_diff(scandir($articles_base), ['.', '..']) as $item) {
        $json_file = $articles_base . '/' . $item . '/data.json';
        $json_data = is_file($json_file) ? json_decode(file_get_contents($json_file), true) : null;
        if (!$json_data) {
            continue;
        }

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
            'title'      => $json_data['title'] ?? 'Untitled Article',
            // "publication" for books and other non-newspaper sources
            'pub'        => ($json_data['publication'] ?? $json_data['newspaper'] ?? '') ?: 'Archive Entry',
            'category'   => $json_data['category'] ?? 'newspaper',
            'date'       => $date_obj ? $date_obj->format($date_format) : 'Undated',
            'sort_date'  => $date_obj ? $date_obj->format('Y-m-d') : '0000-00-00',
            'summary'    => $json_data['summary'] ?? '',
            'featured'   => !empty($json_data['featured']),
            'source_url' => $json_data['source_url'] ?? null,
            'page'       => $json_data['page'] ?? null,
            'scans'      => $json_data['scans'] ?? [],
            'scans_pdf'  => $json_data['scans_pdf'] ?? null,
        ];
    }
}
