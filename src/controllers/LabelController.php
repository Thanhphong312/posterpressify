<?php
require_once __DIR__ . '/../config/database.php';

class LabelController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get label URL for an order
     */
    public function getLabelUrl($orderId) {
        try {
            $sql = "SELECT shipping_label, convert_label, tracking_id FROM orders WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get label error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Convert label format if needed
     */
    public function convertLabel($orderId) {
        try {
            $order = $this->getLabelUrl($orderId);
            
            if (!$order || empty($order['shipping_label'])) {
                return ['success' => false, 'message' => 'No label found for this order'];
            }
            
            // Check if already converted
            if ($order['convert_label'] == 1) {
                return ['success' => true, 'url' => $order['shipping_label'], 'already_converted' => true];
            }
            
            // Here you would implement actual label conversion logic
            // For now, we'll just mark it as converted and return the original URL
            $labelUrl = $order['shipping_label'];
            
            // Update convert_label status
            $updateSql = "UPDATE orders SET convert_label = 1, updated_at_convert_label = NOW() WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([$orderId]);
            
            return [
                'success' => true, 
                'url' => $labelUrl,
                'tracking_id' => $order['tracking_id']
            ];
            
        } catch (Exception $e) {
            error_log("Convert label error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error converting label'];
        }
    }
    
    /**
     * Process label for printing (convert if needed, prepare for print)
     */
    public function prepareLabelForPrint($orderId) {
        try {
            // First convert the label if needed
            $conversion = $this->convertLabel($orderId);
            
            if (!$conversion['success']) {
                return $conversion;
            }
            
            // Prepare print-friendly URL
            $labelUrl = $conversion['url'];
            
            // If it's an external URL, use proxy
            if (filter_var($labelUrl, FILTER_VALIDATE_URL)) {
                $printUrl = '/proxy-label.php?url=' . urlencode($labelUrl);
            } else {
                // Local file or base64 data
                $printUrl = $labelUrl;
            }
            
            return [
                'success' => true,
                'print_url' => $printUrl,
                'original_url' => $labelUrl,
                'tracking_id' => $conversion['tracking_id'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log("Prepare print error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error preparing label for print'];
        }
    }
    
    /**
     * Get all unconverted labels
     */
    public function getUnconvertedLabels($limit = 100) {
        try {
            $sql = "SELECT id, ref_id, shipping_label, tracking_id, created_at 
                    FROM orders 
                    WHERE shipping_label IS NOT NULL 
                    AND shipping_label != ''
                    AND (convert_label = 0 OR convert_label IS NULL)
                    ORDER BY created_at DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get unconverted labels error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Batch convert labels
     */
    public function batchConvertLabels($orderIds) {
        $results = [];
        foreach ($orderIds as $orderId) {
            $results[$orderId] = $this->convertLabel($orderId);
        }
        return $results;
    }
}