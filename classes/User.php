<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $email;
    public $password;
    public $role;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . 
                    " (email, password_hash, role) VALUES 
                    (:email, :password, :role)";

            $stmt = $this->conn->prepare($query);

            // Clean and hash data
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
            $this->role = htmlspecialchars(strip_tags($this->role));

            // Bind parameters
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":role", $this->role);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            throw new Exception("Error creating user: " . $e->getMessage());
        }
    }

    public function emailExists() {
        try {
            $query = "SELECT user_id, password_hash, role 
                    FROM " . $this->table_name . " 
                    WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            
            $this->email = htmlspecialchars(strip_tags($this->email));
            $stmt->bindParam(":email", $this->email);
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw new Exception("Error checking email: " . $e->getMessage());
        }
    }
}
?>
