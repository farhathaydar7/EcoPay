<?php
require_once 'db_connection.php'; // Ensuring database connection
require_once "Transaction.php";
class Transaction {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function recordPayment($userId, $walletId, $type, $amount, $status = 'pending', $p2pData = null) {
        error_log("recordPayment() called for user_id: $userId, type: $type, amount: $amount, wallet_id: $walletId");

        try {
            // Start transaction
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            // Insert transaction record
            $stmt = $this->pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp) 
                                         VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $walletId, $type, $amount, $status]);
            $transactionId = $this->pdo->lastInsertId();
            error_log("Transaction inserted: ID $transactionId");

            // If it's a P2P transaction, add to P2P_Transfers
            if ($type === 'p2p' && is_array($p2pData)) {
                if (isset($p2pData['sender_id']) && isset($p2pData['receiver_id']) && is_string($p2pData['sender_id']) && is_string($p2pData['receiver_id'])) {
                    $stmt = $this->pdo->prepare("INSERT INTO P2P_Transfers (transaction_id, sender_id, receiver_id) 
                                                 VALUES (?, ?, ?)");
                    $stmt->execute([$transactionId, $p2pData['sender_id'], $p2pData['receiver_id']]);
                    error_log("P2P Transfer recorded: transaction_id $transactionId, sender {$p2pData['sender_id']}, receiver {$p2pData['receiver_id']}");
                } else {
                    error_log("P2P data missing sender_id or receiver_id, or they are not strings.");
                }
            }

            // Commit transaction
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Transaction recorded successfully', 'transaction_id' => $transactionId];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Transaction failed: " . print_r($e->getMessage(), true));
            return ['success' => false, 'message' => 'Transaction failed', 'error' => $e->getMessage()];
        }
    }
}

// Usage Example:
//$transaction = new Transaction($pdo);
//$response = $transaction->recordPayment(1, 1, 'deposit', 100.00);
//echo json_encode($response);
?>

