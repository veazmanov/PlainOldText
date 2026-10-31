<?php
// ./scripts/assign-ids.php
// Gives each articles/<slug>/data.json without an "id" a new random 5-character one, used in the
// article's stable URL (/stable/<id>), and likewise each collection in articles/collections.json.
// IDs are permanent: existing ones are never changed.
// So does each commentary/<name>.md without an "id:" in its front matter (/commentary/<id>).
// Usage: php scripts/assign-ids.php [article-folder ...]   (no args = all articles and commentary)

$articles_base = dirname(__DIR__) . '/articles';
$commentary_base = dirname(__DIR__) . '/commentary';
$alphabet = '0123456789abcdefghjkmnpqrstvwxyz';

// A random id not yet in use, which is then marked as used.
function new_id()
{
    global $alphabet, $used;
    do {
        $id = '';
        for ($i = 0; $i < 5; $i++) {
            $id .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
    } while (isset($used[$id]));
    $used[$id] = true;
    return $id;
}

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

$collections_file = $articles_base . '/collections.json';
$collections = is_file($collections_file) ? json_decode(file_get_contents($collections_file), true) : [];
if (!is_array($collections)) {
    throw new RuntimeException("Invalid JSON in $collections_file");
}

// Collect every id in use, across all articles and collections, so new ones are unique.
$used = [];
foreach ($collections as $c) {
    if (($c['id'] ?? '') !== '') {
        $used[$c['id']] = true;
    }
}
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

// A commentary's id, from the "id:" line of its front matter: the id, '' if it has none, or null if it has no front matter
function commentary_id($text)
{
    if (!preg_match('/^---\n(.*?)\n---\n/s', $text, $m)) {
        return null;
    }
    return preg_match('/^id:[ \t]*(\S*)[ \t]*$/m', $m[1], $i) ? trim($i[1], '"\'') : '';
}
foreach (glob($commentary_base . '/*.md') as $md_file) {
    $id = commentary_id(file_get_contents($md_file));
    if ($id !== null && $id !== '') {
        $used[$id] = true;
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

    $id = new_id();

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

// Collections, when run on all articles
if (count($argv) === 1) {
    $changed = false;
    foreach ($collections as $key => $c) {
        if (($c['id'] ?? '') === '') {
            $collections[$key] = ['id' => new_id()] + $c;
            $changed = true;
            echo "collection $key: {$collections[$key]['id']}\n";
        }
    }
    if ($changed) {
        file_put_contents($collections_file,
            json_encode($collections, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }
}

// Commentary: put the id first in the front matter, or fill in an empty "id:"
foreach (array_slice($argv, 1) ? [] : glob($commentary_base . '/*.md') as $md_file) {
    $text = file_get_contents($md_file);
    $old = commentary_id($text);
    if ($old === null) {
        fwrite(STDERR, "skip " . basename($md_file) . ": no front matter\n");
        continue;
    }
    if ($old !== '') {
        continue;
    }
    $id = new_id();
    $text = preg_match('/^id:.*$/m', $text)
        ? preg_replace('/^id:.*$/m', 'id: ' . $id, $text, 1)
        : preg_replace('/^---\n/', "---\nid: $id\n", $text, 1);
    if (commentary_id($text) !== $id) {
        throw new RuntimeException("Refusing to write a broken front matter to $md_file");
    }
    file_put_contents($md_file, $text);
    echo 'commentary/' . basename($md_file) . ": $id\n";
}
