<?php
require_once __DIR__ . '/src/config/database.php';

echo "POD Order Manager - Database Connection Test\n";
echo "=============================================\n\n";

try {
    echo "Attempting to connect to database...\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "✓ Successfully connected to MySQL database!\n\n";
    
    echo "Connection Details:\n";
    echo "- Host: 45.79.0.186\n";
    echo "- User: duytan\n";
    echo "- Database: posterpressify\n\n";
    
    echo "Testing database queries...\n\n";
    
    // Test users table
    echo "1. Checking 'users' table:\n";
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "   - Users table exists with " . $result['count'] . " records\n";
    
    // Test orders table  
    echo "\n2. Checking 'orders' table:\n";
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM orders");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "   - Orders table exists with " . $result['count'] . " records\n";
    
    // Test order_items table
    echo "\n3. Checking 'order_items' table:\n";
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM order_items");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "   - Order items table exists with " . $result['count'] . " records\n";
    
    // Test products table
    echo "\n4. Checking 'products' table:\n";
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "   - Products table exists with " . $result['count'] . " records\n";
    
    // Test product_variants table
    echo "\n5. Checking 'product_variants' table:\n";
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM product_variants");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "   - Product variants table exists with " . $result['count'] . " records\n";
    
    echo "\n=============================================\n";
    echo "✓ All database tests passed successfully!\n";
    echo "=============================================\n\n";
    
    echo "Next Steps:\n";
    echo "1. Create a test user account:\n";
    echo "   - Run: php create-test-user.php\n";
    echo "2. Access the application:\n";
    echo "   - Navigate to: http://localhost/posterpressify/public/login.php\n";
    echo "3. Login with your test credentials\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting:\n";
    echo "1. Verify database credentials in src/config/database.php\n";
    echo "2. Ensure MySQL server at 45.79.0.186 is accessible\n";
    echo "3. Check if database 'posterpressify' exists\n";
    echo "4. Verify network connectivity to remote host\n";
    echo "5. Check firewall settings\n";
    
} catch (Exception $e) {
    echo "✗ Unexpected error occurred!\n";
    echo "Error: " . $e->getMessage() . "\n";
}