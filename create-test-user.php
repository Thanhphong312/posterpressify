<?php
require_once __DIR__ . '/src/config/database.php';

echo "POD Order Manager - Create Test User\n";
echo "====================================\n\n";

// Get user input
echo "Enter username: ";
$username = trim(fgets(STDIN));

echo "Enter email: ";
$email = trim(fgets(STDIN));

echo "Enter password: ";
system('stty -echo');
$password = trim(fgets(STDIN));
system('stty echo');
echo "\n";

echo "Enter first name: ";
$firstName = trim(fgets(STDIN));

echo "Enter last name: ";
$lastName = trim(fgets(STDIN));

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Check if user already exists
    $checkStmt = $connection->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->execute([$username, $email]);
    
    if ($checkStmt->fetch()) {
        echo "\n✗ Error: User with this username or email already exists!\n";
        exit(1);
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user (assuming role_id 1 is for regular users, 2 for admin)
    $insertStmt = $connection->prepare("
        INSERT INTO users (username, email, password, first_name, last_name, role_id, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
    ");
    
    $insertStmt->execute([
        $username,
        $email,
        $passwordHash,
        $firstName,
        $lastName,
        1 // Default role_id
    ]);
    
    $userId = $connection->lastInsertId();
    
    echo "\n✓ User created successfully!\n";
    echo "====================================\n";
    echo "User ID: " . $userId . "\n";
    echo "Username: " . $username . "\n";
    echo "Email: " . $email . "\n";
    echo "Name: " . $firstName . " " . $lastName . "\n";
    echo "====================================\n\n";
    
    echo "You can now login at:\n";
    echo "http://localhost/posterpressify/public/login.php\n\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error creating user!\n";
    echo "Database error: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'role_id') !== false) {
        echo "Note: The 'roles' table might be missing or empty.\n";
        echo "Please ensure the database schema is properly set up.\n";
    }
} catch (Exception $e) {
    echo "\n✗ Unexpected error!\n";
    echo "Error: " . $e->getMessage() . "\n";
}