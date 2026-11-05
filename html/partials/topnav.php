<?php $current_page = $current_page ?? ''; ?>
<div class="site-masthead">
    <a class="site-logo" href="/">
        <span class="site-logo-name"><?= htmlspecialchars($site_name) ?><span class="site-logo-mark" aria-hidden="true">&dagger;</span></span>
    </a>
    <p class="site-masthead-note"><span aria-hidden="true">&dagger;</span> plainoldtext.vjbe.net</p>
    <?php // Shown only in print: the day the page was printed and where from (an article's stable URL, from its
    // canonical tag), filled in as the print dialog opens ?>
    <p class="print-stamp" id="print-stamp"></p>
    <script>addEventListener('beforeprint', function () { var c = document.querySelector('link[rel="canonical"]'); document.getElementById('print-stamp').textContent = 'Printed ' + new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) + ' from ' + (c ? c.href : location.href); });</script>
</div>

<nav class="site-nav" aria-label="Primary">
    <ul>
        <?php foreach (['catalogue' => 'Catalogue', 'about' => 'About', 'contribute' => 'Contribute'] as $slug => $label): ?>
            <li>
                <a href="/<?= $slug === 'catalogue' ? '' : $slug ?>"<?= $current_page === $slug ? ' aria-current="page"' : '' ?>><?= $label ?></a>
            </li>
        <?php endforeach; ?>
        <?php // Light/dark toggle (theme.js), shown once the script runs; CSS pins it to the corner of the window ?>
        <li class="site-nav-theme" id="theme-toggle-item" hidden>
            <button type="button" class="theme-toggle" id="theme-toggle">
                <svg class="theme-icon-dark" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>
                </svg>
                <svg class="theme-icon-light" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.6"/>
                    <path stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8"/>
                </svg>
            </button>
        </li>
    </ul>
</nav>
