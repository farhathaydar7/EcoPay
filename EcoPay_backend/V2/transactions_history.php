<?php
require_once 'db_connection.php';
require_once 'Transaction.php';

session_start();

if (!isset($_SESSION["user_id"])) {
    echo "User not logged in.";
    exit;
}

$userId = $_SESSION["user_id"];

// Super Verification Check

try {
    // --- Fetch  History ---
    $transactions = Transaction::getByUserId($userId, $pdo);

    if (empty($transactions)) {
        echo "No transaction history found.";
        exit;
    }

    $allTransactions = [];
    foreach ($transactions as $transaction) {
        $transactionData = [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'status' => $transaction->status,
            'timestamp' => $transaction->timestamp
        ];

        if ($transaction->type === 'transfer') {
            // Fetch receiver's name
            $stmt = $pdo->prepare("SELECT fName, lName FROM Users WHERE id = ?");
            $stmt->execute([$transaction->receiver_id]);
            $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
            $transactionData['receiver'] = $receiver ? $receiver['fName'] . ' ' . $receiver['lName'] : 'Unknown';
        }

        $allTransactions[] = $transactionData;
    }

    echo json_encode($allTransactions);

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
