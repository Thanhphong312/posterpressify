<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/config/env.php';

$auth = new AuthController();
$auth->requireLogin();

$enableTestOrder = filter_var(Env::get('ENABLE_TEST_ORDER', 'false'), FILTER_VALIDATE_BOOLEAN);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Auto-Print Feature</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 { color: #2c3e50; }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
        }
        button {
            background: #0066ff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        button:hover {
            background: #0052cc;
        }
        button:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }
        .log {
            background: #f1f3f5;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 13px;
            max-height: 200px;
            overflow-y: auto;
        }
        .log-entry {
            padding: 3px 0;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <h1>🧪 Test Auto-Print Feature</h1>
    
    <div class="info-box">
        <h3>Current Configuration</h3>
        <p>
            <strong>ENABLE_TEST_ORDER:</strong> 
            <span class="status <?php echo $enableTestOrder ? 'status-enabled' : 'status-disabled'; ?>">
                <?php echo $enableTestOrder ? 'ENABLED' : 'DISABLED'; ?>
            </span>
        </p>
        <p>
            <strong>Auto-Print Status:</strong>
            <span class="status status-enabled">ENABLED</span>
            (Prints automatically after shipping)
        </p>
    </div>
    
    <div class="test-section">
        <h3>Test Auto-Print Flow</h3>
        <p>Enter an order ID with a shipping label to test:</p>
        
        <input type="text" id="orderId" placeholder="Order ID (e.g., 2038099)" style="padding: 8px; width: 200px;">
        
        <br><br>
        
        <button onclick="testShipOnly()">Ship Only (No Print)</button>
        <button onclick="testShipAndPrint()">Ship + Auto Print</button>
        <button onclick="testPrintOnly()">Print Only</button>
        
        <div id="log" class="log" style="display: none;"></div>
    </div>
    
    <div class="info-box">
        <h3>How Auto-Print Works</h3>
        <ul>
            <li>When you click "Ship" on an order, it automatically marks as shipped</li>
            <li>If the order has a shipping label, it will automatically print after shipping</li>
            <li>The print happens via hidden iframe (no popup window)</li>
            <li>Timeline is updated with both "shipped" and "label_printed" events</li>
        </ul>
    </div>
    
    <script src="/assets/js/print-label-advanced.js"></script>
    <script>
    // Configure label printer
    const labelPrinter = new LabelPrinter({
        mode: 'direct',
        autoprint: true
    });
    
    // Create global printLabel function
    window.printLabel = function(orderId) {
        addLog('📄 Printing label for order #' + orderId);
        labelPrinter.print(orderId);
    };
    
    function addLog(message) {
        const log = document.getElementById('log');
        log.style.display = 'block';
        const entry = document.createElement('div');
        entry.className = 'log-entry';
        entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
        log.insertBefore(entry, log.firstChild);
    }
    
    function testShipOnly() {
        const orderId = document.getElementById('orderId').value;
        if (!orderId) {
            alert('Please enter an order ID');
            return;
        }
        
        addLog('🚚 Shipping order #' + orderId + ' (without auto-print)...');
        
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
                addLog('✅ Order shipped successfully');
                addLog('ℹ️ Has label: ' + (data.has_label ? 'Yes' : 'No'));
                if (data.already_shipped) {
                    addLog('⚠️ Order was already shipped');
                }
            } else {
                addLog('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            addLog('❌ Network error: ' + error);
        });
    }
    
    function testShipAndPrint() {
        const orderId = document.getElementById('orderId').value;
        if (!orderId) {
            alert('Please enter an order ID');
            return;
        }
        
        addLog('🚚 Shipping order #' + orderId + ' with auto-print...');
        
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
                addLog('✅ Order shipped successfully');
                
                if (data.has_label) {
                    addLog('🖨️ Auto-printing label...');
                    setTimeout(() => {
                        printLabel(orderId);
                        addLog('✅ Label print command sent');
                    }, 500);
                } else {
                    addLog('⚠️ No label available for printing');
                }
                
                if (data.already_shipped) {
                    addLog('ℹ️ Order was already shipped');
                }
            } else {
                addLog('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            addLog('❌ Network error: ' + error);
        });
    }
    
    function testPrintOnly() {
        const orderId = document.getElementById('orderId').value;
        if (!orderId) {
            alert('Please enter an order ID');
            return;
        }
        
        addLog('🖨️ Printing label for order #' + orderId + '...');
        printLabel(orderId);
        addLog('✅ Label print command sent');
    }
    </script>
</body>
</html>