<?php
// ./html/layout.php
$head_title = $title . ' | ' . $pub_name;
$body_class = isset($article_header_partial) ? 'page-article' . (!empty($scans_open) ? ' show-scans' : '') : '';
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
                include $article_header_partial;
                ?>
                <?= $article_title_html ?>
            </section>
        <?php endif; ?>

        <section>
            <?= $content ?>
        </section>
    </article>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>
