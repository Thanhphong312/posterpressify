<?php
$allowedDomains = [
    'stamps.com',
    'usps.com',
    'fedex.com',
    'ups.com',
    'dhl.com',
    'easypost.com',
    'shipstation.com',
    'endicia.com'
];

$url = $_GET['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    die('Error: No URL provided');
}

$url = urldecode($url);
$parsedUrl = parse_url($url);

if (!$parsedUrl || !isset($parsedUrl['host'])) {
    http_response_code(400);
    die('Error: Invalid URL');
}

$isAllowed = false;
foreach ($allowedDomains as $domain) {
    if (strpos($parsedUrl['host'], $domain) !== false) {
        $isAllowed = true;
        break;
    }
}

if (!$isAllowed) {
    http_response_code(403);
    die('Error: Domain not allowed');
}

$cacheDir = __DIR__ . '/../cache/labels/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$cacheKey = md5($url);
$cachePath = $cacheDir . $cacheKey;
$cacheTime = 3600; // 1 hour cache

if (file_exists($cachePath) && (time() - filemtime($cachePath) < $cacheTime)) {
    $imageData = file_get_contents($cachePath);
} else {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; POD-Label-Proxy/1.0)');
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($imageData)) {
        http_response_code(404);
        die('Error: Failed to fetch image');
    }
    
    file_put_contents($cachePath, $imageData);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $imageData);
finfo_close($finfo);

$allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
if (!in_array($mimeType, $allowedMimeTypes)) {
    http_response_code(415);
    die('Error: Invalid file type');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . strlen($imageData));
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');

echo $imageData;