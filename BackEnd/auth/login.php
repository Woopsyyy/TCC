<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

class Auth {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function login($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        return false;
    }

    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /TCC/public/index.html");
            exit();
        }
    }
}

// Only process login if this file is accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $auth = new Auth();
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($auth->login($username, $password)) {
            header("Location: /TCC/public/home.php");
        } else {
            header("Location: /TCC/public/index.html?error=1");
        }
        exit();
    }
}
?>