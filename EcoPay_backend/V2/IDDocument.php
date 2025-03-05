<?php

class IDDocument {
    public $id;
    public $user_id;
    public $link;
    private $pdo;

    public function __construct($data, $pdo = null) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->link = $data['link'] ?? null;
        $this->pdo = $pdo;
    }

    // Read (Get by user ID)
    public static function getByUserId($userId, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM IDDocuments WHERE user_id = ?");
        $stmt->execute([$userId]);
        $idDocumentData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $idDocumentData ? new IDDocument($idDocumentData, $pdo) : null;
    }
}
