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

<style>
.site-nav {
    margin: 0 0 3rem 0;
}

.site-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.site-nav li {
    display: inline;
}

.site-nav li + li::before {
    content: "/";
    color: #999;
    margin: 0 0.75rem;
}

.site-nav a {
    text-decoration: none;
    color: #666;
    font-size: 0.95rem;
    font-variant: small-caps;
    letter-spacing: 0.05em;
}

/* TARGET THE LINK INSIDE THE ACTIVE LI */
.site-nav li.active a {
    color: #111;
    font-weight: bold;
    pointer-events: none; /* Optional: stops users re-clicking the page they're already on */
}
</style>