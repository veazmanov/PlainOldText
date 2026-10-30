<?php
// php scripts/tcp-to-articles.php <config.json> <tcp.xml>
// Converts an EEBO-TCP text (TEI P5 XML from github.com/textcreationpartnership) into articles: one folder per
// division named in the config, each with a semi-diplomatic diplomatic.md. data.json is written only when the
// folder has none, so hand edits to it (id, summary, notes) survive a re-run; run assign-ids.php afterwards.
//
// The config (see scripts/tcp/) names the TCP id, the divisions to convert, and the Internet Archive scan that
// page breaks link to. TCP marks a recto's folio number on its page break (<pb n="12">); the scan's leaf for
// folio N recto is a*N + b (scan.recto_leaf = [a, b]), and the leaves of unnumbered pages are counted from the
// nearest page break whose leaf is known, without counting across a page TCP's copy is missing.
//
// Conventions, as in alexis-1595-preface/diplomatic.md: spelling, u/v and i/j as printed; long s as s; line-end
// hyphens rejoined; illegible letters kept as TCP's • (a letter) and 〈◊〉 (a word or more); margin notes as
// footnotes; each page as an inline [f. 12r] linking to its scan.

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php scripts/tcp-to-articles.php <config.json> <tcp.xml>\n");
    exit(1);
}

$config = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$articles_dir = dirname(__DIR__) . '/articles';

$doc = new DOMDocument();
$doc->load($argv[2], LIBXML_NONET);
$xp = new DOMXPath($doc);
$xp->registerNamespace('t', 'http://www.tei-c.org/ns/1.0');

// ---- Page breaks: their order and scan leaves ----

// A number for a node that stays the same however the node is reached (PHP's DOM objects don't)
function node_key(DOMNode $n): int
{
    static $keys = [];
    return $keys[$n->getNodePath()] ??= count($keys);
}

// A page break followed by a "duplicate" gap is a second photograph of a page already given: it is no page
function is_duplicate(DOMElement $pb): bool
{
    for ($n = $pb->nextSibling; $n && !$n instanceof DOMElement; $n = $n->nextSibling);
    return $n && $n->localName === 'gap' && $n->getAttribute('reason') === 'duplicate';
}

// The next or previous element beside a node, skipping whitespace
function sibling(DOMNode $n, string $dir): ?DOMElement
{
    for ($n = $n->$dir; $n && !$n instanceof DOMElement; $n = $n->$dir);
    return $n;
}

function is_missing(?DOMElement $n): bool
{
    return $n && $n->localName === 'gap' && $n->getAttribute('reason') === 'missing';
}

// Blank pages before the title page, which TCP photographs as part of its first opening but a scan may not have
$blank = [];   // node_key(pb) => true
foreach ($xp->query('//t:text//t:pb') as $pb) {
    if (sibling($pb, 'nextSibling')?->localName !== 'pb') {
        break;
    }
    $blank[node_key($pb)] = true;
}

// Every page break and missing-page gap in document order; a missing gap stands for pages whose number is unknown.
// TCP sometimes puts the gap just after the page break of the page that follows the missing ones: swap them back.
$events = [];
foreach ($xp->query('//t:text//t:pb | //t:text//t:gap[@reason="missing"]') as $node) {
    if ($node->nodeName === 'pb' && (is_duplicate($node) || isset($blank[node_key($node)]))) {
        continue;
    }
    $last = end($events);
    if ($node->nodeName === 'gap' && $last && $last->nodeName === 'pb' && sibling($node, 'previousSibling') === $last) {
        array_splice($events, -1, 0, [$node]);
        continue;
    }
    $events[] = $node;
}
[$a, $b] = $config['scan']['recto_leaf'];

