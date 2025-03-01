<?php

class User {
    public $id;
    public $userName;
    public $fName;
    public $lName;
    public $email;
    public $password;
    public $created_at;
    public $otp;
    public $otp_expiry;
    public $activatedAcc;
    private $pdo;

    public function __construct($data, $pdo = null) {
        $this->id = $data['id'] ?? null;
        $this->userName = $data['userName'] ?? null;
        $this->fName = $data['fName'] ?? null;
        $this->lName = $data['lName'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->otp = $data['otp'] ?? null;
        $this->otp_expiry = $data['otp_expiry'] ?? null;
        $this->activatedAcc = $data['activatedAcc'] ?? null;
        $this->pdo = $pdo; // Store PDO connection
    }

    // Create
    public function create() {
        if (!$this->pdo) return false; // No database connection

        $stmt = $this->pdo->prepare("INSERT INTO Users (userName, fName, lName, email, password, otp, otp_expiry, activatedAcc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$this->userName, $this->fName, $this->lName, $this->email, $this->password, $this->otp, $this->otp_expiry, $this->activatedAcc]);
        $this->id = $this->pdo->lastInsertId(); // Get the newly created user ID
        return $this->id ? true : false;
    }

    // Read (Get by ID)
    public static function getById($id, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $userData ? new User($userData, $pdo) : null;
    }

    // Read (Get by Email)
    public static function getByEmail($email, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $userData ? new User($userData, $pdo) : null;
    }

    // Update
    public function update() {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("UPDATE Users SET userName = ?, fName = ?, lName = ?, email = ?, password = ?, otp = ?, otp_expiry = ?, activatedAcc = ? WHERE id = ?");
        $stmt->execute([$this->userName, $this->fName, $this->lName, $this->email, $this->password, $this->otp, $this->otp_expiry, $this->activatedAcc, $this->id]);
        return $stmt->rowCount() > 0;
    }

    // Delete
    public function delete() {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("DELETE FROM Users WHERE id = ?");
        $stmt->execute([$this->id]);
        return $stmt->rowCount() > 0;
    }
}
