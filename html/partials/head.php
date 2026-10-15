<?php
// ./html/partials/head.php
// Expects $head_title; optional $body_class and $head_extra (trusted HTML for <head>).
$body_class = $body_class ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($head_title) ?></title>
    <?php if (isset($head_extra)): ?>
        <?= $head_extra ?>

    <?php endif; ?>
    <link rel="stylesheet" href="/style/tufte.css">
    <link rel="stylesheet" href="<?= asset('style/site.css') ?>">
    <?php if ($body_class === 'page-article'): ?>
        <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <?php endif; ?>
</head>
