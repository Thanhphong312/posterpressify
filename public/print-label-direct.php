<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/config/database.php';

$auth = new AuthController();
$auth->requireLogin();

$orderId = $_GET['order'] ?? 0;

if (!$orderId) {
    die('Error: No order specified');
}

// Get label URL directly from database
$db = Database::getInstance();
$stmt = $db->prepare("SELECT shipping_label, ref_id, tracking_id FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || empty($order['shipping_label'])) {
    die('Error: No label found for this order');
}

$labelUrl = $order['shipping_label'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label - Order #<?php echo $orderId; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        
        body {
            background: white;
            font-family: Arial, sans-serif;
        }
        
        /* Hide everything except image when printing */
        @media print {
            body * {
                visibility: hidden;
            }
            #labelImage, #labelImage * {
                visibility: visible;
            }
            #labelImage {
                position: absolute;
                left: 0;
                top: 0;
                max-width: 100%;
                height: auto;
            }
        }
        
        /* Screen display */
        @media screen {
            .container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            #labelImage {
                max-width: 100%;
                height: auto;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img id="labelImage" 
             src="<?php echo htmlspecialchars($labelUrl); ?>" 
             alt="Shipping Label"
             onload="autoPrint()"
             onerror="handleError()">
    </div>
    
    <script>
    function autoPrint() {
        // Automatically open print dialog when image loads
        setTimeout(() => {
            window.print();
        }, 100);
    }
    
    function handleError() {
        alert('Error loading label image');
    }
    
    // Listen for Ctrl+P / Cmd+P
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });
    </script>
</body>
</html>