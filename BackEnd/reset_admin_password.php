<?php
require_once 'db.php';

// Default users and their passwords
$users = [
    ['username' => 'admin', 'password' => 'admin123', 'role' => 'admin'],
    ['username' => 'teacher1', 'password' => 'teacher123', 'role' => 'teacher'],
    ['username' => 'student1', 'password' => 'student123', 'role' => 'student']
];

echo "<h2>Setting up default users:</h2>";

foreach ($users as $user) {
    $username = $user['username'];
    $password = $user['password'];
    $role = $user['role'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if user exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing user
        $sql = "UPDATE users SET password = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hash, $username);
    } else {
        // Insert new user
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $hash, $role);
    }
    
    if ($stmt->execute()) {
        echo "<div style='margin-bottom: 20px;'>";
        echo "<strong>User setup successful:</strong><br>";
        echo "Username: " . htmlspecialchars($username) . "<br>";
        echo "Password: " . htmlspecialchars($password) . "<br>";
        echo "Role: " . htmlspecialchars($role) . "<br>";
        echo "</div>";
    } else {
        echo "Error setting up " . htmlspecialchars($username) . ": " . $conn->error . "<br>";
    }
}

echo "<h3>You can now log in with any of these accounts.</h3>";
echo "<p>Go to <a href='../public/index.html'>login page</a></p>";
?>