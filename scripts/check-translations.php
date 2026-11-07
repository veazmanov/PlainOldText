<?php
// ./scripts/check-translations.php
// Checks each articles/<slug>/translation.md lines up with its main.md, as the site pairs them by position (the
// Both view and "Show Latin"): their built HTML must have the same sequence of top-level blocks, leaving out the
// editorial note and the footnotes. Prints a line per mismatch and exits 1 if there are any.
// Usage: php scripts/check-translations.php   (after scripts/build-articles.sh)

$built = dirname(__DIR__) . '/oldnews-articles-only';

// The tag names of a built file's top-level blocks.
function block_tags($file)
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . file_get_contents($file) . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $tags = [];
    foreach ($doc->getElementById('root')->childNodes as $child) {
        if (!$child instanceof DOMElement) {
            continue;
        }
        $class = ' ' . $child->getAttribute('class') . ' ';
        if ($child->nodeName !== 'nav' && !preg_match('/ (editorial-note|footnotes) /', $class)) {
            $tags[] = $child->nodeName;
        }
    }
    return $tags;
}

$checked = 0;
$failing = 0;
foreach (glob("$built/*/translation.html") as $translation) {
    $checked++;
    $text = block_tags(dirname($translation) . '/main.html');
    $english = block_tags($translation);
    if ($text !== $english) {
        $failing++;
        $at = count(array_diff_assoc($text, $english)) ? array_key_first(array_diff_assoc($text, $english)) : min(count($text), count($english));
        echo basename(dirname($translation)) . ': ' . count($text) . ' blocks in the text, ' . count($english) . " in the translation; they differ at block $at\n";
    }
}
echo "$checked translations checked, " . ($checked - $failing) . " ok, $failing failing\n";
exit($failing ? 1 : 0);
