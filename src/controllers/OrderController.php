<?php
require_once __DIR__ . '/../config/database.php';

class OrderController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
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
                
                $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT 50";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();
            
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
                    COUNT(oi.id) as item_count
                    FROM orders o
                    LEFT JOIN order_items oi ON o.id = oi.order_id";
            
            $params = [];
            if ($userId) {
                $sql .= " WHERE o.seller_id = ?";
                $params[] = $userId;
            }
            
            $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
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
            'cancelled' => ['label' => 'Cancelled', 'class' => 'status-cancelled']
        ];
        
        return $statusMap[$status] ?? ['label' => ucfirst($status), 'class' => 'status-default'];
    }
}