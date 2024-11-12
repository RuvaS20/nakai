<?php
class Database {
    private $host = "localhost";
    private $db_name = "nakai_db";
    private $username = "root";  // Change as needed
    private $password = "";      // Change as needed
    private $conn = null;

    public function getConnection() {
        try {
            if ($this->conn === null) {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            return $this->conn;
        } catch(PDOException $e) {
            throw new Exception("Connection error: " . $e->getMessage());
        }
    }
}
?>
