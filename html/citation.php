<?php
// ./html/citation.php
// Chicago-style footnote citations (notes and bibliography, 17th ed.) built from an article's data.json,
// ending with two links: where the text was found ("source_url") and its permalink here. Returns HTML; titles of
// publications are in <em>.

function chicago_citation($meta, $url)
{
    // "Headline," — a title's final period is dropped, its double quotes become single,
    // and no comma follows a title ending in ? or !
    $title = rtrim(trim($meta['title']), '.');
    $title = strtr($title, ['“' => '‘', '”' => '’', '"' => '\'']);
    $headline = '“' . h($title) . (preg_match('/[?!]$/', $title) ? '”' : ',”');

    // A newspaper or journal title drops a leading "The"; the site name keeps it
    $periodical = '<em>' . h(preg_replace('/^The\s+/i', '', $meta['pub'])) . '</em>';
    $date = chicago_date($meta);
    $page = $meta['page'] ? ', ' . h($meta['page']) : '';

    $parts = [];
    if ($meta['author']) {
        $parts[] = h($meta['author']) . ',';
    }
    $parts[] = $headline;

    switch ($meta['category']) {
        case 'book-chapter':
            $book = 'in <em>' . h($meta['full_title'] ?: $meta['pub']) . '</em>';
            if ($meta['translator']) {
                $book .= ', trans. ' . h($meta['translator']);
            }
            $imprint = chicago_imprint($meta);
            $parts[] = $book . ($imprint ? ' (' . $imprint . ')' : '') . $page . ',';
            break;

        case 'journal':
            if ($meta['volume']) {
                $parts[] = $periodical . ' ' . h($meta['volume'])
                    . ($meta['issue'] ? ', no. ' . h($meta['issue']) : '')
                    . ' (' . $date . ')' . ($meta['page'] ? ': ' . h($meta['page']) : '') . ',';
            } else {
                $parts[] = $periodical . ', ' . $date . $page . ',';
            }
            break;

        default: // newspaper, letter
            $parts[] = $periodical . ($meta['place'] ? ' (' . h($meta['place']) . ')' : '') . ', ' . $date . $page . ',';
    }

    $parts[] = citation_links($meta, $url);
    return implode(' ', $parts);
}

// A whole book (a collection's contents page), from its first part's data.json:
// Francis Bacon, <em>Novum Organum</em> (New York: P. F. Collier &amp; Son, 1902), <source url>; available online, <url>.
function chicago_book_citation($meta, $title, $url)
{
    $book = '<em>' . h($meta['full_title'] ?: $title) . '</em>';
    if ($meta['translator']) {
        $book .= ', trans. ' . h($meta['translator']);
    }
    $imprint = chicago_imprint($meta);
    return ($meta['author'] ? h($meta['author']) . (!empty($meta['author_is_editor']) ? ', ed.' : '') . ', ' : '')
        . $book . ($imprint ? ' (' . $imprint . ')' : '') . ', ' . citation_links($meta, $url);
}

// The end of a citation: where the text was found, off-site, then its permalink here:
// https://archive.org/details/…; available online, https://lib.vjbe.net/stable/d89xb.
function citation_links($meta, $url)
{
    return (!empty($meta['source_url']) ? h($meta['source_url']) . '; ' : '') . 'available online, ' . h($url) . '.';
}

// A commentary, cited like a blog post: Victor Elgersma, “Kepler's Press,” <em>Plain Old Text</em>, October 31, 2026,
// <url>. Its title is HTML already.
function chicago_commentary_citation($c, $url)
{
    global $site_name;
    $date = DateTime::createFromFormat('!Y-m-d', $c['date']);
    return ($c['author'] ? h($c['author']) . ', ' : '')
        . '“' . $c['title'] . ',” <em>' . h($site_name) . '</em>, '
        . ($date ? $date->format('F j, Y') . ', ' : '')
        . h($url) . '.';
}

// A book's imprint; a later edition is cited by its own: New York: Collier, 1902
function chicago_imprint($meta)
{
    return implode(', ', array_filter([
        implode(': ', array_filter([h($meta['place'] ?? ''), h($meta['printer'] ?? '')])),
        !empty($meta['edition_year']) ? h($meta['edition_year']) : chicago_date($meta),
    ]));
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
