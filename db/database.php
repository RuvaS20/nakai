<?php
require_once __DIR__ . '/config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
    $this->conn = null;

    try {
        $this->conn = new PDO(
            "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
            $this->username,
            $this->password
        );
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        error_log("Database connection successful");
    } catch(PDOException $e) {
        error_log("Connection Error: " . $e->getMessage());
    }

    return $this->conn;
}
}
