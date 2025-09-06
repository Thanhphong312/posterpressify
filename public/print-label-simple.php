<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/config/database.php';

$auth = new AuthController();
$auth->requireLogin();

$orderId = $_GET['order'] ?? 0;

if (!$orderId) {
    die('Error: No order specified');
}

// Get label URL directly from database
$db = Database::getInstance();
$stmt = $db->prepare("SELECT shipping_label, ref_id, tracking_id, CONCAT(first_name, ' ', last_name) as customer_name FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || empty($order['shipping_label'])) {
    die('Error: No label found for this order');
}

$labelUrl = $order['shipping_label'];

// Check if need proxy (external URL)
if (filter_var($labelUrl, FILTER_VALIDATE_URL)) {
    $imageUrl = '/proxy-label.php?url=' . urlencode($labelUrl);
} else {
    $imageUrl = $labelUrl;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Label #<?php echo $orderId; ?></title>
    <style>
        body { margin: 0; padding: 0; }
        img { max-width: 100%; height: auto; display: block; }
        @media print {
            @page { margin: 0; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
         onload="window.print();" 
         onerror="alert('Cannot load label');">
</body>
</html>