<?php
// ./sync_scans.php
// Writes each article's scan filenames into markdown/<article>/data.json as "scans",
// read from the photo host's JSON directory listing. Run after deploying new photos,
// then ./generate_html.sh copies data.json through to html_polished.
// Usage: php sync_scans.php [article-folder ...]   (no args = all articles)

$articles_base = __DIR__ . '/markdown';
$photos_url_base = 'https://oldnews-photos.vjbe.net';

function list_scans($photos_url_base, $folder)
{
    $context = stream_context_create(['http' => [
        'header' => "Accept: application/json\r\n",
        'timeout' => 15,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents($photos_url_base . '/' . rawurlencode($folder) . '/', false, $context);
    $entries = $raw !== false ? json_decode($raw, true) : null;
    if (!is_array($entries)) {
        return null;
    }

    // Group by base name so one format is picked per page: webp, then png, then jpg.
    $groups = [];
    foreach ($entries as $entry) {
        if (empty($entry['is_dir']) && preg_match('/^(.*)\.(webp|png|jpg|jpeg)$/i', $entry['name'], $m)) {
            $groups[$m[1]][strtolower($m[2])] = $entry['name'];
        }
    }

    $scans = [];
    foreach ($groups as $exts) {
        $scans[] = $exts['webp'] ?? $exts['png'] ?? $exts['jpg'] ?? $exts['jpeg'];
    }
    natcasesort($scans);
    return array_values($scans);
}

// Set "scans" in place so the rest of the file keeps its formatting.
function write_scans($json_file, $scans)
{
    $text = file_get_contents($json_file);
    $text = preg_replace('/,\s*"scans"\s*:\s*\[[^\]]*\]/', '', $text);

    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    $items = array_map(fn($s) => '        ' . json_encode($s, $flags), $scans);
    $block = ",\n    \"scans\": [" . ($items ? "\n" . implode(",\n", $items) . "\n    " : '') . ']';

    $end = strrpos($text, '}');
    $text = rtrim(substr($text, 0, $end)) . $block . "\n" . substr($text, $end);

    if (json_decode($text, true) === null) {
        throw new RuntimeException("Refusing to write invalid JSON to $json_file");
    }
    file_put_contents($json_file, $text);
}

$folders = array_slice($argv, 1) ?: array_map('basename', glob($articles_base . '/*', GLOB_ONLYDIR));
foreach ($folders as $folder) {
    $json_file = $articles_base . '/' . $folder . '/data.json';
    if (!is_file($json_file)) {
        fwrite(STDERR, "skip $folder: no data.json\n");
        continue;
    }
    $scans = list_scans($photos_url_base, $folder);
    if ($scans === null) {
        fwrite(STDERR, "skip $folder: no photo folder on $photos_url_base\n");
        continue;
    }
    write_scans($json_file, $scans);
    echo "$folder: " . count($scans) . " scans\n";
}
