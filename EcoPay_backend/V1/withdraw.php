<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    echo "User not logged in.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "POST requests only.";
    exit;
}

$userId = $_SESSION["user_id"];

// Super Verification Check
if (!isSuperVerified($pdo, $userId)) {
    echo "User is not super verified.";
    exit;
}

$walletId = $_POST["wallet_id"];
$amount = $_POST["amount"];

if (empty($walletId) || !is_numeric($walletId) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo "Invalid request parameters.";
    exit;
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
    }

    $currentBalance = $wallet["balance"];

    if ($currentBalance < $amount) {
        echo "Insufficient balance in selected wallet.";
        exit;
    }

    $newBalance = $currentBalance - $amount;
    // Withdrawing from the specified wallet
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newBalance, $walletId, $userId]);

    if ($stmt->rowCount() == 0) {
        $pdo->rollBack();
        echo "Wallet not found or does not belong to user.";
        exit;
    }

    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'withdraw', -$amount, 'completed']); // Debit transaction

    $pdo->commit();
    echo "Withdrawal successful!";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Withdrawal error: " . $e->getMessage();
}
?>
