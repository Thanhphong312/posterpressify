<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/TimelineController.php';

$auth = new AuthController();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$userName = $currentUser['display_name'] ?? $currentUser['username'] ?? 'Admin';
$userId = $currentUser['id'] ?? 1;

$orderId = $_GET['order_id'] ?? 2038099;

$timeline = new TimelineController();

// Test adding shipping event
if (isset($_GET['test'])) {
    $timeline->logShipped($orderId, $userId, $userName);
    header("Location: test-timeline-format.php?order_id=$orderId");
    exit;
}

// Get timeline
$events = $timeline->getOrderTimeline($orderId);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Timeline Format</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .timeline-event {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #0066ff;
        }
        .event-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .event-label {
            font-weight: 600;
            color: #2c3e50;
        }
        .event-date {
            color: #6c757d;
            font-size: 13px;
        }
        .event-note {
            color: #495057;
            font-size: 14px;
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 8px;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #0066ff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
        }
        .btn-success {
            background: #28a745;
        }
        .format-example {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        code {
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Timeline Format</h1>
        
        <div class="info-box">
            <strong>Current User:</strong> <?php echo htmlspecialchars($userName); ?> (ID: <?php echo $userId; ?>)<br>
            <strong>Order ID:</strong> <?php echo htmlspecialchars($orderId); ?>
        </div>
        
        <div class="format-example">
            <strong>Expected Format:</strong><br>
            When shipping: <code>username + "complete order" + orderId</code><br>
            Example: <code>Thanh Phong complete order 2038099</code>
        </div>
        
        <div style="margin: 20px 0;">
            <a href="?order_id=<?php echo $orderId; ?>&test=1" class="btn btn-success">
                Test Ship Order (Add to Timeline)
            </a>
            <a href="/order-details.php?id=<?php echo $orderId; ?>" class="btn">
                View Order Details
            </a>
        </div>
        
        <h2>Timeline Events</h2>
        
        <?php if (empty($events)): ?>
            <p>No timeline events found for order #<?php echo $orderId; ?></p>
        <?php else: ?>
            <?php foreach ($events as $event): 
                $actionInfo = $timeline->formatAction($event['action']);
            ?>
                <div class="timeline-event">
                    <div class="event-header">
                        <span class="event-label">
                            <?php echo $actionInfo['icon']; ?> <?php echo $actionInfo['label']; ?>
                        </span>
                        <span class="event-date">
                            <?php echo date('Y-m-d H:i:s', strtotime($event['created_at'])); ?>
                        </span>
                    </div>
                    <?php if (!empty($event['note'])): ?>
                        <div class="event-note">
                            <?php echo htmlspecialchars($event['note']); ?>
                        </div>
                    <?php endif; ?>
                    <div style="margin-top: 8px; font-size: 12px; color: #6c757d;">
                        Action: <code><?php echo htmlspecialchars($event['action']); ?></code>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h3>Raw Timeline Data</h3>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-size: 12px; overflow-x: auto;">
<?php 
if (!empty($events)) {
    foreach ($events as $event) {
        echo "Action: " . $event['action'] . "\n";
        echo "Note: " . ($event['note'] ?? 'N/A') . "\n";
        echo "Created: " . $event['created_at'] . "\n";
        echo "---\n";
    }
} else {
    echo "No events";
}
?>
        </pre>
    </div>
</body>
</html>