<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';

$auth = new AuthController();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$orderController = new OrderController();

$searchTerm = $_GET['search'] ?? '';
$orders = [];

// Check if user is admin (you may need to adjust this based on your role system)
// For now, let's remove seller_id filter to allow searching all orders
// You can add role-based filtering later
if (!empty($searchTerm)) {
    // Remove seller_id filter for search to allow finding any order
    $orders = $orderController->searchOrders($searchTerm);
} else {
    // Keep seller_id filter for recent orders (optional)
    $orders = $orderController->getRecentOrders();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Pressify Poster</title>
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
                    <div class="orders-list">
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
                                        <?php if ($orderController->isTestOrder($order)): ?>
                                            <span class="badge-test" style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">TEST</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="order-status <?php echo $statusInfo['class']; ?>">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </div>
                                
                                <div class="order-details">
                                    <div class="order-info-summary">
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
                                    
                                    <!-- Full Details (Always Visible) -->
                                    <div class="order-full-details" id="details-<?php echo $order['id']; ?>">
                                        
                                        <?php if (!empty($order['items'])): ?>
                                            <!-- Item Information (Main Focus) -->
                                            <div class="items-section-main">
                                                <h4 class="items-title">📦 Item Information</h4>
                                                <div class="items-table-container">
                                                    <table class="items-table">
                                                        <thead>
                                                            <tr>
                                                                <th class="th-mockup">Mockup</th>
                                                                <th class="th-product">Product Name</th>
                                                                <th class="th-variant">Variant ID</th>
                                                                <th class="th-style">Style</th>
                                                                <th class="th-color">Color</th>
                                                                <th class="th-size">Size</th>
                                                                <th class="th-qty">Qty</th>
                                                                <th class="th-price">Price</th>
                                                                <th class="th-total">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php 
                                                            $orderTotal = 0;
                                                            foreach ($order['items'] as $item): 
                                                                $itemTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                                                                $orderTotal += $itemTotal;
                                                            ?>
                                                                <tr class="item-row">
                                                                    <td class="td-mockup">
                                                                        <?php if (!empty($item['mockup'])): ?>
                                                                            <div class="mockup-wrapper">
                                                                                <img src="<?php echo htmlspecialchars($item['mockup']); ?>" 
                                                                                     class="mockup-img"
                                                                                     alt="Product"
                                                                                     onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=no-img>No Image</span>';">
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <span class="no-img">No Image</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td class="td-product">
                                                                        <div class="product-name"><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></div>
                                                                        <?php if (!empty($item['sku'])): ?>
                                                                            <div class="product-sku">SKU: <?php echo htmlspecialchars($item['sku']); ?></div>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td class="td-variant"><?php echo htmlspecialchars($item['variant_id'] ?? 'N/A'); ?></td>
                                                                    <td class="td-style"><?php echo htmlspecialchars($item['style'] ?? '-'); ?></td>
                                                                    <td class="td-color"><?php echo htmlspecialchars($item['color'] ?? '-'); ?></td>
                                                                    <td class="td-size"><?php echo htmlspecialchars($item['size'] ?? '-'); ?></td>
                                                                    <td class="td-qty"><?php echo $item['quantity'] ?? 1; ?></td>
                                                                    <td class="td-price">$<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                                                                    <td class="td-total"><strong>$<?php echo number_format($itemTotal, 2); ?></strong></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="total-row">
                                                                <td colspan="8" class="text-right">Subtotal:</td>
                                                                <td class="order-total"><strong>$<?php echo number_format($orderTotal, 2); ?></strong></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Compact Order Info -->
                                        <div class="compact-info">
                                            <div class="info-group">
                                                <div class="info-item">
                                                    <span class="info-icon">👤</span>
                                                    <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-icon">📍</span>
                                                    <span>
                                                        <?php echo htmlspecialchars($order['city'] ?? ''); ?><?php if (!empty($order['state'])): ?>, <?php echo htmlspecialchars($order['state']); ?><?php endif; ?>
                                                        <?php echo htmlspecialchars($order['postcode'] ?? ''); ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($order['phone'])): ?>
                                                <div class="info-item">
                                                    <span class="info-icon">📞</span>
                                                    <span><?php echo htmlspecialchars($order['phone']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="info-group">
                                                <div class="info-item">
                                                    <span class="info-icon">💰</span>
                                                    <span>Total: <strong>$<?php echo number_format($order['total_cost'] ?? 0, 2); ?></strong></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-icon">🚚</span>
                                                    <span><?php echo htmlspecialchars($order['shipping_service'] ?? 'Standard'); ?></span>
                                                </div>
                                                <?php if (!empty($order['tracking_id'])): ?>
                                                <div class="info-item">
                                                    <span class="info-icon">📦</span>
                                                    <span><?php echo htmlspecialchars($order['tracking_id']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <?php 
                                        $isTestOrder = $orderController->isTestOrder($order);
                                        $testOrderEnabled = $orderController->isTestOrderEnabled();
                                        ?>
                                        <?php if ($order['fulfill_status'] !== 'shipped'): ?>
                                            <?php if (!$isTestOrder || $testOrderEnabled): ?>
                                                <button class="ship-btn btn btn-sm btn-success" data-order-id="<?php echo $order['id']; ?>">
                                                    Ship
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-disabled" disabled title="Test orders are disabled">
                                                    Ship (Test)
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($order['fulfill_status'] !== 'return_to_support'): ?>
                                            <button class="return-support-btn btn btn-sm btn-warning" data-order-id="<?php echo $order['id']; ?>">
                                                Return to Support
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!empty($order['shipping_label'])): ?>
                                            <?php if ($order['fulfill_status'] === 'shipped'): ?>
                                                <button class="print-btn btn btn-sm btn-primary" data-order-id="<?php echo $order['id']; ?>">
                                                    Print Label
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-disabled" disabled title="Ship order first to enable printing">
                                                    Print Label
                                                </button>
                                            <?php endif; ?>
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
    
    <script src="/assets/js/print-label-advanced.js"></script>
    <script>
    // Print configuration - labelPrinter already defined in print-label-advanced.js
    // Just reconfigure if needed
    if (typeof labelPrinter !== 'undefined') {
        labelPrinter.options.mode = 'direct';
        labelPrinter.options.autoprint = true;
    }
    
    // Initialize event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners for ship buttons
        document.querySelectorAll('.ship-btn').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                shipOrder(orderId, this);
            });
        });
        
        // Add event listeners for return to support buttons
        document.querySelectorAll('.return-support-btn').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                returnToSupport(orderId, this);
            });
        });
        
        // Add event listeners for print buttons
        document.querySelectorAll('.print-btn').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                printLabel(orderId);
            });
        });
    });
    
    // Return order to support
    function returnToSupport(orderId, buttonElement = null) {
        // Update button to show loading
        const button = buttonElement || event.target;
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
                // Update button
                button.textContent = 'Returned ✓';
                button.className = 'btn btn-sm btn-outline';
                button.disabled = true;
                
                // Update status badge
                const card = button.closest('.order-card');
                if (card) {
                    const statusBadge = card.querySelector('.order-status');
                    if (statusBadge) {
                        statusBadge.className = 'order-status status-return-support';
                        statusBadge.textContent = 'Return to Support';
                    }
                    
                    // Hide ship button if exists
                    const shipBtn = card.querySelector('.ship-btn');
                    if (shipBtn) {
                        shipBtn.style.display = 'none';
                    }
                }
                
                // Show success message
                showNotification('Order #' + orderId + ' returned to support!', 'warning');
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
    function shipOrder(orderId, buttonElement = null) {
        // No confirmation needed - ship directly
        
        // Update button to show loading
        const button = buttonElement || event.target;
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Shipping...';
        
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
                // Update button to show shipped
                button.textContent = 'Shipped ✓';
                button.className = 'btn btn-sm btn-outline';
                button.disabled = true;
                
                // Update status badge
                const card = button.closest('.order-card');
                if (card) {
                    const statusBadge = card.querySelector('.order-status');
                    if (statusBadge) {
                        statusBadge.className = 'order-status status-shipped';
                        statusBadge.textContent = 'Shipped';
                    }
                }
                
                // Auto-print label after successful ship
                if (data.has_label) {
                    // Enable the disabled print button
                    const disabledPrintBtn = card.querySelector('.btn-disabled:disabled');
                    let printBtn = card.querySelector('.print-btn');
                    
                    if (disabledPrintBtn && disabledPrintBtn.textContent.includes('Print')) {
                        // Convert disabled button to enabled print button
                        disabledPrintBtn.disabled = false;
                        disabledPrintBtn.className = 'print-btn btn btn-sm btn-primary';
                        disabledPrintBtn.setAttribute('data-order-id', orderId);
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
                
                // Show success message (optional)
                showNotification('Order #' + orderId + ' marked as shipped!', 'success');
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
    
    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : '#0066ff'};
            color: ${type === 'warning' ? '#000' : 'white'};
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10001;
            animation: slideInRight 0.3s ease;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Add styles for disabled button and detail sections
    const disabledStyle = document.createElement('style');
    disabledStyle.textContent = `
        .btn-disabled {
            background: #e9ecef !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
            opacity: 0.6;
        }
        .btn-warning {
            background: #ffc107 !important;
            color: #000 !important;
            border: 1px solid #ffc107 !important;
        }
        .btn-warning:hover {
            background: #e0a800 !important;
            border-color: #e0a800 !important;
        }
        .status-return-support {
            background: #ffc107 !important;
            color: #000 !important;
        }
        .order-full-details {
            margin-top: 20px;
        }
        
        /* Items Section - Main Focus */
        .items-section-main {
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .items-title {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            padding-bottom: 10px;
            border-bottom: 2px solid #0066ff;
        }
        
        .items-table-container {
            overflow-x: auto;
            width: 100%;
        }
        
        .items-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .items-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .items-table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            white-space: nowrap;
            color: white !important;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s;
        }
        
        .items-table tbody tr:hover {
            background: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .items-table td {
            padding: 10px 8px;
            vertical-align: middle;
        }
        
        /* Column specific styles */
        .th-mockup, .td-mockup {
            width: 80px;
            min-width: 80px;
            text-align: center;
        }
        
        .th-product, .td-product {
            min-width: 200px;
        }
        
        .th-variant, .td-variant {
            min-width: 120px;
        }
        
        .th-style, .td-style {
            min-width: 100px;
        }
        
        .th-color, .td-color {
            min-width: 100px;
        }
        
        .th-size, .td-size {
            min-width: 80px;
        }
        
        .th-qty, .td-qty {
            width: 60px;
            min-width: 60px;
            text-align: center;
        }
        
        .th-price, .td-price {
            min-width: 100px;
            text-align: right;
        }
        
        .th-total, .td-total {
            min-width: 100px;
            text-align: right;
        }
        
        .mockup-wrapper {
            width: 60px;
            height: 60px;
            overflow: hidden;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid #e9ecef;
        }
        
        .mockup-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-img {
            color: #adb5bd;
            font-size: 11px;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.4;
        }
        
        .product-sku {
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
        }
        
        .td-variant {
            font-family: monospace;
            font-size: 12px;
            color: #495057;
        }
        
        .td-style, .td-color, .td-size {
            color: #495057;
        }
        
        .td-qty {
            text-align: center;
            font-weight: 600;
        }
        
        .td-price, .td-total {
            text-align: right;
            white-space: nowrap;
        }
        
        .td-total {
            font-weight: 700;
            color: #28a745;
        }
        
        /* Footer */
        .items-table tfoot {
            background: #f8f9fa;
            font-weight: 700;
        }
        
        .total-row td {
            padding: 12px 8px;
            font-size: 14px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .order-total {
            color: #28a745;
            font-size: 16px;
        }
        
        /* Compact Info Section */
        .compact-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .info-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #495057;
        }
        
        .info-icon {
            font-size: 14px;
        }
        .order-card {
            margin-bottom: 0;
            padding: 20px;
            width: 100%;
            max-width: none !important;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            width: 100%;
        }
        
        .order-id {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .order-info-summary {
            display: none; /* Hide summary since we show full details */
        }
        .orders-list {
            width: 100%;
            max-width: none;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .orders-grid {
            display: none !important; /* Hide grid layout */
        }
        .container {
            max-width: none !important;
            width: 100%;
            padding: 0 20px;
        }
        .main-content {
            width: 100%;
            max-width: none;
        }
        .info-row {
            display: flex;
            padding: 4px 0;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 120px;
            font-size: 12px;
        }
        .order-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Full width layout */
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            overflow-x: auto;
        }
        
        .header-container {
            max-width: none !important;
            width: 100%;
            padding: 0 20px;
        }
        
        .search-section {
            width: 100%;
            max-width: none;
        }
        
        /* Responsive for large screens */
        @media (min-width: 1400px) {
            .items-table {
                min-width: 100%;
            }
            
            .compact-info {
                display: flex;
                justify-content: space-between;
                gap: 40px;
            }
            
            .container {
                padding: 0 40px;
            }
        }
    `;
    document.head.appendChild(disabledStyle);
    
    // Add animation styles
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
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>