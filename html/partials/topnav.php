<?php $current_page = $current_page ?? ''; ?>
<h1><?= htmlspecialchars($site_name) ?></h1>
<p class="subtitle">Plain Text Historic Sources</p>

<nav class="site-nav" aria-label="Primary">
    <ul>
        <li class="<?= $current_page === 'catalogue' ? 'active' : '' ?>">
            <a href="/catalogue">Catalogue</a>
        </li>

        <li class="<?= $current_page === 'about' ? 'active' : '' ?>">
            <a href="/about">About</a>
        </li>

        <li class="<?= $current_page === 'contribute' ? 'active' : '' ?>">
            <a href="/contribute">Contribute</a>
        </li>
    </ul>
</nav>
<hr>
