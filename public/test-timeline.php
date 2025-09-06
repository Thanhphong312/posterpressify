<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/TimelineController.php';
require_once __DIR__ . '/../src/config/database.php';

$auth = new AuthController();
$auth->requireLogin();

$orderId = $_GET['order_id'] ?? 0;

if (!$orderId) {
    die('Please provide order_id parameter');
}

$timeline = new TimelineController();
$currentUser = $auth->getCurrentUser();
$userName = $currentUser['display_name'] ?? $currentUser['username'] ?? 'System';
$userId = $currentUser['id'] ?? null;

// Test adding various timeline entries
if (isset($_GET['test_add'])) {
    echo "<h3>Adding test timeline entries for Order #$orderId</h3>";
    
    // Add created event
    $timeline->logCreated($orderId, "Order created via API");
    echo "<p>✓ Added 'created' event</p>";
    
    // Add status change
    $timeline->logStatusChange($orderId, 'pending', 'processing', $userId, $userName);
    echo "<p>✓ Added 'status_changed' event</p>";
    
    // Add label printed
    $timeline->logLabelPrinted($orderId, $userId, $userName);
    echo "<p>✓ Added 'label_printed' event</p>";
    
    // Add shipped
    $timeline->logShipped($orderId, $userId, $userName);
    echo "<p>✓ Added 'shipped' event</p>";
    
    echo "<hr>";
}

// Get and display timeline
$events = $timeline->getOrderTimeline($orderId);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Timeline Test - Order #<?php echo $orderId; ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; }
        .timeline-event { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .event-action { font-weight: bold; color: #0066ff; }
        .event-time { color: #666; font-size: 0.9em; }
        .event-details { margin-top: 5px; color: #333; }
        h2 { color: #2c3e50; }
        .add-test-link { background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        .back-link { color: #0066ff; text-decoration: none; margin: 10px 0; display: inline-block; }
    </style>
</head>
<body>
    <h2>Timeline for Order #<?php echo htmlspecialchars($orderId); ?></h2>
    
    <a href="?order_id=<?php echo $orderId; ?>&test_add=1" class="add-test-link">Add Test Timeline Events</a>
    <a href="?order_id=<?php echo $orderId; ?>&clear=1" class="add-test-link" style="background: #dc3545;">Clear Timeline</a>
    <a href="/order-details.php?id=<?php echo $orderId; ?>" class="back-link">← View Full Order Details</a>
    
    <?php if (empty($events)): ?>
        <p>No timeline events found for this order.</p>
    <?php else: ?>
        <h3>Timeline Events (<?php echo count($events); ?> total)</h3>
        <?php foreach ($events as $event): 
            $actionInfo = $timeline->formatAction($event['action']);
        ?>
            <div class="timeline-event">
                <div>
                    <span class="event-action"><?php echo $actionInfo['icon']; ?> <?php echo $actionInfo['label']; ?></span>
                    <span class="event-time"><?php echo date('Y-m-d H:i:s', strtotime($event['created_at'])); ?></span>
                </div>
                <?php if (!empty($event['note'])): ?>
                    <div class="event-details">
                        <?php echo htmlspecialchars($event['note']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($event['owner_id'])): ?>
                    <div class="event-details">
                        User ID: <?php echo htmlspecialchars($event['owner_id']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <hr>
    <h3>Timeline Storage Info</h3>
    <?php
    // Check if timeline table exists
    $db = Database::getInstance();
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'timeline'");
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ Timeline table exists</p>";
            
            // Count timeline entries for this order
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM timeline WHERE object = 'order' AND object_id = ?");
            $stmt->execute([$orderId]);
            $count = $stmt->fetch();
            echo "<p>Timeline entries for this order: " . $count['count'] . "</p>";
            
            // Show last 5 entries
            $stmt = $db->prepare("SELECT * FROM timeline WHERE object = 'order' AND object_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$orderId]);
            $recent = $stmt->fetchAll();
            
            if ($recent) {
                echo "<h4>Recent Timeline Entries:</h4>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
                echo "<tr><th>Action</th><th>Note</th><th>Owner ID</th><th>Created At</th></tr>";
                foreach ($recent as $entry) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($entry['action']) . "</td>";
                    echo "<td>" . htmlspecialchars(substr($entry['note'] ?? '', 0, 50)) . "</td>";
                    echo "<td>" . htmlspecialchars($entry['owner_id'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($entry['created_at']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Timeline table does not exist - timeline features will not work</p>";
            echo "<p>Table structure needed:</p>";
            echo "<pre>CREATE TABLE timeline (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    object VARCHAR(50),
    object_id BIGINT,
    owner_id BIGINT,
    action VARCHAR(100),
    note TEXT,
    created_at TIMESTAMP
);</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking timeline table: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Clear timeline option
    if (isset($_GET['clear'])) {
        $timeline->clearTimeline($orderId);
        echo "<p style='color: orange;'>Timeline cleared for order #$orderId</p>";
        echo "<script>setTimeout(() => window.location.href='?order_id=$orderId', 1000);</script>";
    }
    ?>
</body>
</html>