<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';

$auth = new AuthController();
$auth->requireLogin();

$orderController = new OrderController();
$orders = $orderController->getRecentOrders(null, 1); // Get only 1 order for testing
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Display</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .test-container {
            width: 100%;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        
        .test-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .test-table th {
            background: #0066ff;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 13px;
        }
        
        .test-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        
        .test-table tr:hover {
            background: #f8f9fa;
        }
        
        .mockup-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-box {
            background: #ffebee;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h2>Test Display - Order Items</h2>
        
        <?php if (empty($orders)): ?>
            <div class="error-box">
                No orders found. Please add some orders to the database.
            </div>
        <?php else: ?>
            <?php 
            $order = $orders[0];
            ?>
            <div class="info-box">
                <strong>Order #<?php echo $order['id']; ?></strong><br>
                Customer: <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?><br>
                Items Count: <?php echo count($order['items'] ?? []); ?>
            </div>
            
            <?php if (!empty($order['items'])): ?>
                <h3>Items Table</h3>
                <table class="test-table">
                    <thead>
                        <tr>
                            <th>Mockup</th>
                            <th>Product</th>
                            <th>Variant ID</th>
                            <th>Style</th>
                            <th>Color</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['mockup'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['mockup']); ?>" 
                                             class="mockup-img"
                                             onerror="this.style.display='none'">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['variant_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['style'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($item['color'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($item['size'] ?? '-'); ?></td>
                                <td><?php echo $item['quantity'] ?? 1; ?></td>
                                <td>$<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="error-box">
                    No items found for this order.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h3>Debug Info</h3>
        <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 11px;">
<?php 
if (!empty($orders)) {
    echo "Order Data:\n";
    echo "ID: " . $order['id'] . "\n";
    echo "Customer: " . ($order['customer_name'] ?? 'N/A') . "\n";
    echo "Items Count: " . count($order['items'] ?? []) . "\n";
    
    if (!empty($order['items'])) {
        echo "\nFirst Item:\n";
        $firstItem = $order['items'][0];
        echo "- Product: " . ($firstItem['product_name'] ?? 'N/A') . "\n";
        echo "- Variant: " . ($firstItem['variant_id'] ?? 'N/A') . "\n";
        echo "- Style: " . ($firstItem['style'] ?? 'N/A') . "\n";
        echo "- Color: " . ($firstItem['color'] ?? 'N/A') . "\n";
        echo "- Size: " . ($firstItem['size'] ?? 'N/A') . "\n";
    }
}
?>
        </pre>
    </div>
</body>
</html>