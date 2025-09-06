<?php
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/controllers/OrderController.php';

echo "Order Search Debug Tool\n";
echo "=======================\n\n";

$searchId = '2038099';
echo "Searching for order ID: $searchId\n\n";

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Test 1: Direct query for the order
    echo "Test 1: Direct query by ID\n";
    $stmt = $connection->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$searchId]);
    $order = $stmt->fetch();
    
    if ($order) {
        echo "✓ Order found in database!\n";
        echo "  - ID: " . $order['id'] . "\n";
        echo "  - Ref ID: " . ($order['ref_id'] ?? 'NULL') . "\n";
        echo "  - Seller ID: " . ($order['seller_id'] ?? 'NULL') . "\n";
        echo "  - Status: " . ($order['fulfill_status'] ?? 'NULL') . "\n";
        echo "  - Created: " . $order['created_at'] . "\n\n";
    } else {
        echo "✗ Order NOT found with direct query\n\n";
    }
    
    // Test 2: Check if it's treated as numeric
    echo "Test 2: Check data type handling\n";
    echo "  - is_numeric('$searchId'): " . (is_numeric($searchId) ? 'true' : 'false') . "\n";
    echo "  - Value as int: " . intval($searchId) . "\n\n";
    
    // Test 3: Try OrderController search
    echo "Test 3: OrderController search method\n";
    $orderController = new OrderController();
    $results = $orderController->searchOrders($searchId);
    
    if (!empty($results)) {
        echo "✓ OrderController found " . count($results) . " result(s)\n";
        foreach ($results as $result) {
            echo "  - Order #" . $result['id'] . " (Ref: " . ($result['ref_id'] ?? 'none') . ")\n";
        }
    } else {
        echo "✗ OrderController returned empty results\n";
    }
    echo "\n";
    
    // Test 4: Check for seller_id constraint
    echo "Test 4: Check with specific seller_id\n";
    $stmt = $connection->prepare("SELECT id, seller_id FROM orders WHERE id = ?");
    $stmt->execute([$searchId]);
    $order = $stmt->fetch();
    
    if ($order) {
        echo "  - Order seller_id: " . ($order['seller_id'] ?? 'NULL') . "\n";
        
        // Try search with that seller_id
        if ($order['seller_id']) {
            $results = $orderController->searchOrders($searchId, $order['seller_id']);
            if (!empty($results)) {
                echo "  ✓ Found when using seller_id: " . $order['seller_id'] . "\n";
            } else {
                echo "  ✗ Not found even with seller_id: " . $order['seller_id'] . "\n";
            }
        }
    }
    echo "\n";
    
    // Test 5: Check order_items relationship
    echo "Test 5: Check order_items\n";
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
    $stmt->execute([$searchId]);
    $result = $stmt->fetch();
    echo "  - Order has " . $result['count'] . " item(s)\n\n";
    
    // Test 6: Try alternative search as string
    echo "Test 6: Search as ref_id (string)\n";
    $stmt = $connection->prepare("SELECT * FROM orders WHERE ref_id LIKE ?");
    $stmt->execute(['%' . $searchId . '%']);
    $orders = $stmt->fetchAll();
    echo "  - Found " . count($orders) . " order(s) with ref_id containing '$searchId'\n";
    
    if (count($orders) > 0) {
        foreach ($orders as $order) {
            echo "    Order ID: " . $order['id'] . ", Ref ID: " . $order['ref_id'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}