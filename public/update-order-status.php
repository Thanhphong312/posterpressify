<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';
require_once __DIR__ . '/../src/config/database.php';

$auth = new AuthController();
$auth->requireLogin();

header('Content-Type: application/json');

$orderId = $_POST['order_id'] ?? 0;
$status = $_POST['status'] ?? '';

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

if (!$status) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
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
    
    // Store old status for logging
    $oldStatus = $order['fulfill_status'];
    
    // Update order status
    $updateStmt = $db->prepare("
        UPDATE orders 
        SET fulfill_status = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$status, $orderId]);
    
    // Log the status change to timeline
    require_once __DIR__ . '/../src/controllers/TimelineController.php';
    $timeline = new TimelineController();
    
    // Get current user info for logging
    $currentUser = $auth->getCurrentUser();
    $userName = $currentUser['display_name'] ?? $currentUser['username'] ?? 'System';
    $userId = $currentUser['id'] ?? null;
    
    // Log the status change event
    if ($status === 'return_to_support') {
        $note = $userName . " returned order to support";
        $timeline->addEntry($orderId, 'return_to_support', $note, null, $userId, $userName);
    } else {
        $timeline->logStatusChange($orderId, $oldStatus, $status, $userId, $userName);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order status updated successfully',
        'new_status' => $status,
        'old_status' => $oldStatus
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}