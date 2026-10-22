<?php // ./html/partials/article_header.php
// The top of an article page: the title, a dateline (publication, date, page), the scans button and
// edition switch, and a cog menu with the page tools in three groups: Source (source link), Text
// (markdown, GitHub, corrections) and Reference (summary, citation). The scans open beside the text by default on
// wide screens (body.scans-auto); see article.js.
?>
<div class="article-top">
    <?= $article_title_html ?>

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
                        <p class="menu-reveal">Email your correction to <a href="mailto:victor@vjbe.net">victor@vjbe.net</a>.</p>
                    </details>
                </li>
            </ul>

            <p class="menu-heading">Reference</p>
            <ul>
                <?php if (!empty($summary)): ?>
                    <li>
                        <details class="menu-disclosure">
                            <summary class="menu-item">Summary</summary>
                            <p class="menu-reveal menu-summary"><?= htmlspecialchars($summary) ?></p>
                        </details>
                    </li>
                <?php endif; ?>
                <li>
                    <details class="menu-disclosure">
                        <summary class="menu-item">View citation</summary>
                        <div class="menu-reveal">
                            <p class="menu-citation" id="citation"><?= $citation ?></p>
                            <button type="button" class="cite-copy" data-target="citation" hidden>Copy</button>
                        </div>
                    </details>
                </li>
            </ul>
        </div>
    </details>
</div>

<?php // Dateline: the publication and date, under the title (styled like the catalogue cards) ?>
<p class="dateline sans">
    <span class="dateline-pub"><?= htmlspecialchars($pub_name) ?></span>
    <span class="dateline-sep" aria-hidden="true">&middot;</span>
    <span><?= htmlspecialchars($date_str) ?><?php if (!empty($page_num)): ?>, p.&nbsp;<?= htmlspecialchars($page_num) ?><?php endif; ?></span>
</p>

<?php if ($has_scans): ?>
    <p class="article-actions sans">
        <a class="scans-toggle" href="<?= htmlspecialchars($photo_link) ?>" id="scans-toggle"
            aria-controls="scan-panel" aria-expanded="<?= $scans_open ? 'true' : 'false' ?>"><?= $scans_open ? 'Hide scans' : 'Original scans' ?></a>
    </p>
<?php endif; ?>

<?php if (count($editions) > 1): ?>
    <p class="edition-switch sans">
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
