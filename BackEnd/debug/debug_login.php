<?php
session_start();
require_once __DIR__ . '/../database/db.php';

class Debug {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function showLoginAttempt($username, $password) {
        echo "<h3>Login Attempt:</h3>";
        echo "Username: " . htmlspecialchars($username) . "<br>";
        echo "Password: " . str_repeat("*", strlen($password)) . "<br><br>";
    }

    public function showUserInfo($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<h3>User Database Info:</h3>";
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "Found user:<br>";
            echo "ID: " . $user['id'] . "<br>";
            echo "Username: " . $user['username'] . "<br>";
            echo "Role: " . $user['role'] . "<br>";
            echo "Password Hash: " . $user['password'] . "<br><br>";
        } else {
            echo "No user found with username: " . htmlspecialchars($username) . "<br><br>";
        }
    }

    public function showAllUsers() {
        $result = $this->conn->query("SELECT * FROM users");
        
        echo "<h3>All Users in Database:</h3>";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "ID: " . $row['id'] . "<br>";
                echo "Username: " . $row['username'] . "<br>";
                echo "Role: " . $row['role'] . "<br>";
                echo "Password Hash: " . $row['password'] . "<br><br>";
            }
        } else {
            echo "No users found in database.<br>";
        }
    }

    public function showSessionInfo() {
        echo "<h3>Current Session Info:</h3>";
        if (isset($_SESSION['user_id'])) {
            echo "User ID: " . $_SESSION['user_id'] . "<br>";
            echo "Username: " . $_SESSION['username'] . "<br>";
            echo "Role: " . $_SESSION['role'] . "<br>";
        } else {
            echo "No active session<br>";
        }
    }
}

// If this is a login attempt
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $debug = new Debug();
    
    echo "<h2>Debug Information</h2>";
    $debug->showLoginAttempt($_POST['username'], $_POST['password']);
    $debug->showUserInfo($_POST['username']);
    
    // Try to login
    require_once __DIR__ . '/../auth/login.php';
    $auth = new Auth();
    if ($auth->login($_POST['username'], $_POST['password'])) {
        echo "<div style='color: green; font-weight: bold;'>Login successful!</div>";
        $debug->showSessionInfo();
        echo "<p>You will be redirected to home page in 5 seconds...</p>";
        echo "<meta http-equiv='refresh' content='5;url=../public/home.php'>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>Login failed!</div>";
    }
}

// Show all users
$debug = new Debug();
$debug->showAllUsers();
?>