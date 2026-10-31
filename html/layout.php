<?php
// ./html/layout.php
$head_title = $title . ' | ' . $pub_name;
// show-scans: opened at /stable/<id>/scans. scans-auto: the default, open beside the text on wide screens, except
// beside a translation. show-translation: the text beside its translation (?original=1).
$body_class = isset($article_header_partial)
    ? 'page-article'
        . (!empty($scans_open) ? ' show-scans' : (!empty($has_scans) && empty($translation_shown) ? ' scans-auto' : ''))
        . (!empty($translation_shown) ? ' show-translation' : '')
    : ($body_class ?? '');
include __DIR__ . '/partials/head.php';
?>
    <header>
        <?php include __DIR__ . '/partials/topnav.php'; ?>
    </header>
    <article>
        <?php if (isset($article_header_partial)): ?>
            <section class="article-head">
                <?php
                $current_page = '';
                include $article_header_partial; // includes the title
                ?>
            </section>
        <?php endif; ?>

        <section>
            <?= $content ?>
        </section>
    </article>
    <script src="<?= asset('js/theme.js') ?>"></script>
</body>

</html>
