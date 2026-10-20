<?php
// ./html/card_scan.php
// Small images of each article's first scan, for the corner of its catalogue card.
// /stable/<id>/card makes a ~700px-wide image of the top of the first scan the first time it's asked
// for, keeps it in the system temp folder, and serves that afterwards. The scan is read from the
// photo folder next to the site when there is one (~/html/oldnews-photos on the server), and otherwise
// downloaded from oldnews-photos.vjbe.net, so this works locally too. Without PHP's GD extension, or if
// the scan can't be read, it redirects to the full scan instead.

$photos_base = dirname(__DIR__) . '/oldnews-photos';
$photos_url_base = getenv('PHOTOS_URL_BASE') ?: 'https://oldnews-photos.vjbe.net';

// The first scan's filename, or null
function card_scan_name($uri)
{
    global $metadata;
    return $metadata[$uri]['scans'][0] ?? null;
}

// URL of the card image, or null. ?v= changes when the first scan does, so browsers can keep it.
function card_scan_url($uri)
{
    global $metadata;
    $name = card_scan_name($uri);
    return $name ? '/stable/' . $metadata[$uri]['id'] . '/card?v=' . substr(md5($name), 0, 8) : null;
}

function serve_card_scan($uri)
{
    global $metadata, $photos_base, $photos_url_base;
    $name = card_scan_name($uri);
    if ($name === null) {
        http_response_code(404);
        exit;
    }
    $remote = $photos_url_base . '/' . rawurlencode($uri) . '/' . rawurlencode($name);
    $local = $photos_base . '/' . $uri . '/' . $name;

    $webp = function_exists('imagewebp');
    $type = $webp ? 'image/webp' : 'image/jpeg';
    $cache_dir = sys_get_temp_dir() . '/plainoldtext-cards';
    $cache = $cache_dir . '/' . $metadata[$uri]['id'] . '-' . substr(md5($name), 0, 8) . ($webp ? '.webp' : '.jpg');

    if (!is_file($cache)) {
        $image = null;
        if (function_exists('imagecreatefromstring')) {
            $data = is_file($local) ? file_get_contents($local) : fetch_scan($remote);
            $image = $data !== null ? make_card_image($data) : null;
        }
        if ($image === null) {
            // No GD, or the scan couldn't be read: fall back to the full scan
            header('Location: ' . $remote);
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

// Download a scan from the photo host, or null
function fetch_scan($url)
{
    $context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
    $data = @file_get_contents($url, false, $context);
    $ok = isset($http_response_header[0]) && preg_match('#\s200\s#', $http_response_header[0]);
    return ($data !== false && $ok) ? $data : null;
}

// The top of the scan (at most 1.4× as tall as it is wide), scaled to 700px wide
function make_card_image($data)
{
    ini_set('memory_limit', '512M');
    $source = @imagecreatefromstring($data);
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
