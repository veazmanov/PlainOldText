<?php
// ./scripts/check-notes.php
// Checks the "notes" in each articles/<slug>/data.json: every note's anchor ("on") must match its edition's
// text exactly once. The text is compared as a reader sees it: without the editorial note, Markdown
// emphasis (* and _), footnotes (^[...], [^n] and their definitions) and backslash escapes, with whitespace
// collapsed. Prints a line per note and exits 1 if any anchor matches 0 or more than 1 times.
// Usage: php scripts/check-notes.php [article-folder ...]   (no args = all articles)

$articles_base = dirname(__DIR__) . '/articles';
$types = ['explanatory', 'sic', 'query'];

// The text of a Markdown edition as a reader sees it.
function reader_text($markdown)
{
    // The editorial note block
    $text = preg_replace('/^:::\s*\{\.editorial-note\}.*?^:::\s*$/ms', '', $markdown);

    // Footnote definitions: "[^n]: ..." and their indented continuation paragraphs
    $text = preg_replace('/^\[\^[^\]]+\]:.*?(?=\n(?!\n| {4}|\t)|\z)/ms', '', $text);
    // Footnote references
    $text = preg_replace('/\[\^[^\]]+\]/', '', $text);
    // Inline notes ^[...], which may contain brackets of their own
    $text = preg_replace('/\^(\[(?:[^\[\]]++|(?1))*\])/', '', $text);

    // Backslash escapes, then emphasis
    $text = preg_replace('/\\\\(.)/s', '$1', $text);
    $text = str_replace(['*', '_'], '', $text);

    return trim(preg_replace('/\s+/u', ' ', $text));
}

$folders = array_slice($argv, 1) ?: array_map('basename', glob($articles_base . '/*', GLOB_ONLYDIR));
$checked = 0;
$bad = 0;
foreach ($folders as $folder) {
    $json_file = $articles_base . '/' . $folder . '/data.json';
    $data = is_file($json_file) ? json_decode(file_get_contents($json_file), true) : null;
    if (!is_array($data)) {
        fwrite(STDERR, "skip $folder: no valid data.json\n");
        continue;
    }
    if (empty($data['notes'])) {
        continue;
    }

    $texts = [];
    foreach ($data['notes'] as $i => $note) {
        $checked++;
        $edition = $note['edition'] ?? 'main';
        $anchor = trim(preg_replace('/\s+/u', ' ', (string) ($note['on'] ?? '')));
        $problems = [];
        if (!in_array($note['type'] ?? null, $types, true)) {
            $problems[] = 'type must be one of ' . implode(', ', $types);
        }
        if (trim((string) ($note['note'] ?? '')) === '') {
            $problems[] = 'empty note';
        }

        $md_file = $articles_base . '/' . $folder . '/' . $edition . '.md';
        if (!is_file($md_file)) {
            $problems[] = "no edition '$edition'";
            $count = 0;
        } else {
            $texts[$edition] ??= reader_text(file_get_contents($md_file));
            $count = $anchor === '' ? 0 : substr_count($texts[$edition], $anchor);
            if ($count !== 1) {
                $problems[] = "anchor matches $count times";
            }
        }

        $status = $problems ? 'FAIL' : 'ok  ';
        echo "$status $folder #$i [$edition] ($count) " . json_encode($anchor, JSON_UNESCAPED_UNICODE)
            . ($problems ? ' — ' . implode('; ', $problems) : '') . "\n";
        if ($problems) {
            $bad++;
        }
    }
}

echo "\n$checked notes checked, " . ($checked - $bad) . " ok, $bad failing\n";
exit($bad ? 1 : 0);
