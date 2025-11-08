<?php
require_once 'db.php';

echo "<h2>Database Connection Check:</h2>";
if ($conn) {
    echo "✓ Database connection successful<br>";
} else {
    echo "✗ Database connection failed<br>";
}

echo "<h2>Database Tables Check:</h2>";
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows > 0) {
    echo "✓ Users table exists<br>";
} else {
    echo "✗ Users table does not exist<br>";
}

echo "<h2>Admin User Check:</h2>";
$admin_check = $conn->query("SELECT * FROM users WHERE username = 'admin'");
if ($admin_check->num_rows > 0) {
    $admin = $admin_check->fetch_assoc();
    echo "✓ Admin user exists<br>";
    echo "Password hash: " . $admin['password'] . "<br>";
} else {
    echo "✗ Admin user does not exist<br>";
    
    // Create admin user if it doesn't exist
    $admin_username = "admin";
    $admin_password = "admin123";
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $admin_username, $hashed_password);
    
    if ($stmt->execute()) {
        echo "✓ Admin user created successfully<br>";
    } else {
        echo "✗ Error creating admin user: " . $conn->error . "<br>";
    }
}

// Try to verify the admin password
if ($admin_check->num_rows > 0) {
    $test_password = "admin123";
    if (password_verify($test_password, $admin['password'])) {
        echo "✓ Password verification successful<br>";
    } else {
        echo "✗ Password verification failed<br>";
    }
}
?>