<?php
// ./html/photocopy.php
require_once('data.php');

$uri = $_GET['uri'] ?? '';
$uri = str_replace(['../', '..\\'], '', $uri); // Sanitize
$folder_name = str_replace('.html', '', $uri);

$meta = $metadata[$uri] ?? [];

$photos_url_base = "https://oldnews-photos.vjbe.net";

// Scan filenames come from the article's data.json ("scans"); see sync_scans.php in the article repo.
$images = array_map(
    fn($file) => $photos_url_base . '/' . rawurlencode($folder_name) . '/' . rawurlencode($file),
    $meta['scans'] ?? []
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="preconnect" href="https://oldnews-photos.vjbe.net">
    <meta charset="UTF-8">
    <title>Photocopy Viewer | <?= htmlspecialchars($folder_name) ?></title>
    <link rel="stylesheet" href="https://oldnews.vjbe.net/style/tufte.css">
    <style>
        body {
            text-align: center;
        }

        .controls {
            margin-bottom: 2rem;
            text-align: left;
        }

        img {
            display: block;
            margin: 2rem auto;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.9);
            border: 1px solid #333;
        }

        a {
            text-decoration: none;
            border-bottom: 1px solid #8cf;
        }

        .error-msg {
            margin-top: 5rem;
        }

        code {
            background: #222;
            padding: 2px 5px;
        }

        body {
            text-align: center;
            width: 100%;
            padding-left: 0;
            margin: 0;
        }

        .controls {
            margin: 2rem auto;
            max-width: 900px;
            text-align: left;
            font-size: 1.2rem;
            padding: 0 1rem;
        }

        a {
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #8cf;
            transition: border-color 0.2s ease;
        }

        a:hover {
            border-bottom-color: transparent;
        }
    </style>
</head>

<body>
    <div class="controls">
        <!-- New Back to Article Link -->
        <a href="/<?= htmlspecialchars($uri) ?>" class="back-link">← Back to Article</a>


    </div>

    <?php if (empty($images)): ?>
        <div class="error-msg">
            <p>No photocopies found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($images as $index => $img): ?>
            <img src="<?= htmlspecialchars($img) ?>" alt="Original Newspaper Clipping"
                loading="<?= $index < 2 ? 'eager' : 'lazy' ?>" fetchpriority="<?= $index === 0 ? 'high' : 'auto' ?>"
                decoding="async">
        <?php endforeach; ?>
    <?php endif; ?>
</body>

</html>