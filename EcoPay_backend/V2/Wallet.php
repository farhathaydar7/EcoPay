<?php

class Wallet {
    public $id;
    public $user_id;
    public $wallet_name;
    public $balance;
    public $currency;
    public $is_default;
    private $pdo;

    public function __construct($data, $pdo = null) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->wallet_name = $data['wallet_name'] ?? null;
        $this->balance = $data['balance'] ?? null;
        $this->currency = $data['currency'] ?? null;
        $this->is_default = $data['is_default'] ?? null;
        $this->pdo = $pdo;
    }

    // Create
    public function create() {
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("INSERT INTO Wallets (user_id, wallet_name, balance, currency, is_default) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$this->user_id, $this->wallet_name, $this->balance, $this->currency, $this->is_default]);
        $this->id = $this->pdo->lastInsertId();
        return $this->id ? true : false;
    }

    // Read (Get by ID)
    public static function getById($id, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM Wallets WHERE id = ?");
        $stmt->execute([$id]);
        $walletData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $walletData ? new Wallet($walletData, $pdo) : null;
    }

    // Update
    public function update() {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("UPDATE Wallets SET user_id = ?, wallet_name = ?, balance = ?, currency = ?, is_default = ? WHERE id = ?");
        $stmt->execute([$this->user_id, $this->wallet_name, $this->balance, $this->currency, $this->is_default, $this->id]);
        return $stmt->rowCount() > 0;
    }

    // Delete
    public function delete() {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("DELETE FROM Wallets WHERE id = ?");
        $stmt->execute([$this->id]);
        return $stmt->rowCount() > 0;
    }
}