// The folio numbers TCP gives (<pb n="12">) place a recto on the scan, but some are misprinted in the book (part 1
// numbers ff. 62-64 as 64, 63, 62) or mistyped by TCP (n="1011" for 111). Keep the longest chain of them that can
// be right, where each comes after the last and is at least as many leaves on as there are pages between them
// (TCP's copy can lack pages, but has none the scan lacks). The title page, the first page break, is leaf 0.
$candidates = [];   // event index => leaf
foreach ($events as $i => $node) {
    if ($node->nodeName === 'pb' && ctype_digit($node->getAttribute('n'))) {
        $candidates[$i] = $a * (int) $node->getAttribute('n') + $b;
    }
}
$first_pb = array_key_first(array_filter($events, fn($n) => $n->nodeName === 'pb'));
$candidates = [$first_pb => 0] + $candidates;
$idx = array_keys($candidates);
$chain = [];   // position in $idx => [length of the best chain ending here, the position before it]
foreach ($idx as $k => $i) {
    $chain[$k] = [1, null];
    for ($m = 0; $m < $k; $m++) {
        $j = $idx[$m];
        if ($candidates[$i] - $candidates[$j] >= $i - $j && $chain[$m][0] + 1 > $chain[$k][0]) {
            $chain[$k] = [$chain[$m][0] + 1, $m];
        }
    }
}
$lengths = array_column($chain, 0);
$known = [];
for ($k = array_search(max($lengths), $lengths, true); $k !== null; $k = $chain[$k][1]) {
    $known[$idx[$k]] = $candidates[$idx[$k]];
}
foreach (array_diff_key($candidates, $known) as $i => $l) {
    fwrite(STDERR, 'Page break ' . $events[$i]->getAttribute('facs') . ': folio ' . $events[$i]->getAttribute('n')
        . " is out of sequence, ignored\n");
}

// Other pages are counted from the nearer folio number on either side, without counting across a missing-page gap.
// If neither is nearer, from the one after: TCP's images are openings, a verso beside the next recto.
$leaf = [];   // node_key(pb) => scan leaf, 0-based
foreach ($events as $i => $node) {
    if ($node->nodeName !== 'pb') {
        continue;
    }
    $guesses = [];   // distance => leaf
    foreach ([1, -1] as $dir) {
        for ($j = $i; isset($events[$j]); $j += $dir) {
            if ($j !== $i && $events[$j]->nodeName === 'gap') {
                break;
            }
            if (isset($known[$j])) {
                $guesses[abs($i - $j)] ??= $known[$j] + ($i - $j);
                break;
            }
        }
    }
    if (!$guesses) {
        fwrite(STDERR, 'Page break ' . $node->getAttribute('facs') . ": no leaf, left unlinked\n");
        continue;
    }
    ksort($guesses);
    $leaf[node_key($node)] = reset($guesses);
}

// Pages TCP's copy lacks: at a missing-page gap, or unmarked, where the leaves of two page breaks in a row jump
$missing = [];   // node_key(gap or the pb after the jump) => [first leaf, last leaf]
$prev = null;
foreach ($events as $i => $node) {
    if ($node->nodeName === 'gap') {
        continue;
    }
    $to = $leaf[node_key($node)] ?? null;
    $from = $prev !== null ? ($leaf[node_key($events[$prev])] ?? null) : null;
    if ($from !== null && $to !== null && $to - $from >= 2) {
        // Marked by the gap just before this page break, if there is one
        $gap = $events[$i - 1]->nodeName === 'gap' ? $events[$i - 1] : $node;
        $missing[node_key($gap)] = [$from + 1, $to - 1];
        if ($gap === $node) {
            fwrite(STDERR, "TCP lacks scan leaves " . ($from + 1) . '-' . ($to - 1) . " without marking them\n");
        }
    }
    $prev = $i;
}

// A leaf's folio label, counting from the first numbered recto
function folio_label(int $leaf, int $a, int $b): string
{
    $offset = $leaf - $b;
    if ($offset < $a) {
        return '';
    }
    return 'f. ' . intdiv($offset, $a) . ($offset % $a === 0 ? 'r' : 'v');
}

function scan_url(array $scan, int $leaf): string
{
    return 'https://archive.org/details/' . $scan['archive_id'] . '/page/n' . $leaf;
}

// ---- Text ----

// Markdown's special characters in running text
function md_escape(string $s): string
{
    return preg_replace('/([\\\\*_\[\]<>`^~#|])/u', '\\\\$1', $s);
}

// The inline Markdown of a node's contents
function inline(DOMNode $node, array $ctx): string
{
    $out = '';
    foreach ($node->childNodes as $child) {
        $out .= inline_node($child, $ctx);
    }
    return $out;
}

