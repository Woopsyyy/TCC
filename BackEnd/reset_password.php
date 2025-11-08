<?php
require_once __DIR__ . '/database/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Password Reset Debug</h2>";

$conn = Database::getInstance()->getConnection();

// Create new password hash
$password = "admin123";
$new_hash = password_hash($password, PASSWORD_DEFAULT);

echo "New password hash: " . $new_hash . "<br>";

// Update admin password
$sql = "UPDATE users SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $new_hash);

if ($stmt->execute()) {
    echo "<div style='color:green'>✓ Password updated successfully!</div>";
    echo "<br>You can now login with:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    
    // Verify the update
    $check = $conn->query("SELECT password FROM users WHERE username = 'admin'");
    if ($row = $check->fetch_assoc()) {
        echo "<br>Stored hash in database: " . $row['password'] . "<br>";
        if (password_verify('admin123', $row['password'])) {
            echo "<div style='color:green'>✓ Password verification test successful!</div>";
        } else {
            echo "<div style='color:red'>✗ Password verification test failed!</div>";
        }
    }
} else {
    echo "<div style='color:red'>✗ Error updating password: " . $conn->error . "</div>";
}

echo "<br><a href='/TCC/public/index.html'>Back to login</a>";
?>