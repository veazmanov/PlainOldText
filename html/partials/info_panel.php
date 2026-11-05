<?php // ./html/partials/info_panel.php
// The top of a page about a text, rendered by info_panel() in index.php from $info: the title, with an "i" beside it
// (and a map icon under that, when there is a map), then the dateline and a line of context, such as the collection,
// a click away. The "i" opens a panel about the text: the summary and source note, then the page tools in two groups:
// Source (citation, source link) and Text (markdown, GitHub, corrections). Kept out of the way so that the eye goes
// from the title to the text. toggles.js opens the panel and the citation; without JS, :target does.
?>
<div class="article-top<?= $info['map_url'] ? ' has-map' : '' ?>">
    <?= trim($info['title']) ?>

    <p class="title-icons sans">
        <a class="title-icon info-toggle" href="#info" aria-controls="info" aria-expanded="false" aria-label="About this text" title="About this text">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                <circle cx="12" cy="12" r="9.25" fill="none" stroke="currentColor" stroke-width="1.6"/>
                <path d="M12 10.6v5.9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="12" cy="7.5" r="1.15" fill="currentColor"/>
            </svg>
        </a>
        <?php if ($info['map_url']): ?>
            <a class="title-icon" href="<?= h($info['map_url']) ?>" target="_blank" rel="noopener" aria-label="Map" title="Map">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"
                        d="M3.5 6.2l5.5-2.2 6 2.2 5.5-2.2v13.8l-5.5 2.2-6-2.2-5.5 2.2zM9 4v13.8M15 6.2V20"/>
                </svg>
            </a>
        <?php endif; ?>
    </p>
</div>

<?= $info['dateline'] ?>

<?= $info['context'] ?>

<div class="info-panel" id="info">
    <?php if ($info['summary']): ?>
        <p class="info-summary"><?= h($info['summary']) ?><?= summary_note() ?></p>
    <?php endif; ?>

    <?php if ($info['source_note']): ?>
        <div class="editorial-note"><?= $info['source_note'] ?></div>
    <?php endif; ?>

    <div class="info-tools sans">
        <?php if ($info['citation'] || $info['source_url']): ?>
            <p class="info-tools-heading">Source</p>
            <ul>
                <?php if ($info['citation']): ?>
                    <li>
                        <a class="info-tool cite-toggle" href="#cite" aria-controls="cite" aria-expanded="false">Cite this page</a>
                        <div class="title-citation" id="cite">
                            <p id="citation"><?= $info['citation'] ?></p>
                            <button type="button" class="cite-copy" data-target="citation" hidden>Copy</button>
                        </div>
                    </li>
                <?php endif; ?>
                <?php if ($info['source_url']): ?>
                    <li>
                        <details class="info-disclosure">
                            <summary class="info-tool">Go to source</summary>
                            <p class="info-reveal">
                                <a class="source-link" href="<?= h($info['source_url']) ?>" target="_blank" rel="noopener"><?= h($info['source_url']) ?></a>
                            </p>
                        </details>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>

        <p class="info-tools-heading">Text</p>
        <ul>
            <?php if ($info['markdown_url']): ?>
                <li>
                    <a class="info-tool" href="<?= h($info['markdown_url']) ?>">View as markdown</a>
                </li>
            <?php endif; ?>
            <?php if ($info['github_url']): ?>
                <li>
                    <a class="info-tool" href="<?= h($info['github_url']) ?>" target="_blank" rel="noopener">View on GitHub</a>
                </li>
            <?php endif; ?>
            <li>
                <details class="info-disclosure">
                    <summary class="info-tool">Submit a correction</summary>
                    <p class="info-reveal">Email your correction to <a href="mailto:plainoldtext@vjbe.net">plainoldtext@vjbe.net</a>.</p>
                </details>
            </li>
        </ul>
    </div>
</div>

<script src="<?= asset('js/toggles.js') ?>"></script>
