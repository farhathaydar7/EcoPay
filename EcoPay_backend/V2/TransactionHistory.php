<?php
require_once 'db_connection.php';

class TransactionHistory {
    public static function getByUserId($userId, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM Transactions WHERE user_id = ? ORDER BY timestamp DESC");
        $stmt->execute([$userId]);
        $transactions = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $transactions;
    }
}
