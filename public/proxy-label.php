<?php
require_once __DIR__ . '/../src/config/env.php';

// Load environment variables
Env::load();

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

// Validate it's a proper URL (http or https only)
if (!in_array($parsedUrl['scheme'], ['http', 'https'])) {
    http_response_code(400);
    die('Error: Only HTTP/HTTPS URLs are allowed');
}

// Create cache directory
$cacheDir = __DIR__ . '/../cache/labels/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$cacheKey = md5($url);
$cachePath = $cacheDir . $cacheKey;
$cacheTime = Env::get('LABEL_PROXY_CACHE_TIME', 3600);

// Check cache first
if (file_exists($cachePath) && (time() - filemtime($cachePath) < $cacheTime)) {
    $imageData = file_get_contents($cachePath);
} else {
    // Fetch the image/file
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, Env::get('LABEL_PROXY_TIMEOUT', 30));
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; POD-Label-Proxy/1.0)');
    
    // Set max file size to prevent abuse (10MB)
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 128);
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $download_size, $downloaded, $upload_size, $uploaded) {
        if ($download_size > 10485760) { // 10MB limit
            return 1; // Abort transfer
        }
        return 0;
    });
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($imageData)) {
        http_response_code(404);
        die('Error: Failed to fetch image');
    }
    
    // Validate content type (must be image or PDF)
    $allowedTypes = [
        'image/jpeg', 
        'image/jpg', 
        'image/png', 
        'image/gif', 
        'image/webp',
        'image/bmp',
        'image/svg+xml',
        'application/pdf'
    ];
    
    // Get mime type from data if not provided
    if (empty($contentType)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);
    }
    
    // Check if content type is allowed
    $isAllowedType = false;
    foreach ($allowedTypes as $type) {
        if (strpos($contentType, $type) !== false) {
            $isAllowedType = true;
            break;
        }
    }
    
    if (!$isAllowedType) {
        http_response_code(415);
        die('Error: Invalid file type. Only images and PDFs are allowed.');
    }
    
    // Save to cache
    file_put_contents($cachePath, $imageData);
}

// Determine content type from cached file if needed
if (!isset($contentType)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $contentType = finfo_file($finfo, $cachePath);
    finfo_close($finfo);
}

// Send appropriate headers
header('Content-Type: ' . $contentType);
header('Content-Length: ' . strlen($imageData));
header('Cache-Control: public, max-age=' . $cacheTime);
header('Access-Control-Allow-Origin: *');

// Output the image/file
echo $imageData;