<?php

class VerificationStatus {
    public $id;
    public $user_id;
    public $email_verified;
    public $document_verified;
    public $super_verified;
    private $pdo;

    public function __construct($data, $pdo = null) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->email_verified = $data['email_verified'] ?? false;
        $this->document_verified = $data['document_verified'] ?? false;
        $this->super_verified = $data['super_verified'] ?? false;
        $this->pdo = $pdo;
    }

    // Read (Get by user ID)
    public static function getByUserId($userId, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM VerificationStatuses WHERE user_id = ?");
        $stmt->execute([$userId]);
        $verificationStatusData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $verificationStatusData ? new VerificationStatus($verificationStatusData, $pdo) : null;
    }

    // Create or Update
    public function save() {
        if (!$this->pdo) return false;

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM VerificationStatuses WHERE user_id = ?");
            $stmt->execute([$this->user_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update
                $stmt = $this->pdo->prepare("UPDATE VerificationStatuses SET email_verified = ?, document_verified = ?, super_verified = ? WHERE user_id = ?");
                $stmt->execute([$this->email_verified, $this->document_verified, $this->super_verified, $this->user_id]);
            } else {
                // Create
                $stmt = $this->pdo->prepare("INSERT INTO VerificationStatuses (user_id, email_verified, document_verified, super_verified) VALUES (?, ?, ?, ?)");
                $stmt->execute([$this->user_id, $this->email_verified, $this->document_verified, $this->super_verified]);
                $this->id = $this->pdo->lastInsertId();
            }
            return true;
        } catch (PDOException $e) {
            error_log("VerificationStatus save error: " . $e->getMessage());
            return false;
        }
    }
}
