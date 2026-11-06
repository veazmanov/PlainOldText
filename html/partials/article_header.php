<?php // ./html/partials/article_header.php
// The top of an article page: the title, dateline and the panel behind the "i" ($info_panel, from info_panel() in
// index.php), then the scans button and edition switch. An article with an English translation shows it instead of
// the text; a switch under the scans button shows the text alone (?view=original) or the two side by side
// (?view=both). The scans open beside the text by default on wide screens (body.scans-auto); see article.js.
// "Commentary" links to the commentary on the article (commentary.php).
?>
<?= $info_panel ?>

<?php if ($has_scans || $commentary): ?>
    <p class="article-actions sans">
        <?php if ($has_scans): ?>
            <a class="scans-toggle" href="<?= htmlspecialchars($photo_link) ?>" id="scans-toggle"
                aria-controls="scan-panel" aria-expanded="<?= $scans_open ? 'true' : 'false' ?>"><?= $scans_open ? 'Hide scans' : 'Original scans' ?></a>
        <?php endif; ?>
        <?php if ($commentary): ?>
            <?php if ($has_scans): ?>&middot; <?php endif; ?>
            <a class="commentary-link" href="<?= htmlspecialchars($commentary_link) ?>">Commentary<?= count($commentary) > 1 ? ' (' . count($commentary) . ')' : '' ?></a>
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php if ($view_links): ?>
    <p class="edition-switch view-switch sans">
        <?php $first = true; foreach ($view_links as $v => $link): ?>
            <?= $first ? '' : '&middot;' ?>
            <?php if ($v === $view): ?>
                <strong aria-current="page"><?= htmlspecialchars($link['label']) ?></strong>
            <?php else: ?>
                <a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['label']) ?></a>
            <?php endif; ?>
        <?php $first = false; endforeach; ?>
    </p>
    <?php if ($view !== 'original'): ?>
        <p class="translation-note sans"><?= htmlspecialchars(translation_note($meta['language'])) ?></p>
    <?php endif; ?>
<?php endif; ?>

<?php if (count($editions) > 1): ?>
    <p class="edition-switch sans">
        <?php $first = true; foreach ($editions as $id => $ed):
            $url = ($scans_open ? $slug_url . '/scans' : $slug_url)
                . ($id === array_key_first($editions) ? '' : '?edition=' . rawurlencode($id));
            if ($view === 'original' || $view === 'both') {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'view=' . $view;
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
