<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

class OrderController {
    private $db;
    private $enableTestOrder;
    
    public function __construct() {
        $this->db = Database::getInstance();
        // Check if test orders are enabled
        $this->enableTestOrder = filter_var(Env::get('ENABLE_TEST_ORDER', 'false'), FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Check if an order is a test order
     */
    public function isTestOrder($order) {
        // Check if ref_id or order_stt contains 'test'
        return (
            (isset($order['ref_id']) && stripos($order['ref_id'], 'test') !== false) ||
            (isset($order['order_stt']) && stripos($order['order_stt'], 'test') !== false)
        );
    }
    
    /**
     * Check if test orders are enabled
     */
    public function isTestOrderEnabled() {
        return $this->enableTestOrder;
    }
    
    public function searchOrders($searchTerm, $userId = null) {
        try {
            $orders = [];
            
            if (is_numeric($searchTerm)) {
                $sql = "SELECT o.*, 
                        CONCAT(o.first_name, ' ', o.last_name) as customer_name,
                        COUNT(oi.id) as item_count
                        FROM orders o
                        LEFT JOIN order_items oi ON o.id = oi.order_id
                        WHERE o.id = ?";
                $params = [$searchTerm];
                
                if ($userId) {
                    $sql .= " AND o.seller_id = ?";
                    $params[] = $userId;
                }
                
                // Filter out test orders if disabled
                if (!$this->enableTestOrder) {
                    $sql .= " AND (o.ref_id NOT LIKE '%test%' OR o.ref_id IS NULL)";
                    $sql .= " AND (o.order_stt NOT LIKE '%test%' OR o.order_stt IS NULL)";
                }
                
                $sql .= " GROUP BY o.id";
            } else {
                $sql = "SELECT o.*, 
                        CONCAT(o.first_name, ' ', o.last_name) as customer_name,
                        COUNT(oi.id) as item_count
                        FROM orders o
                        LEFT JOIN order_items oi ON o.id = oi.order_id
                        WHERE o.ref_id LIKE ?";
                $params = ['%' . $searchTerm . '%'];
                
                if ($userId) {
                    $sql .= " AND o.seller_id = ?";
                    $params[] = $userId;
                }
                
                // Filter out test orders if disabled
                if (!$this->enableTestOrder) {
                    $sql .= " AND (o.ref_id NOT LIKE '%test%' OR o.ref_id IS NULL)";
                    $sql .= " AND (o.order_stt NOT LIKE '%test%' OR o.order_stt IS NULL)";
                }
                
                $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT 50";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();
            
            // Get items for each order
            foreach ($orders as &$order) {
                $itemSql = "SELECT oi.*, 
                           pv.style, pv.color, pv.size
                           FROM order_items oi
                           LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
                           WHERE oi.order_id = ?";
                $itemStmt = $this->db->prepare($itemSql);
                $itemStmt->execute([$order['id']]);
                $order['items'] = $itemStmt->fetchAll();
            }
            
            return $orders;
        } catch (Exception $e) {
            error_log("Order search error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getOrderDetails($orderId, $userId = null) {
        try {
            $sql = "SELECT o.*, 
                    CONCAT(o.first_name, ' ', o.last_name) as customer_name
                    FROM orders o
                    WHERE o.id = ?";
            $params = [$orderId];
            
            if ($userId) {
                $sql .= " AND o.seller_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $order = $stmt->fetch();
            
            if ($order) {
                $itemSql = "SELECT oi.*, 
                           pv.style, pv.color, pv.size
                           FROM order_items oi
                           LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
                           WHERE oi.order_id = ?";
                $itemStmt = $this->db->prepare($itemSql);
                $itemStmt->execute([$orderId]);
                $order['items'] = $itemStmt->fetchAll();
            }
            
            return $order;
        } catch (Exception $e) {
            error_log("Order details error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getRecentOrders($userId = null, $limit = 20) {
        try {
            $sql = "SELECT o.*, 
                    CONCAT(o.first_name, ' ', o.last_name) as customer_name,
                    COUNT(oi.id) as item_count,
                    GROUP_CONCAT(DISTINCT oi.product_name SEPARATOR ', ') as product_names,
                    GROUP_CONCAT(DISTINCT oi.sku SEPARATOR ', ') as product_skus
                    FROM orders o
                    LEFT JOIN order_items oi ON o.id = oi.order_id";
            
            $params = [];
            $whereConditions = [];
            
            if ($userId) {
                $whereConditions[] = "o.seller_id = ?";
                $params[] = $userId;
            }
            
            // Filter out test orders if disabled
            if (!$this->enableTestOrder) {
                $whereConditions[] = "(o.ref_id NOT LIKE '%test%' OR o.ref_id IS NULL)";
                $whereConditions[] = "(o.order_stt NOT LIKE '%test%' OR o.order_stt IS NULL)";
            }
            
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(' AND ', $whereConditions);
            }
            
            $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $orders = $stmt->fetchAll();
            
            // Get items for each order
            foreach ($orders as &$order) {
                $itemSql = "SELECT oi.*, 
                           pv.style, pv.color, pv.size
                           FROM order_items oi
                           LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
                           WHERE oi.order_id = ?";
                $itemStmt = $this->db->prepare($itemSql);
                $itemStmt->execute([$order['id']]);
                $order['items'] = $itemStmt->fetchAll();
            }
            
            return $orders;
        } catch (Exception $e) {
            error_log("Recent orders error: " . $e->getMessage());
            return [];
        }
    }
    
    public function formatOrderStatus($status) {
        $statusMap = [
            'pending' => ['label' => 'Pending', 'class' => 'status-pending'],
            'processing' => ['label' => 'Processing', 'class' => 'status-processing'],
            'fulfilled' => ['label' => 'Fulfilled', 'class' => 'status-fulfilled'],
            'shipped' => ['label' => 'Shipped', 'class' => 'status-shipped'],
            'delivered' => ['label' => 'Delivered', 'class' => 'status-delivered'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'status-cancelled'],
            'return_to_support' => ['label' => 'Return to Support', 'class' => 'status-return-support']
        ];
        
        return $statusMap[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'status-default'];
    }
}