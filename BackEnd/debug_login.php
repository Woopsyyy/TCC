<?php
session_start();
require_once 'db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Information:</h2>";

// Check if POST data exists
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    echo "Attempting login with:<br>";
    echo "Username: " . htmlspecialchars($username) . "<br>";
    echo "Password: " . str_repeat("*", strlen($password)) . "<br><br>";

    // Check if user exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "Database query results:<br>";
    if ($result->num_rows === 1) {
        echo "✓ User found in database<br>";
        $user = $result->fetch_assoc();
        
        echo "Stored password hash: " . $user['password'] . "<br>";
        
        // Try password verification
        if (password_verify($password, $user['password'])) {
            echo "✓ Password verified successfully<br>";
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo "<br>Login successful! You should be redirected...";
            echo "<meta http-equiv='refresh' content='5;url=../public/home.php'>";
        } else {
            echo "✗ Password verification failed<br>";
            // Create a new hash for comparison
            echo "Test hash of entered password: " . password_hash($password, PASSWORD_DEFAULT) . "<br>";
        }
    } else {
        echo "✗ User not found in database<br>";
    }

    // Show current database contents
    echo "<br><h3>Current Database Contents:</h3>";
    $all_users = $conn->query("SELECT id, username, password FROM users");
    if ($all_users->num_rows > 0) {
        while($row = $all_users->fetch_assoc()) {
            echo "ID: " . $row['id'] . "<br>";
            echo "Username: " . $row['username'] . "<br>";
            echo "Password Hash: " . $row['password'] . "<br><br>";
        }
    } else {
        echo "No users found in database!";
    }
} else {
    echo "No POST data received<br>";
}
?>