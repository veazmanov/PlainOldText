<?php
// ./scripts/assign-ids.php
// Gives each articles/<slug>/data.json without an "id" a new random 5-character one, used in the
// article's stable URL (/stable/<id>). IDs are permanent: existing ones are never changed.
// Usage: php scripts/assign-ids.php [article-folder ...]   (no args = all articles)

$articles_base = dirname(__DIR__) . '/articles';
$alphabet = '0123456789abcdefghjkmnpqrstvwxyz';

// Insert "id" as the first key in place so the rest of the file keeps its formatting.
function write_id($json_file, $id)
{
    $text = file_get_contents($json_file);
    if (!preg_match('/^\s*\{(\s*)("?)/', $text, $m, PREG_OFFSET_CAPTURE)) {
        throw new RuntimeException("Not a JSON object: $json_file");
    }
    $open = strpos($text, '{');
    $empty = $m[2][0] === '';
    // Indent like the first existing key, or four spaces
    $indent = preg_match('/\n([ \t]*)$/', $m[1][0], $i) ? $i[1] : '    ';
    $line = "\n" . $indent . '"id": ' . json_encode($id) . ($empty ? "\n" : ',');
    $text = substr($text, 0, $open + 1) . $line . ($empty ? ltrim(substr($text, $open + 1)) : substr($text, $open + 1));

    $data = json_decode($text, true);
    if (!is_array($data) || ($data['id'] ?? null) !== $id) {
        throw new RuntimeException("Refusing to write invalid JSON to $json_file");
    }
    file_put_contents($json_file, $text);
}

// Collect every id in use, across all articles, so new ones are unique.
$used = [];
foreach (glob($articles_base . '/*/data.json') as $json_file) {
    $data = json_decode(file_get_contents($json_file), true);
    if (!is_array($data)) {
        fwrite(STDERR, "warning: invalid JSON in $json_file\n");
        continue;
    }
    if (isset($data['id']) && $data['id'] !== '') {
        $used[$data['id']] = true;
    }
}

$folders = array_slice($argv, 1) ?: array_map('basename', glob($articles_base . '/*', GLOB_ONLYDIR));
foreach ($folders as $folder) {
    $json_file = $articles_base . '/' . $folder . '/data.json';
    if (!is_file($json_file)) {
        fwrite(STDERR, "skip $folder: no data.json\n");
        continue;
    }
    $data = json_decode(file_get_contents($json_file), true);
    if (!is_array($data)) {
        fwrite(STDERR, "skip $folder: invalid JSON\n");
        continue;
    }
    if (isset($data['id']) && $data['id'] !== '') {
        continue;
    }

    do {
        $id = '';
        for ($i = 0; $i < 5; $i++) {
            $id .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
    } while (isset($used[$id]));
    $used[$id] = true;

    if (array_key_exists('id', $data)) {
        // An empty "id": "" (as in data.example.json): fill it in rather than adding a second key
        $text = preg_replace('/"id"\s*:\s*""/', '"id": ' . json_encode($id), file_get_contents($json_file), 1);
        if ((json_decode($text, true)['id'] ?? null) !== $id) {
            throw new RuntimeException("Refusing to write invalid JSON to $json_file");
        }
        file_put_contents($json_file, $text);
    } else {
        write_id($json_file, $id);
    }
    echo "$folder: $id\n";
}
