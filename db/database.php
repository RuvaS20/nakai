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
            // Create the connection
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            
            // Set character set to utf8
            $this->conn->exec("set names utf8");
            
            return $this->conn;
            
        } catch(PDOException $e) {
            // Log the error details
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Check specific error conditions
            if ($e->getCode() == 1049) {
                throw new Exception("Database '{$this->db_name}' does not exist. Please run the database setup script.");
            } else if ($e->getCode() == 2002) {
                throw new Exception("Could not connect to database server. Please check if MySQL is running.");
            } else if ($e->getCode() == 1045) {
                throw new Exception("Invalid database credentials. Please check username and password.");
            }
            
            // For any other errors
            throw new Exception("Database connection failed. Please check error logs for details.");
        }
    }
}
?>
