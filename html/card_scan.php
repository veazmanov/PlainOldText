<?php
// ./html/card_scan.php
// Small images of each article's first scan, for the corner of its catalogue card.
// The scans live next to the site on the server (~/html/oldnews-photos/<slug>/, served at
// oldnews-photos.vjbe.net). /stable/<id>/card makes a ~700px-wide image of the top of the first scan
// the first time it's asked for, keeps it in the system temp folder, and serves that afterwards.
// Without PHP's GD extension it redirects to the full scan instead.

$photos_base = dirname(__DIR__) . '/oldnews-photos';

// The first scan of an article, as a file on disk, or null
function card_scan_source($uri)
{
    global $metadata, $photos_base;
    $scans = $metadata[$uri]['scans'] ?? [];
    if (empty($scans)) {
        return null;
    }
    $file = $photos_base . '/' . $uri . '/' . $scans[0];
    return is_file($file) ? $file : null;
}

// URL of the card image (versioned by the scan's date, so browsers can keep it), or null
function card_scan_url($uri)
{
    global $metadata;
    $file = card_scan_source($uri);
    return $file ? '/stable/' . $metadata[$uri]['id'] . '/card?v=' . filemtime($file) : null;
}

function serve_card_scan($uri)
{
    global $metadata;
    $file = card_scan_source($uri);
    if ($file === null) {
        http_response_code(404);
        exit;
    }

    $webp = function_exists('imagewebp');
    $type = $webp ? 'image/webp' : 'image/jpeg';
    $cache_dir = sys_get_temp_dir() . '/plainoldtext-cards';
    $cache = $cache_dir . '/' . $metadata[$uri]['id'] . '-' . filemtime($file) . ($webp ? '.webp' : '.jpg');

    if (!is_file($cache)) {
        $image = function_exists('imagecreatefromstring') ? make_card_image($file) : null;
        if ($image === null) {
            // No GD (or an unreadable file): fall back to the full scan
            header('Location: https://oldnews-photos.vjbe.net/' . rawurlencode($uri) . '/' . rawurlencode(basename($file)));
            exit;
        }
        @mkdir($cache_dir, 0775, true);
        $tmp = $cache . '.' . getmypid();
        $webp ? imagewebp($image, $tmp, 62) : imagejpeg($image, $tmp, 72);
        imagedestroy($image);
        if (!@rename($tmp, $cache)) {
            // Can't write the cache: send it anyway
            header('Content-Type: ' . $type);
            readfile($tmp);
            @unlink($tmp);
            exit;
        }
    }

    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($cache));
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($cache);
    exit;
}

// The top of the scan (at most 1.4× as tall as it is wide), scaled to 700px wide
function make_card_image($file)
{
    ini_set('memory_limit', '512M');
    $source = @imagecreatefromstring(file_get_contents($file));
    if ($source === false) {
        return null;
    }
    $w = imagesx($source);
    $h = min(imagesy($source), (int) round($w * 1.4));
    $out_w = min(700, $w);
    $out_h = (int) round($h * $out_w / $w);

    $image = imagecreatetruecolor($out_w, $out_h);
    imagecopyresampled($image, $source, 0, 0, 0, 0, $out_w, $out_h, $w, $h);
    imagedestroy($source);
    return $image;
}
