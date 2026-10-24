<?php $current_page = $current_page ?? ''; ?>
<div class="site-masthead">
    <a class="site-logo" href="/catalogue">
        <span class="site-logo-name"><?= htmlspecialchars($site_name) ?><span class="site-logo-mark" aria-hidden="true">&dagger;</span></span>
    </a>
    <p class="site-masthead-note"><span aria-hidden="true">&dagger;</span> plainoldtext.vjbe.net</p>
</div>

<nav class="site-nav" aria-label="Primary">
    <ul>
        <?php foreach (['catalogue' => 'Catalogue', 'about' => 'About', 'contribute' => 'Contribute'] as $slug => $label): ?>
            <li>
                <a href="/<?= $slug ?>"<?= $current_page === $slug ? ' aria-current="page"' : '' ?>><?= $label ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