function inline_node(DOMNode $n, array $ctx): string
{
    if ($n instanceof DOMText) {
        return md_escape(str_replace('ſ', 's', $n->data));
    }
    if (!$n instanceof DOMElement) {
        return '';
    }
    switch ($n->localName) {
        case 'g':
            // A line-end hyphen is dropped, rejoining the word, along with the line break after it
            if (in_array($n->getAttribute('ref'), ['char:EOLhyphen', 'char:EOLunhyphen'], true)) {
                return "\x02";
            }
            return md_escape($n->textContent);
        case 'hi':
            $inner = inline($n, $ctx);
            if ($n->getAttribute('rend') === 'sup') {
                return '^' . str_replace(' ', '\\ ', trim($inner)) . '^';
            }
            return "\x03" . $inner . "\x04";
        case 'gap':
            if ($n->getAttribute('reason') === 'duplicate') {
                return '';
            }
            if ($n->getAttribute('reason') === 'missing') {
                // Given with the page break before it, if there is one
                return sibling($n, 'previousSibling')?->localName === 'pb' ? '' : "\x05" . node_key($n) . "\x05";
            }
            // TCP's own placeholder: • for each illegible letter, 〈◊〉 for a word or more
            $desc = $n->getElementsByTagName('desc')->item(0);
            return $desc ? $desc->textContent : '〈◊〉';
        case 'pb':
            if (is_duplicate($n) || isset($ctx['blank'][node_key($n)])) {
                return '';
            }
            // Pages missing before this one: unmarked, or marked by a gap TCP put just after this page break
            $gap = is_missing(sibling($n, 'nextSibling')) ? sibling($n, 'nextSibling') : $n;
            $missing = isset($ctx['missing'][node_key($gap)]) ? "\x05" . node_key($gap) . "\x05" : '';
            return $missing . "\x01" . node_key($n) . "\x01";
        case 'note':
            return '^[' . trim(collapse(inline($n, $ctx))) . ']';
        case 'lb':
            return ' ';
        default:
            // seg (decorated initials), term, date, label, … are just their text
            return inline($n, $ctx);
    }
}

function collapse(string $s): string
{
    $s = preg_replace('/\x02\s*/u', '', $s);
    return preg_replace('/\s+/u', ' ', $s);
}

// Turn the placeholders of an inline run into Markdown
function finish(string $s, array $ctx): string
{
    $s = trim(collapse($s));
    // Italics: spaces belong outside the asterisks, and empty runs vanish
    $s = preg_replace_callback('/\x03(.*?)\x04/us', function ($m) {
        if (trim($m[1]) === '') {
            return $m[1];
        }
        preg_match('/^(\s*)(.*?)(\s*)$/us', $m[1], $p);
        return $p[1] . '*' . $p[2] . '*' . $p[3];
    }, $s);
    // A page break inside a word (after a line-end hyphen) goes after the word
    $s = preg_replace('/(\w+)(\x01\d+\x01)(\w+)/u', '$1$3$2', $s);
    $s = preg_replace_callback('/\s*\x01(\d+)\x01\s*/u', function ($m) use ($ctx) {
        $id = (int) $m[1];
        if (!isset($ctx['leaf'][$id])) {
            return ' ';
        }
        $l = $ctx['leaf'][$id];
        $text = folio_label($l, ...$ctx['config']['scan']['recto_leaf']) ?: 'scan ' . ($l + 1);
        return ' [[' . $text . '](' . scan_url($ctx['config']['scan'], $l) . ')] ';
    }, $s);
    $s = preg_replace_callback('/\s*\x05(\d+)\x05\s*/u', function ($m) use ($ctx) {
        $range = $ctx['missing'][(int) $m[1]] ?? null;
        $pages = '';
        if ($range) {
            $links = [];
            for ($l = $range[0]; $l <= $range[1]; $l++) {
                $links[] = '[' . (folio_label($l, ...$ctx['config']['scan']['recto_leaf']) ?: 'scan ' . ($l + 1))
                    . '](' . scan_url($ctx['config']['scan'], $l) . ')';
            }
            $pages = ': ' . implode(', ', $links);
        }
        $these = $range && $range[1] > $range[0] ? 'these pages' : 'this page';
        return " *[Not in the TCP transcription, whose copy lacks $these" . $pages . ']* ';
    }, $s);
    return trim(preg_replace('/ {2,}/', ' ', $s));
}

// A paragraph must not begin like a Markdown list item or heading
function block_safe(string $s): string
{
    return preg_replace('/^(\d+)\.(\s)/u', '$1\\.$2', preg_replace('/^([-+])(\s)/u', '\\\\$1$2', $s));
}

