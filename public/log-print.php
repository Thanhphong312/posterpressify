<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/TimelineController.php';

$auth = new AuthController();
$auth->requireLogin();

header('Content-Type: application/json');

$orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? 0;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    $timeline = new TimelineController();
    
    // Get current user info
    $currentUser = $auth->getCurrentUser();
    $userName = $currentUser['display_name'] ?? $currentUser['username'] ?? 'System';
    $userId = $currentUser['id'] ?? null;
    
    // Log the print event
    $timeline->logLabelPrinted($orderId, $userId, $userName);
    
    echo json_encode([
        'success' => true,
        'message' => 'Print event logged'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}