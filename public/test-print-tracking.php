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

// Test adding print event
if (isset($_GET['test_print'])) {
    $timeline->logLabelPrinted($orderId, $userId, $userName);
    header("Location: test-print-tracking.php?order_id=$orderId");
    exit;
}

// Get timeline
$events = $timeline->getOrderTimeline($orderId);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Print Tracking</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px;
            max-width: 1000px;
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
        .print-event {
            border-left-color: #17a2b8;
            background: #e7f5ff;
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
        .print-history {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .print-entry {
            background: #0066ff;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
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
        .btn-print {
            background: #17a2b8;
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
        .stats-box {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            display: flex;
            gap: 30px;
        }
        .stat-item {
            flex: 1;
        }
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖨️ Test Print Tracking (Cumulative)</h1>
        
        <div class="info-box">
            <strong>Current User:</strong> <?php echo htmlspecialchars($userName); ?> (ID: <?php echo $userId; ?>)<br>
            <strong>Order ID:</strong> <?php echo htmlspecialchars($orderId); ?><br>
            <strong>Current Date:</strong> <?php echo date('Y-m-d'); ?>
        </div>
        
        <div class="format-example">
            <strong>Cumulative Print Format:</strong><br>
            First print: <code>username(date)</code><br>
            Second print: <code>username(date)|username(date)</code><br>
            Example: <code>Thanh Phong(2025-09-06)|Admin(2025-09-07)|Thanh Phong(2025-09-07)</code>
        </div>
        
        <?php
        // Calculate print statistics
        $printCount = 0;
        $printHistory = [];
        foreach ($events as $event) {
            if ($event['action'] === 'click print order' && !empty($event['note'])) {
                $prints = explode('|', $event['note']);
                $printCount = count($prints);
                $printHistory = $prints;
                break;
            }
        }
        ?>
        
        <div class="stats-box">
            <div class="stat-item">
                <div class="stat-label">Total Prints</div>
                <div class="stat-value"><?php echo $printCount; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Last Print</div>
                <div class="stat-value"><?php echo $printCount > 0 ? htmlspecialchars(end($printHistory)) : 'Never'; ?></div>
            </div>
        </div>
        
        <div style="margin: 20px 0;">
            <a href="?order_id=<?php echo $orderId; ?>&test_print=1" class="btn btn-print">
                🖨️ Simulate Print Label
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
                $isPrintEvent = $event['action'] === 'click print order';
            ?>
                <div class="timeline-event <?php echo $isPrintEvent ? 'print-event' : ''; ?>">
                    <div class="event-header">
                        <span class="event-label">
                            <?php echo $actionInfo['icon']; ?> <?php echo $actionInfo['label']; ?>
                        </span>
                        <span class="event-date">
                            <?php echo date('Y-m-d H:i:s', strtotime($event['created_at'])); ?>
                        </span>
                    </div>
                    <?php if (!empty($event['note'])): ?>
                        <?php if ($isPrintEvent): ?>
                            <div class="event-note">
                                <strong>Print History:</strong> <?php echo htmlspecialchars($event['note']); ?>
                            </div>
                            <div class="print-history">
                                <?php 
                                $prints = explode('|', $event['note']);
                                foreach ($prints as $index => $print): 
                                ?>
                                    <div class="print-entry">
                                        Print #<?php echo ($index + 1); ?>: <?php echo htmlspecialchars($print); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="event-note">
                                <?php echo htmlspecialchars($event['note']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div style="margin-top: 8px; font-size: 12px; color: #6c757d;">
                        Action: <code><?php echo htmlspecialchars($event['action']); ?></code>
                        <?php if ($event['updated_at'] && $event['updated_at'] !== $event['created_at']): ?>
                            | Last Updated: <?php echo date('Y-m-d H:i:s', strtotime($event['updated_at'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h3>How It Works</h3>
        <ol>
            <li>When a label is printed for the first time, a new timeline entry is created with action "click print order"</li>
            <li>The note contains the username and date: <code>username(date)</code></li>
            <li>For subsequent prints, the existing entry is updated by appending the new print with a pipe separator</li>
            <li>This creates a complete print history: <code>user1(date1)|user2(date2)|user3(date3)</code></li>
            <li>The timeline entry shows when it was created and last updated</li>
        </ol>
    </div>
</body>
</html>