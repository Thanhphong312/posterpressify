<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($username, $password) {
        try {
            $sql = "SELECT id, username, password, first_name, last_name, role_id FROM users WHERE username = ? OR email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['display_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['role_id'] = $user['role_id'];
                
                $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$user['id']]);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit();
        }
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'display_name' => $_SESSION['display_name'],
                'role_id' => $_SESSION['role_id']
            ];
        }
        return null;
    }
}