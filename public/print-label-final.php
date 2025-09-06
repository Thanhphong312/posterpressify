<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/config/database.php';

$auth = new AuthController();
$auth->requireLogin();

$orderId = $_GET['order'] ?? 0;

if (!$orderId) {
    die('Error: No order specified');
}

// Get label URLs from database
$db = Database::getInstance();
$stmt = $db->prepare("SELECT shipping_label, convert_label, ref_id, tracking_id FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || empty($order['shipping_label'])) {
    die('Error: No label found for this order');
}

// Determine which label to use
if (!empty($order['convert_label'])) {
    // Use converted image if available
    $labelUrl = $order['convert_label'];
} else {
    // Convert PDF to image if needed
    $originalUrl = $order['shipping_label'];
    
    // Check if it's a PDF
    if (strpos(strtolower($originalUrl), '.pdf') !== false) {
        // Redirect to converter which will handle PDF->Image conversion
        $labelUrl = '/convert-pdf-label.php?url=' . urlencode($originalUrl) . '&order=' . $orderId;
    } else {
        // Use original if it's already an image
        $labelUrl = $originalUrl;
    }
}

// If it's an external URL and not our converter, use proxy
if (filter_var($labelUrl, FILTER_VALIDATE_URL) && strpos($labelUrl, '/convert-pdf-label.php') === false) {
    $labelUrl = '/proxy-label.php?url=' . urlencode($labelUrl);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Label #<?php echo $orderId; ?></title>
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            background: white;
            font-family: Arial, sans-serif;
        }
        
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        img { 
            max-width: 100%; 
            height: auto; 
            display: block;
        }
        
        .loading {
            text-align: center;
            color: #666;
        }
        
        @media print {
            @page { 
                margin: 0;
                size: auto;
            }
            body { 
                margin: 0;
            }
            .container {
                min-height: auto;
            }
            img {
                max-width: 100%;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="loading" class="loading">Loading label...</div>
        <img id="labelImage"
             src="<?php echo htmlspecialchars($labelUrl); ?>" 
             style="display: none;"
             onload="handleImageLoad()" 
             onerror="handleError()">
    </div>
    
    <script>
    let printed = false;
    
    function handleImageLoad() {
        // Hide loading text
        document.getElementById('loading').style.display = 'none';
        // Show image
        document.getElementById('labelImage').style.display = 'block';
        
        // Only auto-print if requested and not already printed
        <?php if (($_GET['autoprint'] ?? '1') === '1'): ?>
        if (!printed) {
            printed = true;
            setTimeout(() => {
                window.print();
            }, 100);
        }
        <?php endif; ?>
    }
    
    function handleError() {
        document.getElementById('loading').innerHTML = 'Error: Cannot load label. Please try again.';
    }
    
    // Keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            if (!printed) {
                printed = true;
                window.print();
                // Reset after a delay to allow printing again
                setTimeout(() => { printed = false; }, 1000);
            }
        }
    });
    </script>
</body>
</html>