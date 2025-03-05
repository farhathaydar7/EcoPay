<?php
require_once 'db_connection.php';
require_once 'TransactionHistory.php';

session_start();

if (!isset($_SESSION["user_id"])) {
    echo "User not logged in.";
    exit;
}

$userId = $_SESSION["user_id"];

// Super Verification Check

try {
    // --- Fetch  History ---
    $transactions = TransactionHistory::getByUserId($userId, $pdo);

    if (empty($transactions)) {
        echo json_encode([]);
        exit;
    }

    $allTransactions = [];
    foreach ($transactions as $transaction) {
        if ($transaction->type !== 'transfer' && $transaction->type !== 'p2p') {
            $transactionData = [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'status' => $transaction->status,
                'timestamp' => $transaction->timestamp,
            ];
            $allTransactions[] = $transactionData;
        }
    }

    echo json_encode($allTransactions);

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
