<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';
require_once __DIR__ . '/../src/controllers/TimelineController.php';

$auth = new AuthController();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$orderController = new OrderController();
$timelineController = new TimelineController();

$orderId = $_GET['id'] ?? 0;
$order = null;
$timeline = [];

if ($orderId) {
    // Remove seller_id filter to allow viewing any order
    $order = $orderController->getOrderDetails($orderId);
    // Get timeline for this order
    $timeline = $timelineController->getOrderTimeline($orderId);
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
    <title>Order #<?php echo htmlspecialchars($order['id']); ?> - Pressify Poster</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <h1 class="logo">Pressify Poster</h1>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No items found for this order.</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($timeline)): ?>
            <div class="timeline-section">
                <h3>Order Timeline</h3>
                <div class="timeline-container">
                    <?php foreach ($timeline as $event): 
                        $actionInfo = $timelineController->formatAction($event['action']);
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <?php echo $actionInfo['icon']; ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong><?php echo $actionInfo['label']; ?></strong>
                                <span class="timeline-date">
                                    <?php echo date('Y-m-d H:i:s', strtotime($event['created_at'])); ?>
                                </span>
                            </div>
                            <?php if (!empty($event['note'])): ?>
                            <div class="timeline-details">
                                <?php echo htmlspecialchars($event['note']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <?php if ($order['fulfill_status'] !== 'shipped' && !empty($order['shipping_label'])): ?>
                    <button onclick="shipOrder('<?php echo $order['id']; ?>')" class="btn btn-success">
                        Mark as Shipped
                    </button>
                <?php endif; ?>
                <?php if ($order['fulfill_status'] !== 'return_to_support'): ?>
                    <button onclick="returnToSupport('<?php echo $order['id']; ?>')" class="btn btn-warning">
                        Return to Support
                    </button>
                <?php endif; ?>
                <?php if (!empty($order['shipping_label'])): ?>
                    <?php if ($order['fulfill_status'] === 'shipped'): ?>
                        <button onclick="printLabel('<?php echo $order['id']; ?>')" class="btn btn-primary">
                            Print Label
                        </button>
                    <?php else: ?>
                        <button class="btn btn-disabled" disabled title="Ship order first to enable printing">
                            Print Label
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="/orders.php" class="btn btn-outline">Back to Orders</a>
            </div>
        </div>
    </main>
    
    <script src="/assets/js/print-label-advanced.js"></script>
    <script>
    // Print configuration - labelPrinter already defined in print-label-advanced.js
    // Just reconfigure if needed
    if (typeof labelPrinter !== 'undefined') {
        labelPrinter.options.mode = 'direct';
        labelPrinter.options.autoprint = true;
    }
    
    // Return order to support
    function returnToSupport(orderId) {
        // Update button to show loading
        const button = event.target;
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Processing...';
        
        // Send AJAX request
        fetch('/update-order-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId + '&status=return_to_support'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide button
                button.style.display = 'none';
                
                // Update status badge
                const statusBadge = document.querySelector('.order-status');
                if (statusBadge) {
                    statusBadge.className = 'order-status status-return-support';
                    statusBadge.textContent = 'Return to Support';
                }
                
                // Hide ship button if exists
                const shipBtn = document.querySelector('.btn-success');
                if (shipBtn && shipBtn.textContent.includes('Ship')) {
                    shipBtn.style.display = 'none';
                }
                
                // Show success message
                showNotification('Order #' + orderId + ' returned to support!', 'warning');
                
                // Reload page after 2 seconds to update timeline
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                // Restore button on error
                button.disabled = false;
                button.textContent = originalText;
                alert('Error: ' + (data.message || 'Failed to update order'));
            }
        })
        .catch(error => {
            // Restore button on error
            button.disabled = false;
            button.textContent = originalText;
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
    }
    
    // Ship order and auto-print label
    function shipOrder(orderId) {
        // No confirmation needed - ship directly
        
        // Update button to show loading
        const button = event.target;
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Processing...';
        
        // Send AJAX request
        fetch('/ship-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId + '&action=ship'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide ship button
                button.style.display = 'none';
                
                // Update status badge
                const statusBadge = document.querySelector('.order-status');
                if (statusBadge) {
                    statusBadge.className = 'order-status status-shipped';
                    statusBadge.textContent = 'Shipped';
                }
                
                // Auto-print label after successful ship
                if (data.has_label) {
                    // Enable the disabled print button
                    const disabledPrintBtn = document.querySelector('.btn-disabled:disabled');
                    let printBtn = document.querySelector('button[onclick*="printLabel"]');
                    
                    if (disabledPrintBtn && disabledPrintBtn.textContent.includes('Print')) {
                        // Convert disabled button to enabled print button
                        disabledPrintBtn.disabled = false;
                        disabledPrintBtn.className = 'btn btn-primary';
                        disabledPrintBtn.title = '';
                        disabledPrintBtn.onclick = () => printLabel(orderId);
                        printBtn = disabledPrintBtn;
                    }
                    
                    if (printBtn) {
                        printBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    
                    setTimeout(() => {
                        console.log('Auto-printing label for order ' + orderId);
                        printLabel(orderId);
                    }, 500);
                }
                
                // Show success message
                showNotification('Order #' + orderId + ' marked as shipped successfully!', 'success');
            } else {
                // Restore button on error
                button.disabled = false;
                button.textContent = originalText;
                
                if (data.already_shipped) {
                    showNotification('Order is already shipped', 'info');
                    button.style.display = 'none';
                } else {
                    alert('Error: ' + (data.message || 'Failed to update order'));
                }
            }
        })
        .catch(error => {
            // Restore button on error
            button.disabled = false;
            button.textContent = originalText;
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
    }
    
    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : type === 'info' ? '#17a2b8' : '#0066ff'};
            color: ${type === 'warning' ? '#000' : 'white'};
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10001;
            animation: slideInRight 0.3s ease;
            font-size: 14px;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
    }
    
    // Add animation and timeline styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-warning {
            background: #ffc107;
            color: #000;
            border: 1px solid #ffc107;
        }
        .btn-warning:hover {
            background: #e0a800;
            border-color: #e0a800;
        }
        .status-return-support {
            background: #ffc107;
            color: #000;
        }
        
        /* Timeline styles */
        .timeline-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .timeline-section h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
        }
        
        .timeline-container {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline-container::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 20px;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-icon {
            position: absolute;
            left: -25px;
            width: 30px;
            height: 30px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 3px solid #0066ff;
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .timeline-header strong {
            color: #2c3e50;
            font-size: 14px;
        }
        
        .timeline-date {
            color: #6c757d;
            font-size: 12px;
        }
        
        .timeline-details {
            color: #495057;
            font-size: 13px;
            margin-top: 5px;
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>