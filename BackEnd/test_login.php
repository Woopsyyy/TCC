<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Login Debug Information</h2>";

// Verify database and table exist first
require_once __DIR__ . '/database/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if we can connect to the accountmanager database
    $check_db = $conn->query("SELECT DATABASE()");
    $db_name = $check_db->fetch_row()[0];
    echo "Connected to database: " . $db_name . "<br>";
    
    // Check if users table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_table->num_rows == 0) {
        die("Users table not found! Please run <a href='/TCC/BackEnd/setup_database.php'>database setup</a> first.");
    }
    echo "Users table exists<br><br>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    echo "<h2>Login Debug Information</h2>";
    echo "Attempting login with:<br>";
    echo "Username: " . htmlspecialchars($username) . "<br>";
    echo "Password: " . str_repeat("*", strlen($password)) . "<br>";

    $conn = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        echo "Found user in database:<br>";
        echo "Username: " . htmlspecialchars($user['username']) . "<br>";
        echo "Role: " . htmlspecialchars($user['role']) . "<br>";
        echo "Stored password hash: " . $user['password'] . "<br><br>";
        
        echo "Verifying password...<br>";
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            echo "<div style='color:green'>✓ Password verified successfully!<br>";
            echo "✓ Login successful! Role: " . htmlspecialchars($user['role']) . "</div>";
            echo "<meta http-equiv='refresh' content='5;url=/TCC/public/home.php'>";
        } else {
            echo "<div style='color:red'>✗ Password verification failed!<br>";
            echo "Entered password hash: " . password_hash($password, PASSWORD_DEFAULT) . "</div>";
        }
    } else {
        echo "<div style='color:red'>✗ User not found</div>";
    }

    echo "<br><a href='/TCC/public/index.html'>Back to login</a>";
}
?>