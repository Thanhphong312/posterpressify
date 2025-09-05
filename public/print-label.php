<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';

$auth = new AuthController();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$orderController = new OrderController();

$orderId = $_GET['order'] ?? 0;
$order = null;

if ($orderId) {
    $order = $orderController->getOrderDetails($orderId, $currentUser['id']);
}

if (!$order || empty($order['shipping_label'])) {
    die('Error: Label not found');
}

$labelUrl = $order['shipping_label'];
if (filter_var($labelUrl, FILTER_VALIDATE_URL)) {
    $proxyUrl = '/proxy-label.php?url=' . urlencode($labelUrl);
} else {
    die('Error: Invalid label URL');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Label - Order #<?php echo htmlspecialchars($orderId); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        
        .print-header {
            padding: 20px;
            background: white;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        
        .print-header h1 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .print-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .print-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #0066ff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0052cc;
        }
        
        .btn-outline {
            background: white;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-outline:hover {
            background: #f5f5f5;
        }
        
        .label-container {
            padding: 20px;
            background: white;
            margin: 20px auto;
            max-width: 800px;
            text-align: center;
        }
        
        .label-image {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
        }
        
        .loading {
            padding: 40px;
            text-align: center;
            color: #666;
        }
        
        .error {
            padding: 40px;
            text-align: center;
            color: #d32f2f;
        }
        
        @media print {
            .print-header {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .label-container {
                margin: 0;
                padding: 0;
                border: none;
            }
            
            .label-image {
                border: none;
                max-width: 100%;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h1>Shipping Label - Order #<?php echo htmlspecialchars($orderId); ?></h1>
        <div class="print-info">
            <?php if (!empty($order['ref_id'])): ?>
                Reference: <?php echo htmlspecialchars($order['ref_id']); ?><br>
            <?php endif; ?>
            Customer: <?php echo htmlspecialchars($order['customer_name']); ?><br>
            <?php if (!empty($order['tracking_id'])): ?>
                Tracking: <?php echo htmlspecialchars($order['tracking_id']); ?>
            <?php endif; ?>
        </div>
        <div class="print-actions">
            <button onclick="window.print()" class="btn btn-primary">Print Label</button>
            <button onclick="window.close()" class="btn btn-outline">Close Window</button>
        </div>
    </div>
    
    <div class="label-container">
        <div id="loading" class="loading">Loading label...</div>
        <img id="labelImage" 
             src="<?php echo htmlspecialchars($proxyUrl); ?>" 
             alt="Shipping Label" 
             class="label-image"
             style="display: none;"
             onload="handleImageLoad()"
             onerror="handleImageError()">
    </div>
    
    <script>
    function handleImageLoad() {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('labelImage').style.display = 'block';
        
        // Auto-print if requested
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') === '1') {
            setTimeout(() => {
                window.print();
            }, 500);
        }
    }
    
    function handleImageError() {
        document.getElementById('loading').innerHTML = 
            '<div class="error">Failed to load label. Please try again or contact support.</div>';
    }
    
    // Keyboard shortcut for printing
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });
    </script>
</body>
</html>