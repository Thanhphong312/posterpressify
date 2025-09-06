<?php
require_once __DIR__ . '/../config/database.php';

class TimelineController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get timeline entries for an order
     */
    public function getOrderTimeline($orderId) {
        try {
            $sql = "SELECT * FROM timeline 
                    WHERE object = 'order' AND object_id = ? 
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get timeline error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add timeline entry using existing timeline table structure
     */
    public function addEntry($orderId, $action, $details = null, $status = null, $userId = null, $userName = null) {
        try {
            $currentTime = date('Y-m-d H:i:s');
            $note = $details;
            
            // Add user name to note if provided
            if ($userName && $details) {
                $note = $details;
            }
            
            $sql = "INSERT INTO timeline (object, object_id, owner_id, action, note, created_at) 
                    VALUES ('order', ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId, $userId, $action, $note, $currentTime]);
            
            return true;
        } catch (Exception $e) {
            error_log("Timeline error: " . $e->getMessage());
            // If timeline table doesn't exist, fail silently
            return false;
        }
    }
    
    /**
     * Get recent timeline entries across all orders
     */
    public function getRecentEntries($limit = 50) {
        try {
            $sql = "SELECT t.*, o.ref_id 
                    FROM timeline t
                    LEFT JOIN orders o ON t.object_id = o.id
                    WHERE t.object = 'order'
                    ORDER BY t.created_at DESC 
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get recent timeline error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log order shipped
     */
    public function logShipped($orderId, $userId = null, $userName = null) {
        // Format: "username complete order orderId"
        $note = ($userName ?: 'System') . " complete order " . $orderId;
        return $this->addEntry($orderId, 'complete order', $note, null, $userId, $userName);
    }
    
    /**
     * Log label printed with cumulative history
     */
    public function logLabelPrinted($orderId, $userId = null, $userName = null) {
        try {
            // Get existing print history
            $sql = "SELECT * FROM timeline 
                    WHERE object = 'order' 
                    AND object_id = ? 
                    AND action = 'click print order'
                    ORDER BY created_at DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            $existingPrint = $stmt->fetch();
            
            // Format: username(date)
            $currentDate = date('Y-m-d');
            $printUser = ($userName ?: 'System') . '(' . $currentDate . ')';
            
            if ($existingPrint && !empty($existingPrint['note'])) {
                // Append to existing note with pipe separator
                $note = $existingPrint['note'] . '|' . $printUser;
                
                // Update existing record
                $updateSql = "UPDATE timeline 
                             SET note = ?, 
                                 updated_at = NOW() 
                             WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$note, $existingPrint['id']]);
                return true;
            } else {
                // Create new entry
                $note = $printUser;
                return $this->addEntry($orderId, 'click print order', $note, null, $userId, $userName);
            }
        } catch (Exception $e) {
            error_log("Log print error: " . $e->getMessage());
            // Fallback to simple entry
            $note = ($userName ?: 'System') . '(' . date('Y-m-d') . ')';
            return $this->addEntry($orderId, 'click print order', $note, null, $userId, $userName);
        }
    }
    
    /**
     * Log order created
     */
    public function logCreated($orderId, $details = null) {
        return $this->addEntry($orderId, 'create order', $details ?: 'Order created', null, null, null);
    }
    
    /**
     * Log status change
     */
    public function logStatusChange($orderId, $oldStatus, $newStatus, $userId = null, $userName = null) {
        $note = "Status changed from {$oldStatus} to {$newStatus}";
        if ($userName) {
            $note .= " by " . $userName;
        }
        return $this->addEntry($orderId, 'status change', $note, null, $userId, $userName);
    }
    
    /**
     * Format timeline action for display
     */
    public function formatAction($action) {
        $actionMap = [
            'create order' => ['icon' => '📝', 'label' => 'Order Created'],
            'complete order' => ['icon' => '🚚', 'label' => 'Shipped'],
            'click print order' => ['icon' => '🖨️', 'label' => 'Print Label'],
            'print label' => ['icon' => '🏷️', 'label' => 'Label Printed'],
            'status change' => ['icon' => '🔄', 'label' => 'Status Changed'],
            'update order' => ['icon' => '✏️', 'label' => 'Order Updated'],
            'cancel order' => ['icon' => '❌', 'label' => 'Order Cancelled'],
            'refund order' => ['icon' => '💸', 'label' => 'Order Refunded'],
            'add note' => ['icon' => '📌', 'label' => 'Note Added'],
            'payment received' => ['icon' => '💰', 'label' => 'Payment Received'],
            'delivered' => ['icon' => '✅', 'label' => 'Delivered'],
            'returned' => ['icon' => '↩️', 'label' => 'Returned'],
            'processing' => ['icon' => '⚙️', 'label' => 'Processing'],
            'return_to_support' => ['icon' => '⚠️', 'label' => 'Returned to Support']
        ];
        
        return $actionMap[$action] ?? ['icon' => '📋', 'label' => ucfirst(str_replace('_', ' ', $action))];
    }
    
    /**
     * Clear timeline for an order (delete entries)
     */
    public function clearTimeline($orderId) {
        try {
            $sql = "DELETE FROM timeline WHERE object = 'order' AND object_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return true;
        } catch (Exception $e) {
            error_log("Clear timeline error: " . $e->getMessage());
            return false;
        }
    }
}