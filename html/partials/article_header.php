<?php // ./html/partials/article_header.php ?>
<div class="source-panel sans">
    <ul class="source-links">
        <?php if ($has_scans): ?>
            <li>
                <a href="<?= htmlspecialchars($photo_link) ?>" id="scans-toggle"
                    aria-controls="scan-panel" aria-expanded="<?= $scans_open ? 'true' : 'false' ?>"><?= $scans_open ? 'Hide scans' : 'Original scans' ?></a>
            </li>
        <?php endif; ?>
        <?php if (!empty($markdown_url)): ?>
            <li>
                <a href="<?= htmlspecialchars($markdown_url) ?>">View as markdown</a>
            </li>
        <?php endif; ?>
        <li>
            <a href="<?= htmlspecialchars($github_url) ?>" target="_blank" rel="noopener">Edit on GitHub</a>
        </li>
        <?php if (!empty($source_url)): ?>
            <li>
                <a href="<?= htmlspecialchars($source_url) ?>" target="_blank" rel="noopener">Go to source</a>
            </li>
        <?php endif; ?>
    </ul>

    <?php if (!empty($summary)): ?>
        <p class="source-summary"><?= htmlspecialchars($summary) ?></p>
    <?php endif; ?>

    <p class="source-line">
        <em><?= htmlspecialchars($pub_name) ?></em>
        &ndash; <?= htmlspecialchars($date_str) ?>
        <?php if (!empty($page_num)): ?>
            &middot; p.&nbsp;<?= htmlspecialchars($page_num) ?>
        <?php endif; ?>
    </p>

    <?php if (count($editions) > 1): ?>
        <p class="edition-switch">
            <?php $first = true; foreach ($editions as $id => $ed):
                $url = ($scans_open ? $slug_url . '/scans' : $slug_url)
                    . ($id === array_key_first($editions) ? '' : '?edition=' . rawurlencode($id)); ?>
                <?= $first ? '' : '&middot;' ?>
                <?php if ($ed === $edition): ?>
                    <strong aria-current="page"><?= htmlspecialchars($ed['label']) ?></strong>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($ed['label']) ?></a>
                <?php endif; ?>
            <?php $first = false; endforeach; ?>
        </p>
    <?php endif; ?>

    <p class="cite-as">
        <span class="cite-label">Cite as:</span>
        <span id="citation"><?= $citation ?></span>
        <button type="button" class="cite-copy" data-target="citation" hidden>Copy</button>
    </p>
    <script src="<?= asset('js/cite.js') ?>"></script>
</div>

<?php if ($has_scans): ?>
    <aside class="scan-panel sans" id="scan-panel" <?= $scans_open ? '' : 'hidden' ?> aria-label="Original scans">
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

