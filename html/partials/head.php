<?php
// ./html/partials/head.php
// Starts every page, through the opening <body> tag.
// Expects $head_title; optional $body_class (space-separated; 'page-article' also loads MathJax)
// and $canonical_url (set by render_article()).
$body_class = $body_class ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($head_title) ?></title>
    <?php if (!empty($canonical_url)): ?>
        <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('style/tufte.css') ?>">
    <link rel="stylesheet" href="<?= asset('style/site.css') ?>">
    <?php if (in_array('page-article', explode(' ', $body_class))): ?>
        <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <?php endif; ?>
</head>

<body<?= $body_class !== '' ? ' class="' . htmlspecialchars($body_class) . '"' : '' ?>>
<?php if (str_contains($body_class, 'scans-auto')): ?>
    <?php // A reader who closed the scans gets the text alone next time, without the panel flashing open ?>
    <script>try { if (localStorage.getItem('pot-scans') === 'hidden') document.body.classList.remove('scans-auto'); } catch (e) {}</script>
<?php endif; ?>
