<?php
// ./html/notes.php
// Editor's notes ("notes" in data.json) shown as lettered Tufte sidenotes, distinct from an author's own numbered
// footnotes. Each note's anchor ("on") is found in the edition's HTML as a reader sees the text, the same way
// scripts/check-notes.php finds it in the Markdown, and the note is placed after the anchor's last word.
// A note whose anchor doesn't match exactly once is skipped (and logged).

// The article HTML with the notes for this edition inserted.
function apply_editor_notes($html, $notes, $edition)
{
    $notes = array_values(array_filter($notes ?? [], fn($n) => ($n['edition'] ?? 'main') === $edition));
    if (!$notes) {
        return $html;
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="editor-notes-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('editor-notes-root');

    $n = 0;
    foreach ($notes as $note) {
        $anchor = notes_normalise(trim((string) ($note['on'] ?? '')))[0];
        [$text, $map, $nodes] = notes_reader_text($root);
        $count = $anchor === '' ? 0 : mb_substr_count($text, $anchor);
        if ($count !== 1) {
            error_log("Plain Old Text: note anchor \"{$note['on']}\" matches $count times; not shown");
            continue;
        }
        // The anchor's last character, in its text node; the note goes after it, and after any punctuation that
        // follows in the same text node, as note markers do ("Hermippus,ᵃ" rather than "Hermippusᵃ,")
        $end = $map[mb_strpos($text, $anchor) + mb_strlen($anchor) - 1];
        [$node, $offset] = $nodes[$end];
        $rest = mb_substr($node->data, $offset + 1);
        $offset += preg_match('/^[,.;:?!)\]’”\'"]+/u', $rest, $m) ? mb_strlen($m[0]) : 0;
        $after = $node->splitText($offset + 1);
        $after->parentNode->insertBefore(notes_sidenote($doc, $note, ++$n), $after);
    }

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}

// The text a reader sees under $root, normalised, with for each of its characters the position of the raw
// character it came from, and for each raw position its [text node, character offset].
function notes_reader_text($root)
{
    $raw = [];
    $nodes = [];
    $walk = function ($el) use (&$walk, &$raw, &$nodes) {
        foreach ($el->childNodes as $child) {
            if ($child instanceof DOMText) {
                foreach (preg_split('//u', $child->data, -1, PREG_SPLIT_NO_EMPTY) as $i => $ch) {
                    $raw[] = $ch;
                    $nodes[] = [$child, $i];
                }
            } elseif ($child instanceof DOMElement) {
                $class = ' ' . $child->getAttribute('class') . ' ';
                // Not part of the text: the editorial note, footnote markers and the footnotes, notes already added
                if (preg_match('/ (editorial-note|footnote-ref|footnotes|sidenote|margin-toggle) /', $class)) {
                    continue;
                }
                $block = !in_array($child->nodeName, ['em', 'strong', 'a', 'span', 'sup', 'sub', 'small', 'code', 'i', 'b'], true);
                if ($block) {
                    $raw[] = ' ';
                    $nodes[] = null;
                }
                $walk($child);
                if ($block) {
                    $raw[] = ' ';
                    $nodes[] = null;
                }
            }
        }
    };
    $walk($root);

    [$text, $map] = notes_normalise($raw);
    return [$text, $map, $nodes];
}

// Collapses whitespace and straightens curly quotes (pandoc makes them curly, data.json has them straight).
// Takes a string or an array of characters; returns the text and, per character, its index in the input.
function notes_normalise($chars)
{
    if (is_string($chars)) {
        $chars = preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY);
    }
    $quotes = ['‘' => "'", '’' => "'", '“' => '"', '”' => '"'];
    $out = [];
    $map = [];
    $space = true; // drop leading whitespace
    foreach ($chars as $i => $ch) {
        if (preg_match('/^\s$/u', $ch)) {
            if (!$space) {
                $out[] = ' ';
                $map[] = $i;
                $space = true;
            }
            continue;
        }
        $out[] = $quotes[$ch] ?? $ch;
        $map[] = $i;
        $space = false;
    }
    return [implode('', $out), $map];
}

// A lettered sidenote (Tufte's markup), with "Sic." before a note on a misprint kept in the text.
function notes_sidenote($doc, $note, $n)
{
    $type = in_array($note['type'] ?? '', ['explanatory', 'sic', 'query'], true) ? $note['type'] : 'explanatory';
    $id = 'en-' . $n;
    // Inline Markdown in the note: *italics*
    $body = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', htmlspecialchars((string) $note['note'], ENT_QUOTES));
    if ($type === 'sic') {
        $body = '<em>Sic.</em> ' . $body;
    }
    $markup = '<label for="' . $id . '" class="margin-toggle sidenote-number editor-note-number"></label>'
        . '<input type="checkbox" id="' . $id . '" class="margin-toggle"/>'
        . '<span class="sidenote editor-note editor-note-' . $type . '">' . $body . '</span>';

    $fragment = $doc->createDocumentFragment();
    $fragment->appendXML($markup);
    return $fragment;
}
