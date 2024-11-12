<?php
class Gallery {
    private $conn;
    private $table_name = "galleries";

    public $gallery_id;
    public $user_id;
    public $name;
    public $description;
    public $address;
    public $phone;
    public $website;
    public $profile_image;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . 
                    " (user_id, name, description, address, phone, website) VALUES 
                    (:user_id, :name, :description, :address, :phone, :website)";

            $stmt = $this->conn->prepare($query);

            // Clean data
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->address = htmlspecialchars(strip_tags($this->address));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->website = htmlspecialchars(strip_tags($this->website));

            // Bind parameters
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":address", $this->address);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":website", $this->website);

            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error creating gallery: " . $e->getMessage());
        }
    }
}
?>
