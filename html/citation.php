<?php
// ./html/citation.php
// Chicago-style footnote citations (notes and bibliography, 17th ed.) built from an article's data.json,
// ending with where it was transcribed. Returns HTML; titles of publications are in <em>.

function chicago_citation($meta, $url)
{
    $h = fn($s) => htmlspecialchars($s, ENT_QUOTES);

    // "Headline," — a title's final period is dropped, its double quotes become single,
    // and no comma follows a title ending in ? or !
    $title = rtrim(trim($meta['title']), '.');
    $title = strtr($title, ['“' => '‘', '”' => '’', '"' => '\'']);
    $headline = '“' . $h($title) . (preg_match('/[?!]$/', $title) ? '”' : ',”');

    // A newspaper or journal title drops a leading "The"; the site name keeps it
    $periodical = '<em>' . $h(preg_replace('/^The\s+/i', '', $meta['pub'])) . '</em>';
    $date = chicago_date($meta);
    $page = $meta['page'] ? ', ' . $h($meta['page']) : '';

    $parts = [];
    if ($meta['author']) {
        $parts[] = $h($meta['author']) . ',';
    }
    $parts[] = $headline;

    switch ($meta['category']) {
        case 'book-chapter':
            $book = 'in <em>' . $h($meta['full_title'] ?: $meta['pub']) . '</em>';
            if ($meta['translator']) {
                $book .= ', trans. ' . $h($meta['translator']);
            }
            $imprint = implode(', ', array_filter([
                implode(': ', array_filter([$h($meta['place'] ?? ''), $h($meta['printer'] ?? '')])),
                $date,
            ]));
            $parts[] = $book . ($imprint ? ' (' . $imprint . ')' : '') . $page . ',';
            break;

        case 'journal':
            if ($meta['volume']) {
                $parts[] = $periodical . ' ' . $h($meta['volume'])
                    . ($meta['issue'] ? ', no. ' . $h($meta['issue']) : '')
                    . ' (' . $date . ')' . ($meta['page'] ? ': ' . $h($meta['page']) : '') . ',';
            } else {
                $parts[] = $periodical . ', ' . $date . $page . ',';
            }
            break;

        default: // newspaper, letter
            $parts[] = $periodical . ($meta['place'] ? ' (' . $h($meta['place']) . ')' : '') . ', ' . $date . $page . ',';
    }

    $parts[] = 'transcribed in Plain Old Text, ' . $h($url) . '.';
    return implode(' ', $parts);
}

// "May 28, 1887", or just the year
function chicago_date($meta)
{
    if ($meta['month'] && $meta['day']) {
        $d = DateTime::createFromFormat('!Y-n-j', $meta['year'] . '-' . $meta['month'] . '-' . $meta['day']);
        if ($d) {
            return $d->format('F j, Y');
        }
    }
    return htmlspecialchars((string) $meta['year']);
}
