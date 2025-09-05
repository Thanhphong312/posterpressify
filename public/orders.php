<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';

$auth = new AuthController();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$orderController = new OrderController();

$searchTerm = $_GET['search'] ?? '';
$orders = [];

if (!empty($searchTerm)) {
    $orders = $orderController->searchOrders($searchTerm, $currentUser['id']);
} else {
    $orders = $orderController->getRecentOrders($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - POD Order Manager</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <h1 class="logo">POD Order Manager</h1>
            </div>
            <div class="header-right">
                <span class="user-info">
                    Welcome, <?php echo htmlspecialchars($currentUser['display_name']); ?>
                </span>
                <a href="/logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <div class="search-section">
                <h2>Order Management</h2>
                <form method="GET" action="/orders.php" class="search-form">
                    <div class="search-wrapper">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search by Order ID or Order Number..." 
                            value="<?php echo htmlspecialchars($searchTerm); ?>"
                            class="search-input"
                        >
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="/orders.php" class="btn btn-outline">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="orders-section">
                <?php if (!empty($searchTerm) && empty($orders)): ?>
                    <div class="no-results">
                        <p>No orders found for "<?php echo htmlspecialchars($searchTerm); ?>"</p>
                    </div>
                <?php elseif (!empty($orders)): ?>
                    <div class="orders-grid">
                        <?php foreach ($orders as $order): ?>
                            <?php 
                            $statusInfo = $orderController->formatOrderStatus($order['fulfill_status'] ?? 'pending');
                            ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id">
                                        <strong>Order #<?php echo htmlspecialchars($order['id']); ?></strong>
                                        <?php if (!empty($order['ref_id'])): ?>
                                            <span class="order-ref"><?php echo htmlspecialchars($order['ref_id']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="order-status <?php echo $statusInfo['class']; ?>">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </div>
                                
                                <div class="order-details">
                                    <div class="order-info">
                                        <div class="info-row">
                                            <span class="info-label">Customer:</span>
                                            <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Items:</span>
                                            <span><?php echo $order['item_count'] ?? 0; ?> item(s)</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Total:</span>
                                            <span>$<?php echo number_format($order['total_cost'] ?? 0, 2); ?></span>
                                        </div>
                                        <?php if (!empty($order['tracking_id'])): ?>
                                            <div class="info-row">
                                                <span class="info-label">Tracking:</span>
                                                <span><?php echo htmlspecialchars($order['tracking_id']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="info-row">
                                            <span class="info-label">Date:</span>
                                            <span><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <a href="/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">
                                            View Details
                                        </a>
                                        <?php if (!empty($order['shipping_label'])): ?>
                                            <button onclick="printLabel('<?php echo $order['id']; ?>')" class="btn btn-sm btn-primary">
                                                Print Label
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <p>No recent orders found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
    function printLabel(orderId) {
        const printWindow = window.open('/print-label.php?order=' + orderId, 'PrintLabel', 'width=800,height=600');
        printWindow.focus();
    }
    </script>
</body>
</html>