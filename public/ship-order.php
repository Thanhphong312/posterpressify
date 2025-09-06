<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';
require_once __DIR__ . '/../src/config/database.php';

$auth = new AuthController();
$auth->requireLogin();

header('Content-Type: application/json');

$orderId = $_POST['order_id'] ?? 0;
$action = $_POST['action'] ?? 'ship';

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $orderController = new OrderController();
    
    // Check if order exists
    $checkStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $checkStmt->execute([$orderId]);
    $order = $checkStmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Check if this is a test order and if test orders are disabled
    if ($orderController->isTestOrder($order) && !$orderController->isTestOrderEnabled()) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot ship test orders. Enable ENABLE_TEST_ORDER in .env to allow shipping test orders.'
        ]);
        exit;
    }
    
    // Check if already shipped
    if ($order['fulfill_status'] === 'shipped') {
        echo json_encode([
            'success' => true, 
            'message' => 'Order already shipped',
            'already_shipped' => true,
            'has_label' => !empty($order['shipping_label'])
        ]);
        exit;
    }
    
    // Update status to shipped
    $updateStmt = $db->prepare("
        UPDATE orders 
        SET fulfill_status = 'shipped',
            updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$orderId]);
    
    // Log the shipping action to timeline
    require_once __DIR__ . '/../src/controllers/TimelineController.php';
    $timeline = new TimelineController();
    
    // Get current user info for logging
    $currentUser = $auth->getCurrentUser();
    $userName = $currentUser['display_name'] ?? $currentUser['username'] ?? 'System';
    $userId = $currentUser['id'] ?? null;
    
    // Log the shipping event
    $timeline->logShipped($orderId, $userId, $userName);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order marked as shipped successfully',
        'has_label' => !empty($order['shipping_label']),
        'tracking_id' => $order['tracking_id']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}