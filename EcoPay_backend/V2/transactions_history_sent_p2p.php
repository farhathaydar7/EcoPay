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

    $sentP2pTransactions = [];
    foreach ($transactions as $transaction) {
        if ($transaction->type === 'transfer' || $transaction->type === 'p2p') {
            $stmtP2P = $pdo->prepare("SELECT sender_id, receiver_id FROM P2P_Transfers WHERE transaction_id = ?");
            $stmtP2P->execute([$transaction->id]);
            $p2pTransfer = $stmtP2P->fetch(PDO::FETCH_ASSOC);

            if ($p2pTransfer && $p2pTransfer['sender_id'] == $userId) {
                $stmt = $pdo->prepare("SELECT u.fName, u.lName, u.email FROM Users u JOIN P2P_Transfers p2p ON u.id = p2p.receiver_id WHERE p2p.transaction_id = ?");
                $stmt->execute([$transaction->id]);
                $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

                $transactionData = [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'timestamp' => $transaction->timestamp,
                    'receiver' => 'Unknown',
                    'receiver_email' => 'Unknown'
                ];

                if ($receiver) {
                    $transactionData['receiver'] = $receiver['fName'] . ' ' . $receiver['lName'];
                    $transactionData['receiver_email'] = $receiver['email'];
                }
                $sentP2pTransactions[] = $transactionData;
            }
        }
    }

    echo json_encode($sentP2pTransactions);

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
