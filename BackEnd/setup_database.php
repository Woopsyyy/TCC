<?php
require_once __DIR__ . '/database/db.php';

echo "<h2>Database Setup</h2>";

try {
    // Get initial connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Step 1: Checking current database...<br>";
    $result = $conn->query("SELECT DATABASE()");
    $currentDb = $result->fetch_row()[0];
    echo "Currently connected to: " . ($currentDb ?? "none") . "<br>";
    
    echo "Step 2: Creating/Resetting database...<br>";
    // Drop and recreate database
    $conn->query("DROP DATABASE IF EXISTS accountmanager");
    if ($conn->query("CREATE DATABASE accountmanager")) {
        echo "✓ Database created successfully<br>";
    } else {
        throw new Exception("Failed to create database: " . $conn->error);
    }
    
    echo "Step 3: Selecting database...<br>";
    if ($conn->select_db("accountmanager")) {
        echo "✓ Database selected successfully<br>";
    } else {
        throw new Exception("Failed to select database: " . $conn->error);
    }

echo "Step 3: Creating users table...<br>";
// Create the users table
$sql = "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    image_path VARCHAR(255) DEFAULT 'uploads/default.jpg'
) ENGINE=InnoDB;";

if ($conn->query($sql)) {
    echo "✓ Table created successfully<br>";
} else {
    die("Error creating table: " . $conn->error);
}

echo "Step 4: Creating admin user...<br>";
// Insert admin user with hashed password
$username = 'admin';
$password = 'admin123';
$role = 'admin';
$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $hash, $role);

if ($stmt->execute()) {
    echo "✓ Admin user created successfully<br>";
    echo "<br>You can now login with:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    
    echo "<br>Step 5: Verifying setup...<br>";
    // Verify the password hash
    $check = $conn->query("SELECT password FROM users WHERE username = 'admin'");
    if ($row = $check->fetch_assoc()) {
        echo "Stored hash: " . $row['password'] . "<br>";
        if (password_verify('admin123', $row['password'])) {
            echo "<div style='color:green'>✓ Password verification test successful!</div>";
        } else {
            echo "<div style='color:red'>✗ Password verification test failed!</div>";
        }
    }
} else {
    die("Error creating admin user: " . $stmt->error);
}

echo "<br><a href='/TCC/public/index.html'>Back to login</a>";
?>