<?php

class UserProfile {
    public $user_id;
    public $address;
    public $dob;
    public $profile_pic;
    private $pdo;

    public function __construct($data, $pdo = null) {
        $this->user_id = $data['user_id'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->dob = $data['dob'] ?? null;
        $this->profile_pic = $data['profile_pic'] ?? null;
        $this->pdo = $pdo;
    }

    // Create
    public function create() {
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("INSERT INTO UserProfiles (user_id, address, dob, profile_pic) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->user_id, $this->address, $this->dob, $this->profile_pic]);
        return $stmt->rowCount() > 0;
    }

    // Read (Get by user ID)
    public static function getByUserId($userId, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM UserProfiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $profileData ? new UserProfile($profileData, $pdo) : null;
    }

    // Update
    public function update() {
        if (!$this->pdo || !$this->user_id) return false;

        $stmt = $this->pdo->prepare("UPDATE UserProfiles SET address = ?, dob = ?, profile_pic = ? WHERE user_id = ?");
        $stmt->execute([$this->address, $this->dob, $this->profile_pic, $this->user_id]);
        return $stmt->rowCount() > 0;
    }

    // Delete (Although UserProfiles are typically deleted with the User)
    public function delete() {
        if (!$this->pdo || !$this->user_id) return false;

        $stmt = $this->pdo->prepare("DELETE FROM UserProfiles WHERE user_id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->rowCount() > 0;
    }
}
