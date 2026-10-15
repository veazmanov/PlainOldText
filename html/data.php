<?php
// ./html/data.php

$site_name = "Plain Old Text";

$articles_base = dirname(__DIR__) . '/oldnews-articles-only';

// Values for "category" in data.json, with their labels in the catalogue filter.
$categories = [
    'newspaper'    => 'Newspapers',
    'letter'       => 'Letters',
    'book-chapter' => 'Book chapters',
];

// One record per article folder that has a data.json, keyed by folder name (the URL slug).
$metadata = [];

if (is_dir($articles_base)) {
    foreach (array_diff(scandir($articles_base), ['.', '..']) as $item) {
        $json_file = $articles_base . '/' . $item . '/data.json';
        $json_data = is_file($json_file) ? json_decode(file_get_contents($json_file), true) : null;
        if (!$json_data) {
            continue;
        }

        $year  = $json_data['year'] ?? '0000';
        $month = $json_data['month'] ?? '00';
        $day   = $json_data['day'] ?? '00';
        $date_obj = DateTime::createFromFormat('!Y-n-j', "$year-$month-$day");

        $metadata[$item] = [
            'title'      => $json_data['title'] ?? 'Untitled Article',
            'pub'        => ($json_data['newspaper'] ?? '') ?: 'Archive Entry',
            'category'   => $json_data['category'] ?? 'newspaper',
            'date'       => $date_obj ? $date_obj->format('j M, Y') : 'Undated',
            'sort_date'  => $date_obj ? $date_obj->format('Y-m-d') : '0000-00-00',
            'summary'    => $json_data['summary'] ?? '',
            'featured'   => !empty($json_data['featured']),
            'source_url' => $json_data['source_url'] ?? null,
            'page'       => $json_data['page'] ?? null,
            'scans'      => $json_data['scans'] ?? [],
        ];
    }
}
