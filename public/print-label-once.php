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
$labelUrl = !empty($order['convert_label']) ? $order['convert_label'] : $order['shipping_label'];

// Handle PDF conversion if needed
if (strpos(strtolower($labelUrl), '.pdf') !== false && empty($order['convert_label'])) {
    $labelUrl = '/convert-pdf-label.php?url=' . urlencode($labelUrl) . '&order=' . $orderId;
} elseif (filter_var($labelUrl, FILTER_VALIDATE_URL)) {
    $labelUrl = '/proxy-label.php?url=' . urlencode($labelUrl);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Label #<?php echo $orderId; ?></title>
    <style>
        * { margin: 0; padding: 0; }
        body { background: white; }
        img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
        @media print {
            @page { margin: 0; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <img src="<?php echo htmlspecialchars($labelUrl); ?>" 
         onload="printOnce()" 
         onerror="showError()">
    
    <script>
    let printTriggered = false;
    
    function printOnce() {
        // Only print once when image loads
        if (!printTriggered) {
            printTriggered = true;
            
            // Log print event to timeline
            fetch('/log-print.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=<?php echo $orderId; ?>'
            }).catch(err => console.log('Could not log print event:', err));
            
            // Small delay to ensure image is rendered
            setTimeout(() => {
                window.print();
            }, 200);
        }
    }
    
    function showError() {
        alert('Error loading label');
    }
    
    // Prevent Ctrl+P from printing twice
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            // Do nothing - already handled by onload
        }
    });
    </script>
</body>
</html>