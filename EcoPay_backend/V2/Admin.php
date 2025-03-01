<?php

class Admin {
    public $id;
    public $name;
    public $email;
    public $password;
    public $created_at;
    private $pdo;

    public function __construct($data, $pdo = null) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->pdo = $pdo;
    }

    // Read (Get by Email)
    public static function getByEmail($email, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM Admins WHERE email = ?");
        $stmt->execute([$email]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $adminData ? new Admin($adminData, $pdo) : null;
    }
}
