<?php
require_once __DIR__ . '/../config/database.php';

class PermissionController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if user is admin
     * Assuming role_id 1 = admin, 2 = regular user
     * Adjust based on your actual role system
     */
    public function isAdmin($userId) {
        try {
            $sql = "SELECT role_id FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            // Adjust these role IDs based on your actual roles table
            // You might want to check role name instead
            return $user && in_array($user['role_id'], [1, 99]); // 1 or 99 for admin
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if user can access all orders or just their own
     */
    public function canViewAllOrders($userId) {
        // For now, let all users see all orders
        // You can implement more complex logic here
        return true;
        
        // Or use role-based check:
        // return $this->isAdmin($userId);
    }
    
    /**
     * Get seller filter for queries
     * Returns null if user can see all orders, or user's ID if restricted
     */
    public function getSellerFilter($userId) {
        if ($this->canViewAllOrders($userId)) {
            return null; // No filter, can see all
        }
        return $userId; // Only see own orders
    }
}