// The Markdown blocks of a division, in order
function blocks(DOMElement $div, array $ctx, int $depth): array
{
    $out = [];
    foreach ($div->childNodes as $c) {
        if (!$c instanceof DOMElement) {
            continue;
        }
        $text = fn() => block_safe(finish(inline($c, $ctx), $ctx));
        switch ($c->localName) {
            case 'div':
                $out = array_merge($out, blocks($c, $ctx, $depth + 1));
                break;
            case 'head':
                if ($c->getAttribute('type') === 'sub') {
                    $out[] = '*' . $text() . '*';
                } elseif ($depth === 0) {
                    // The division's own heading is the article's title, written by the caller
                } else {
                    // Text-sized: a book's heads are sentences, such as the title of each recipe
                    $out[] = '#### ' . finish(inline($c, $ctx), $ctx);
                }
                break;
            case 'list':
                foreach ($c->getElementsByTagName('item') as $item) {
                    $out[] = '- ' . finish(inline($item, $ctx), $ctx);
                }
                break;
            case 'trailer':
            case 'closer':
            case 'signed':
            case 'dateline':
            case 'salute':
                $out[] = '*' . $text() . '*';
                break;
            case 'pb':
                // Between paragraphs: put the page marker at the start of the next block
                $out[] = ['pb' => finish(inline_node($c, $ctx), $ctx)];
                break;
            case 'gap':
                $out[] = finish(inline_node($c, $ctx), $ctx);
                break;
            default:
                // p, opener, argument, …
                $t = $text();
                if ($t !== '') {
                    $out[] = $t;
                }
        }
    }
    // Join a lone page marker to the paragraph after it, so it doesn't stand as a paragraph of its own
    $joined = [];
    $carry = '';
    foreach ($out as $block) {
        if (is_array($block)) {
            $carry .= $block['pb'] === '' ? '' : $block['pb'] . ' ';
            continue;
        }
        if ($block === '') {
            continue;
        }
        if ($carry !== '' && !str_starts_with($block, '#') && !str_starts_with($block, '- ')) {
            $block = $carry . $block;
        } elseif ($carry !== '') {
            $joined[] = trim($carry);
        }
        $carry = '';
        $joined[] = $block;
    }
    if ($carry !== '') {
        $joined[] = trim($carry);
    }
    return $joined;
}

// ---- Articles ----

$scan = $config['scan'];
foreach ($config['articles'] as $article) {
    $divs = [];
    foreach ($article['divs'] as $query) {
        foreach ($xp->query($query) as $d) {
            $divs[] = $d;
        }
    }
    if (!$divs) {
        fwrite(STDERR, "{$article['slug']}: no division matches, skipped\n");
        continue;
    }

    $ctx = ['config' => $config, 'leaf' => $leaf, 'missing' => $missing, 'blank' => $blank];

    // The page the article begins on: its first page break, if no text comes before it, or else the last one before
    $first_pb = null;
    $text_before = false;
    $walk = function (DOMNode $n) use (&$walk, &$first_pb, &$text_before) {
        foreach ($n->childNodes as $c) {
            if ($first_pb) {
                return;
            }
            if ($c instanceof DOMText && trim($c->data) !== '') {
                $text_before = true;
            } elseif ($c instanceof DOMElement && $c->localName === 'pb') {
                $first_pb = $c;
            } elseif ($c instanceof DOMElement) {
                $walk($c);
            }
        }
    };
    $walk($divs[0]);
    $start_pb = ($first_pb && !$text_before) ? $first_pb : ($xp->query('preceding::t:pb[1]', $divs[0])->item(0) ?? $first_pb);
    $start = $start_pb ? ($leaf[node_key($start_pb)] ?? null) : null;

    $body = [];
    foreach ($divs as $d) {
        $body = array_merge($body, blocks($d, $ctx, 0));
    }
    if ($start !== null && $start_pb !== $first_pb && $body) {
        $body[0] = finish(inline_node($start_pb, $ctx), $ctx) . ' ' . $body[0];
    }

    $md = '# ' . $article['title'] . "\n\n::: {.editorial-note}\n" . trim($article['note']) . "\n\n"
        . trim($config['note']) . "\n:::\n\n" . implode("\n\n", $body) . "\n";

    $dir = "$articles_dir/{$article['slug']}";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents("$dir/diplomatic.md", $md);

    if (!file_exists("$dir/data.json")) {
        $data = array_merge(
            ['title' => $article['title']],
            $config['data'],
            ['source_url' => $start !== null ? scan_url($scan, $start) : ''],
            ['summary' => $article['summary'] ?? '']
        );
        file_put_contents("$dir/data.json",
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }
    echo "Wrote articles/{$article['slug']}/diplomatic.md (" . str_word_count(strip_tags($md)) . " words)\n";
}
