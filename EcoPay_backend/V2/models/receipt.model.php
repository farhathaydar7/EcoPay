<?php
class Receipt {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Creates a receipt for a transaction and stores it in the database.
     *
     * @param string $transactionType - e.g. 'deposit', 'withdraw', 'p2p'
     * @param int $userId - User ID
     * @param int $walletId - Wallet ID
     * @param float $amount - Transaction amount
     * @param int $transactionId - The related transaction ID
     * @param array $extraData - Additional details to store (will be JSON-encoded)
     * @return array|false - Receipt data on success, false on failure.
     */
    public function createReceipt($transactionType, $userId, $walletId, $amount, $transactionId, $extraData = []) {
        try {
            $timestamp = date("Y-m-d H:i:s");
            // Convert extraData to JSON string
            $extraDataJson = json_encode($extraData);
            
            // Insert receipt into the Receipts table
            $stmt = $this->pdo->prepare("
                INSERT INTO Receipts (transaction_id, user_id, wallet_id, transaction_type, amount, extra_data, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $transactionId, 
                $userId, 
                $walletId, 
                $transactionType, 
                $amount, 
                $extraDataJson, 
                $timestamp
            ]);

            if ($stmt->rowCount() > 0) {
                return [
                    "date" => $timestamp,
                    "transaction_type" => ucfirst($transactionType),
                    "user_id" => $userId,
                    "wallet_id" => $walletId,
                    "amount" => number_format($amount, 2),
                    "transaction_id" => $transactionId,
                    "extra_data" => $extraData
                ];
            }
            return false;
        } catch (PDOException $e) {
            error_log("Receipt creation failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
