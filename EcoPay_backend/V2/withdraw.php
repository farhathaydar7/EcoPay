<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    echo "User not logged in.";
    exit;
    die();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "POST requests only.";
    exit;
    die();
}

$userId = $_SESSION["user_id"];

// Super Verification Check

$walletId = $_POST["wallet_id"];
$amount = $_POST["amount"];

if (empty($walletId) || !is_numeric($walletId) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo "Invalid request parameters.";
    exit;
    die();
}

$amount = floatval($amount);

try {
    $pdo->beginTransaction();

    // --- Check Wallet Balance for specific wallet ID ---
    $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$walletId, $userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        echo "Wallet not found or does not belong to user.";
        exit;
        die();
    }

    $currentBalance = $wallet["balance"];

    if ($currentBalance < $amount) {
        echo "Insufficient balance in selected wallet.";
        exit;
        die();
    }

    $newBalance = $currentBalance - $amount;
    // Withdrawing from the specified wallet
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newBalance, $walletId, $userId]);

    if ($stmt->rowCount() == 0) {
        $pdo->rollBack();
        echo "Wallet not found or does not belong to user.";
        exit;
        die();
    }

    // Record transaction
    require_once 'Transaction.php';
    $transaction = new Transaction([], $pdo);
    $transactionId = $transaction->recordPayment('withdraw', -$amount, $userId, 'completed');

    if ($transactionId) {
        $pdo->commit();
        echo "Withdrawal successful!";
    } else {
        $pdo->rollBack();
        echo "Withdrawal error: Failed to record transaction.";
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Withdrawal error: " . $e->getMessage();
}
?>
