<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';
require_once __DIR__ . '/../src/controllers/LabelController.php';

$auth = new AuthController();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$orderController = new OrderController();
$labelController = new LabelController();

$orderId = $_GET['order'] ?? 0;
$autoPrint = $_GET['autoprint'] ?? '1'; // Default to auto-print
$order = null;

if ($orderId) {
    $order = $orderController->getOrderDetails($orderId);
}

if (!$order) {
    die('Error: Order not found');
}

// Prepare label for printing (handles conversion if needed)
$labelData = $labelController->prepareLabelForPrint($orderId);

if (!$labelData['success']) {
    die('Error: ' . ($labelData['message'] ?? 'Failed to prepare label'));
}

$proxyUrl = $labelData['print_url'];
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
            background: white;
        }
        
        .print-controls {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .print-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .order-info {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .tracking-info {
            font-size: 13px;
            color: #666;
        }
        
        .print-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #0066ff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0052cc;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: white;
            color: #333;
            border: 1px solid #dee2e6;
        }
        
        .btn-secondary:hover {
            background: #f8f9fa;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .label-container {
            margin-top: 80px;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
        }
        
        .label-wrapper {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 100%;
        }
        
        .label-image {
            display: block;
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
        }
        
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 40px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0066ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: #666;
            font-size: 14px;
        }
        
        .error {
            background: #fff5f5;
            color: #d32f2f;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ffcdd2;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-indicator.converted {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        /* Print-specific styles */
        @media print {
            .print-controls {
                display: none !important;
            }
            
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            
            .label-container {
                margin: 0;
                padding: 0;
                min-height: auto;
                display: block;
            }
            
            .label-wrapper {
                box-shadow: none;
                padding: 0;
                border-radius: 0;
            }
            
            .label-image {
                border: none;
                max-width: 100%;
                page-break-inside: avoid;
                margin: 0 auto;
                display: block;
            }
            
            /* Force page break after each label for multiple labels */
            .label-wrapper {
                page-break-after: always;
            }
        }
        
        /* Keyboard shortcut hint */
        .shortcut-hint {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1001;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .shortcut-hint.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <div class="print-info">
            <div class="order-info">
                Order #<?php echo htmlspecialchars($orderId); ?>
                <?php if (!empty($order['ref_id'])): ?>
                    <span style="color: #666; font-size: 14px; font-weight: normal;">
                        (<?php echo htmlspecialchars($order['ref_id']); ?>)
                    </span>
                <?php endif; ?>
            </div>
            <?php if (!empty($labelData['tracking_id'])): ?>
                <div class="tracking-info">
                    Tracking: <?php echo htmlspecialchars($labelData['tracking_id']); ?>
                </div>
            <?php endif; ?>
            <div id="conversionStatus" class="status-indicator">
                <span class="status-text">Ready to print</span>
            </div>
        </div>
        
        <div class="print-actions">
            <button onclick="window.print()" class="btn btn-primary" id="printBtn">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Print Label (Ctrl+P)
            </button>
            <button onclick="downloadLabel()" class="btn btn-secondary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Download
            </button>
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>
    </div>
    
    <div class="label-container">
        <div class="label-wrapper">
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <div class="loading-text">Loading and converting label...</div>
            </div>
            <img id="labelImage" 
                 src="<?php echo htmlspecialchars($proxyUrl); ?>" 
                 alt="Shipping Label" 
                 class="label-image"
                 style="display: none;"
                 onload="handleImageLoad()"
                 onerror="handleImageError()">
        </div>
    </div>
    
    <div id="shortcutHint" class="shortcut-hint">
        Press Ctrl+P to print
    </div>
    
    <script>
    let labelLoaded = false;
    const autoPrint = <?php echo json_encode($autoPrint === '1'); ?>;
    
    function handleImageLoad() {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('labelImage').style.display = 'block';
        labelLoaded = true;
        
        // Update status
        const statusEl = document.getElementById('conversionStatus');
        statusEl.classList.add('converted');
        statusEl.querySelector('.status-text').textContent = 'Label ready';
        
        // Auto-print if enabled
        if (autoPrint) {
            setTimeout(() => {
                window.print();
                showShortcutHint();
            }, 500);
        } else {
            showShortcutHint();
        }
    }
    
    function handleImageError() {
        document.getElementById('loading').innerHTML = 
            '<div class="error">Failed to load label. Please try again or contact support.</div>';
    }
    
    function downloadLabel() {
        if (!labelLoaded) {
            alert('Please wait for the label to load');
            return;
        }
        
        // Create a link element and trigger download
        const link = document.createElement('a');
        link.href = document.getElementById('labelImage').src;
        link.download = 'label_order_<?php echo $orderId; ?>.png';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    function showShortcutHint() {
        const hint = document.getElementById('shortcutHint');
        hint.classList.add('show');
        setTimeout(() => {
            hint.classList.remove('show');
        }, 3000);
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+P or Cmd+P for print
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            if (labelLoaded) {
                window.print();
            }
        }
        
        // Ctrl+D or Cmd+D for download
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            if (labelLoaded) {
                downloadLabel();
            }
        }
        
        // Escape to close window
        if (e.key === 'Escape') {
            window.close();
        }
    });
    
    // Show print button focus on hover
    document.getElementById('printBtn').addEventListener('mouseenter', function() {
        showShortcutHint();
    });
    
    // Track print events
    window.addEventListener('beforeprint', function() {
        console.log('Printing label for order <?php echo $orderId; ?>');
    });
    
    window.addEventListener('afterprint', function() {
        console.log('Print dialog closed');
        // Optionally update the database that label was printed
    });
    </script>
</body>
</html>