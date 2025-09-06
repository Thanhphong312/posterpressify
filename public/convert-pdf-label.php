<?php
require_once __DIR__ . '/../src/config/env.php';
require_once __DIR__ . '/../src/config/database.php';

// Load environment variables
Env::load();

$url = $_GET['url'] ?? '';
$orderId = $_GET['order'] ?? null;

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

// Create cache directory if doesn't exist
$cacheDir = __DIR__ . '/../cache/labels/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Generate cache key
$cacheKey = md5($url);
$cachePath = $cacheDir . $cacheKey;

// Check if it's a PDF
$isPdf = (strtolower(pathinfo($parsedUrl['path'], PATHINFO_EXTENSION)) === 'pdf');

if ($isPdf) {
    // Cache path for converted image
    $imageCachePath = $cachePath . '.jpg';
    $cacheTime = Env::get('LABEL_PROXY_CACHE_TIME', 3600);
    
    // Check if converted image exists in cache
    if (file_exists($imageCachePath) && (time() - filemtime($imageCachePath) < $cacheTime)) {
        // Serve cached image
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($imageCachePath));
        header('Cache-Control: public, max-age=3600');
        readfile($imageCachePath);
        
        // Update convert_label in database if order ID provided
        if ($orderId) {
            updateConvertLabel($orderId, '/cache/labels/' . basename($imageCachePath));
        }
        exit;
    }
    
    // Download PDF
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, Env::get('LABEL_PROXY_TIMEOUT', 30));
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; POD-Label-Proxy/1.0)');
    
    // Limit file size to 10MB
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 128);
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $download_size, $downloaded, $upload_size, $uploaded) {
        if ($download_size > 10485760) { // 10MB limit
            return 1; // Abort transfer
        }
        return 0;
    });
    
    $pdfData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($pdfData)) {
        http_response_code(404);
        die('Error: Failed to fetch PDF');
    }
    
    // Save PDF temporarily
    $pdfPath = $cachePath . '.pdf';
    file_put_contents($pdfPath, $pdfData);
    
    // Convert PDF to image using ImageMagick (if available)
    if (class_exists('Imagick')) {
        try {
            $imagick = new Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($pdfPath . '[0]'); // First page only
            $imagick->setImageFormat('jpg');
            $imagick->setImageBackgroundColor('white');
            $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $imagick->setImageCompressionQuality(90);
            
            // Save converted image
            $imagick->writeImage($imageCachePath);
            $imagick->clear();
            $imagick->destroy();
            
            // Clean up PDF
            unlink($pdfPath);
            
            // Serve the converted image
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($imageCachePath));
            header('Cache-Control: public, max-age=3600');
            readfile($imageCachePath);
            
            // Update convert_label in database if order ID provided
            if ($orderId) {
                updateConvertLabel($orderId, '/cache/labels/' . basename($imageCachePath));
            }
            
        } catch (Exception $e) {
            // Try Ghostscript as fallback
            convertWithGhostscript($pdfPath, $imageCachePath, $orderId);
        }
    } else {
        // Use Ghostscript
        convertWithGhostscript($pdfPath, $imageCachePath, $orderId);
    }
} else {
    // For non-PDF files, just proxy them
    header('Location: /proxy-label.php?url=' . urlencode($url));
    exit;
}

function convertWithGhostscript($pdfPath, $outputPath, $orderId = null) {
    // Try using Ghostscript
    $command = sprintf(
        'gs -dNOPAUSE -dBATCH -sDEVICE=jpeg -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile=%s %s 2>&1',
        escapeshellarg($outputPath),
        escapeshellarg($pdfPath)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputPath)) {
        // Clean up PDF
        unlink($pdfPath);
        
        // Serve the converted image
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($outputPath));
        header('Cache-Control: public, max-age=3600');
        readfile($outputPath);
        
        // Update convert_label in database if order ID provided
        if ($orderId) {
            updateConvertLabel($orderId, '/cache/labels/' . basename($outputPath));
        }
    } else {
        // If conversion failed, try to at least show the PDF
        if (file_exists($pdfPath)) {
            header('Content-Type: application/pdf');
            header('Content-Length: ' . filesize($pdfPath));
            header('Content-Disposition: inline; filename="label.pdf"');
            readfile($pdfPath);
            unlink($pdfPath);
        } else {
            http_response_code(500);
            die('Error: PDF conversion failed. Please install Ghostscript or ImageMagick.');
        }
    }
}

function updateConvertLabel($orderId, $imagePath) {
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE orders SET convert_label = ?, updated_at_convert_label = NOW() WHERE id = ?");
        $stmt->execute([$imagePath, $orderId]);
    } catch (Exception $e) {
        error_log("Failed to update convert_label: " . $e->getMessage());
    }
}