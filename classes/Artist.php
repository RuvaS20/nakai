<?php
class Artist {
    private $conn;
    private $table_name = "artists";

    public $artist_id;
    public $user_id;
    public $name;
    public $bio;
    public $phone;
    public $profile_image_url;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . 
                    " (user_id, name, bio, phone) VALUES 
                    (:user_id, :name, :bio, :phone)";

            $stmt = $this->conn->prepare($query);

            // Clean data
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->bio = htmlspecialchars(strip_tags($this->bio));
            $this->phone = htmlspecialchars(strip_tags($this->phone));

            // Bind parameters
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":bio", $this->bio);
            $stmt->bindParam(":phone", $this->phone);

            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error creating artist: " . $e->getMessage());
        }
    }
}
?>
