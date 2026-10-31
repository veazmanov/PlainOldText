<?php // ./html/partials/article_header.php
// The top of an article page: the title, a dateline (publication, date, page), the scans button and
// edition switch, and a cog menu with the page tools in two groups: Source (source link) and Text
// (markdown, GitHub, corrections). An article with an English translation shows it instead of the text;
// "Original (Latin) text", beside the scans button, puts the text beside it (?original=1). A dagger after the title opens the citation beneath it, and "Summary" at the
// end of the dateline opens the summary beneath that (cite.js). The scans open beside the text by default on wide
// screens (body.scans-auto); see article.js.
$cite_mark = '<a class="cite-mark sans" href="#cite" aria-controls="cite" aria-expanded="false" aria-label="Cite this page" title="Cite this page">&dagger;</a>';
?>
<div class="article-top">
    <?= preg_replace('/(<\/h[1-3]>)$/i', $cite_mark . '$1', trim($article_title_html)) ?>

    <details class="page-tools sans" id="page-tools">
        <summary class="page-tools-button" aria-label="Page tools" title="Page tools">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                <path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"
                    d="M10.3 2.5h3.4l.5 2.6a7.6 7.6 0 0 1 1.9 1.1l2.5-.9 1.7 2.9-2 1.7a7.6 7.6 0 0 1 0 2.2l2 1.7-1.7 2.9-2.5-.9a7.6 7.6 0 0 1-1.9 1.1l-.5 2.6h-3.4l-.5-2.6a7.6 7.6 0 0 1-1.9-1.1l-2.5.9-1.7-2.9 2-1.7a7.6 7.6 0 0 1 0-2.2l-2-1.7 1.7-2.9 2.5.9a7.6 7.6 0 0 1 1.9-1.1z"/>
            </svg>
        </summary>
        <div class="page-tools-menu">
            <?php if (!empty($source_url)): ?>
                <p class="menu-heading">Source</p>
                <ul>
                    <li>
                        <details class="menu-disclosure">
                            <summary class="menu-item">Go to source</summary>
                            <p class="menu-reveal">
                                <a class="source-link" href="<?= htmlspecialchars($source_url) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($source_url) ?></a>
                            </p>
                        </details>
                    </li>
                </ul>
            <?php endif; ?>

            <p class="menu-heading">Text</p>
            <ul>
                <?php if (!empty($markdown_url)): ?>
                    <li>
                        <a class="menu-item" href="<?= htmlspecialchars($markdown_url) ?>">View as markdown</a>
                    </li>
                <?php endif; ?>
                <li>
                    <a class="menu-item" href="<?= htmlspecialchars($github_url) ?>" target="_blank" rel="noopener">View on GitHub</a>
                </li>
                <li>
                    <details class="menu-disclosure">
                        <summary class="menu-item">Submit a correction</summary>
                        <p class="menu-reveal">Email your correction to <a href="mailto:plainoldtext@vjbe.net">plainoldtext@vjbe.net</a>.</p>
                    </details>
                </li>
            </ul>
        </div>
    </details>
</div>

<?php // The citation, opened by the dagger after the title (shown by :target without JS) ?>
<div class="title-citation sans" id="cite">
    <p><span class="title-citation-mark" aria-hidden="true">&dagger;</span> <span id="citation"><?= $citation ?></span></p>
    <button type="button" class="cite-copy" data-target="citation" hidden>Copy</button>
</div>

<?php // Dateline: the publication and date, under the title (styled like the catalogue cards), and the summary toggle ?>
<p class="dateline sans">
    <span class="dateline-pub"><?= htmlspecialchars($pub_name) ?></span>
    <span class="dateline-sep" aria-hidden="true">&middot;</span>
    <span><?= htmlspecialchars($date_str) ?><?php if (!empty($page_num)): ?>, p.&nbsp;<?= htmlspecialchars($page_num) ?><?php endif; ?></span>
    <?php if (!empty($summary)): ?>
        <span class="dateline-more"><span class="dateline-sep" aria-hidden="true">&middot;</span><a class="summary-toggle" href="#summary" aria-controls="summary" aria-expanded="false">Summary</a></span>
    <?php endif; ?>
</p>

<?php if (!empty($collection)): ?>
    <p class="collection-line sans">Part of <a href="/stable/<?= htmlspecialchars($collection['id']) ?>"><em><?= htmlspecialchars($collection['title']) ?></em></a></p>
<?php endif; ?>

<?php // The summary, opened by "Summary" in the dateline (shown by :target without JS) ?>
<?php if (!empty($summary)): ?>
    <p class="dateline-summary" id="summary"><?= htmlspecialchars($summary) ?></p>
<?php endif; ?>

<?php if ($has_scans || $translation): ?>
    <p class="article-actions sans">
        <?php if ($has_scans): ?>
            <a class="scans-toggle" href="<?= htmlspecialchars($photo_link) ?>" id="scans-toggle"
                aria-controls="scan-panel" aria-expanded="<?= $scans_open ? 'true' : 'false' ?>"><?= $scans_open ? 'Hide scans' : 'Original scans' ?></a>
        <?php endif; ?>
        <?php if ($has_scans && $translation): ?>&middot;<?php endif; ?>
        <?php if ($translation): ?>
            <a class="translation-toggle" href="<?= htmlspecialchars($translation_link) ?>"><?= $translation_shown ? 'Hide original' : ($meta['language'] ? 'Original (' . htmlspecialchars($meta['language']) . ') text' : 'Original text') ?></a>
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php if (count($editions) > 1): ?>
    <p class="edition-switch sans">
        <?php $first = true; foreach ($editions as $id => $ed):
            $url = ($scans_open ? $slug_url . '/scans' : $slug_url)
                . ($id === array_key_first($editions) ? '' : '?edition=' . rawurlencode($id));
            if ($translation_shown) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'original=1';
            } ?>
            <?= $first ? '' : '&middot;' ?>
            <?php if ($ed === $edition): ?>
                <strong aria-current="page"><?= htmlspecialchars($ed['label']) ?></strong>
            <?php else: ?>
                <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($ed['label']) ?></a>
            <?php endif; ?>
        <?php $first = false; endforeach; ?>
    </p>
<?php endif; ?>

<script src="<?= asset('js/cite.js') ?>"></script>
<script src="<?= asset('js/page-tools.js') ?>"></script>

<?php if ($has_scans): ?>
    <?php // Shown by CSS when the body has show-scans (opened) or scans-auto (the default, wide screens only) ?>
    <aside class="scan-panel sans" id="scan-panel" aria-label="Original scans">
        <p class="scan-panel-bar">
            <span>Original scans</span>
            <button type="button" id="scans-close">Close &times;</button>
        </p>
        <?php if ($scans_pdf): ?>
            <?php // The PDF only loads once the panel opens (article.js copies data-src to src) ?>
            <iframe class="scan-pdf" title="Original scans (PDF)"
                <?= $scans_open ? 'src' : 'data-src' ?>="<?= htmlspecialchars($scans_pdf) ?>#view=FitH"></iframe>
            <p class="scan-pdf-link"><a href="<?= htmlspecialchars($scans_pdf) ?>">Open the original scans (PDF)</a></p>
        <?php endif; ?>
        <?php foreach ($scans as $i => $scan): ?>
            <img src="<?= htmlspecialchars($scan) ?>" alt="Original scan, page <?= $i + 1 ?>" loading="lazy" decoding="async">
        <?php endforeach; ?>
    </aside>

    <script src="<?= asset('js/article.js') ?>"></script>
<?php endif; ?>
