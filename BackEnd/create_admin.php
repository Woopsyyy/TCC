<?php
require_once 'db.php';

// Admin credentials
$admin_username = "admin";
$admin_password = "admin123";

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin already exists
$check_sql = "SELECT * FROM users WHERE username = 'admin'";
$result = $conn->query($check_sql);

if ($result->num_rows === 0) {
    // Insert admin user
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $admin_username, $hashed_password);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
} else {
    echo "Admin user already exists";
}

$conn->close();
?>