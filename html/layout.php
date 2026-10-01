<!-- ./html/layout.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?> | <?= htmlspecialchars($pub_name) ?></title>
    <link rel="stylesheet" href="/style/tufte.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>

<body>
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
