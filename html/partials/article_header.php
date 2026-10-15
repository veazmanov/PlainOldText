<?php // ./html/partials/article_header.php ?>
<div class="source-panel sans">
    <ul class="source-links">
        <?php if (!empty($scans)): ?>
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
</div>

<?php if (!empty($scans)): ?>
    <aside class="scan-panel sans" id="scan-panel" <?= $scans_open ? '' : 'hidden' ?> aria-label="Original scans">
        <p class="scan-panel-bar">
            <span>Original scans</span>
            <button type="button" id="scans-close">Close &times;</button>
        </p>
        <?php foreach ($scans as $i => $scan): ?>
            <img src="<?= htmlspecialchars($scan) ?>" alt="Original scan, page <?= $i + 1 ?>" loading="lazy" decoding="async">
        <?php endforeach; ?>
    </aside>

    <script src="<?= asset('js/article.js') ?>"></script>
<?php endif; ?>

