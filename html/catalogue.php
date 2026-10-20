<?php
$sort = $_GET['sort'] ?? 'newest';
$query = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$category_counts = array_count_values(array_column($links, 'category'));
usort($links, function ($a, $b) use ($sort) {
    $timeA = strtotime($a['sort_date']);
    $timeB = strtotime($b['sort_date']);
    return $sort === 'oldest' ? $timeA <=> $timeB : $timeB <=> $timeA;
});
?>
<?php
$head_title = 'Archive Catalogue | ' . $site_name;
$body_class = 'page-catalogue';
include __DIR__ . '/partials/head.php';
?>
    <header>
        <?php include __DIR__ . '/partials/topnav.php'; ?>
    </header>
    <article>
        <section>
            <form class="sans catalogue-tools" role="search" action="/catalogue" method="get" onsubmit="return false;">
                <div class="search-field">
                    <label for="catalogue-search">Search the archive</label>
                    <input id="catalogue-search" type="search" name="q" autocomplete="off"
                        placeholder="Titles, newspapers, dates, text…"
                        value="<?= htmlspecialchars($query) ?>">
                    <kbd aria-hidden="true">/</kbd>
                </div>
                <div class="sort-controls">
                    Sort by:
                    <a href="?sort=newest" class="sort-link <?= $sort === 'newest' ? 'active' : '' ?>" data-sort="newest">Newest</a> |
                    <a href="?sort=oldest" class="sort-link <?= $sort === 'oldest' ? 'active' : '' ?>" data-sort="oldest">Oldest</a>
                </div>
                <div class="category-filter" role="group" aria-label="Category">
                    <button type="button" data-category="" aria-pressed="<?= $category === '' ? 'true' : 'false' ?>">
                        All<span class="n"><?= count($links) ?></span>
                    </button>
                    <?php foreach ($categories as $key => $label):
                        $n = $category_counts[$key] ?? 0; ?>
                        <button type="button" data-category="<?= htmlspecialchars($key) ?>"
                            aria-pressed="<?= $category === $key ? 'true' : 'false' ?>" <?= $n === 0 ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($label) ?><span class="n"><?= $n ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p class="result-count" id="result-count" aria-live="polite">
                    <?= number_format(count($links)) ?> items
                </p>
            </form>

            <ul class="clip-grid" id="clip-grid">
                <?php foreach ($links as $article):
                    $search_text = implode(' ', [
                        $article['title'],
                        $article['pub'],
                        $article['date'],
                        $article['summary'] ?? '',
                        $article['excerpt'] ?? '',
                    ]);
                    $minutes = max(1, (int) round($article['words'] / 220));
                    $body = $article['excerpt'] ?: $article['summary'];
                ?>
                    <li class="<?= !empty($article['featured']) ? 'is-featured' : '' ?>"
                        data-search="<?= htmlspecialchars($search_text) ?>"
                        data-category="<?= htmlspecialchars($article['category']) ?>">
                        <div class="clip">
                            <p class="clip-masthead"><?= htmlspecialchars($article['pub']) ?></p>
                            <p class="clip-dateline sans">
                                <span><?= htmlspecialchars($article['date']) ?></span>
                                <?php if ($article['words'] > 0): ?>
                                    <span><?= number_format($article['words']) ?> words</span>
                                <?php endif; ?>
                            </p>

                            <h2 class="clip-headline">
                                <a href="/stable/<?= htmlspecialchars($article['id']) ?>"
                                    <?php if (!empty($article['summary'])): ?>title="<?= htmlspecialchars($article['summary']) ?>"<?php endif; ?>>
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h2>
                            <hr class="clip-dinkus">

                            <?php if (!empty($body)): ?>
                                <p class="clip-body <?= empty($article['excerpt']) ? 'is-summary' : '' ?>">
                                    <?= htmlspecialchars($body) ?>
                                </p>
                            <?php else: ?>
                                <div class="clip-body"></div>
                            <?php endif; ?>

                            <p class="clip-foot sans">
                                <?php if ($article['words'] > 0): ?>
                                    <span><?= $minutes ?> min read</span>
                                <?php else: ?>
                                    <span class="pending-tag">Transcription pending</span>
                                <?php endif; ?>
                                <span class="read-more" aria-hidden="true">Read &rarr;</span>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="no-results" id="no-results" hidden>No items match.</p>
        </section>
    </article>

    <script src="<?= asset('js/catalogue.js') ?>"></script>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
