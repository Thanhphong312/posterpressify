<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';

$auth = new AuthController();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$orderController = new OrderController();

$orderId = $_GET['id'] ?? 0;
$order = null;

if ($orderId) {
    $order = $orderController->getOrderDetails($orderId, $currentUser['id']);
}

if (!$order) {
    header('Location: /orders.php');
    exit();
}

$statusInfo = $orderController->formatOrderStatus($order['fulfill_status'] ?? 'pending');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo htmlspecialchars($order['id']); ?> - POD Order Manager</title>
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
            <div class="breadcrumb">
                <a href="/orders.php">← Back to Orders</a>
            </div>
            
            <div class="order-detail-header">
                <h2>Order #<?php echo htmlspecialchars($order['id']); ?></h2>
                <span class="order-status <?php echo $statusInfo['class']; ?>">
                    <?php echo $statusInfo['label']; ?>
                </span>
            </div>
            
            <div class="detail-grid">
                <div class="detail-section">
                    <h3>Order Information</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <span class="detail-label">Order ID:</span>
                            <span><?php echo htmlspecialchars($order['id']); ?></span>
                        </div>
                        <?php if (!empty($order['ref_id'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Reference ID:</span>
                            <span><?php echo htmlspecialchars($order['ref_id']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-label">Order Date:</span>
                            <span><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></span>
                        </div>
                        <?php if (!empty($order['tracking_id'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Tracking Number:</span>
                            <span><?php echo htmlspecialchars($order['tracking_id']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($order['shipping_service'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Shipping Service:</span>
                            <span><?php echo htmlspecialchars($order['shipping_service']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3>Customer Information</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <?php if (!empty($order['phone'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span><?php echo htmlspecialchars($order['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-label">Address:</span>
                            <span>
                                <?php echo htmlspecialchars($order['address_1']); ?><br>
                                <?php if (!empty($order['address_2'])): ?>
                                    <?php echo htmlspecialchars($order['address_2']); ?><br>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($order['city']); ?>, 
                                <?php echo htmlspecialchars($order['state']); ?> 
                                <?php echo htmlspecialchars($order['postcode']); ?><br>
                                <?php echo htmlspecialchars($order['country']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3>Pricing Details</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <span class="detail-label">Print Cost:</span>
                            <span>$<?php echo number_format($order['print_cost'] ?? 0, 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Shipping Cost:</span>
                            <span>$<?php echo number_format($order['shipping_cost'] ?? 0, 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Cost:</span>
                            <span><strong>$<?php echo number_format($order['total_cost'] ?? 0, 2); ?></strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Status:</span>
                            <span><?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="items-section">
                <h3>Order Items</h3>
                <?php if (!empty($order['items'])): ?>
                    <div class="items-table-wrapper">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Variant</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($item['mockup'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['mockup']); ?>" 
                                                     alt="Product" 
                                                     class="item-thumbnail">
                                            <?php else: ?>
                                                <div class="no-image">No Image</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            $variant = [];
                                            if (!empty($item['style'])) $variant[] = $item['style'];
                                            if (!empty($item['color'])) $variant[] = $item['color'];
                                            if (!empty($item['size'])) $variant[] = $item['size'];
                                            echo htmlspecialchars(implode(' / ', $variant) ?: 'N/A');
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                        <td><?php echo $item['quantity'] ?? 1; ?></td>
                                        <td>$<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                                        <td>$<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No items found for this order.</p>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <?php if (!empty($order['shipping_label'])): ?>
                    <button onclick="printLabel('<?php echo $order['id']; ?>')" class="btn btn-primary">
                        Print Shipping Label
                    </button>
                <?php endif; ?>
                <a href="/orders.php" class="btn btn-outline">Back to Orders</a>
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