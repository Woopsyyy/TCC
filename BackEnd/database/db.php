<?php
class Database {
    private $conn = null;
    private static $instance = null;
    private $servername = "localhost";
    private $username   = "root";
    private $password   = "";
    private $dbname     = "accountmanager";

    private function __construct() {
        $this->connect();
    }

    private function connect($database = null) {
        try {
            // Always use accountmanager database unless specifically told otherwise
            $db = $database ?? 'accountmanager';
            
            // Create connection with database
            $this->conn = new mysqli($this->servername, $this->username, $this->password, $db);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public function reconnect($database = null) {
        if ($this->conn) {
            $this->conn->close();
        }
        $this->connect($database);
        return $this->conn;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>