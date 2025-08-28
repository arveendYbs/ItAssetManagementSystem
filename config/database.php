<?php
/**
 * Database Configuration
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'itam_system';
    private $auth_db = 'it_request_system';
    private $username = 'root'; // Change as needed
    private $password = '';     // Change as needed
    private $charset = 'utf8mb4';
    private $conn;
    private $authConn;

    /*
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            exit;
        }

        return $this->conn;
    }*/
        // Get connection for System 2 (default DB)
    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            echo "Connection Error (System DB): " . $e->getMessage();
            exit;
        }
        return $this->conn;
    }

/*
// Global database connection function
function getDB() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}
?> */
// Get connection for Auth DB (System 1 users table)
    public function getAuthConnection() {
        $this->authConn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->auth_db};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->authConn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            echo "Connection Error (Auth DB): " . $e->getMessage();
            exit;
        }
        return $this->authConn;
    }
}

// Global helpers
function getDB() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}

function getAuthDB() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getAuthConnection();
    }
    return $db;
}