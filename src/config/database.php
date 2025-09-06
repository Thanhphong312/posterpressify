<?php
require_once __DIR__ . '/env.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private $host;
    private $port;
    private $username;
    private $password;
    private $database;
    
    private function __construct() {
        // Load environment variables
        Env::load();
        
        // Get database configuration from .env file
        $this->host = Env::get('DB_HOST', 'localhost');
        $this->port = Env::get('DB_PORT', 3306);
        $this->database = Env::get('DB_DATABASE', 'pressify');
        $this->username = Env::get('DB_USERNAME', 'root');
        $this->password = Env::get('DB_PASSWORD', '');
        
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Check if in debug mode
            if (Env::get('APP_DEBUG', false)) {
                die("Connection failed: " . $e->getMessage());
            } else {
                die("Database connection error. Please contact administrator.");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}