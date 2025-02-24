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
$amount = $_POST["amount"];

if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo "Invalid withdrawal amount.";
    exit;
}

$amount = floatval($amount); // Convert to float for cents

try {
    $pdo->beginTransaction(); 

    
    $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        echo "Wallet not found.";
        exit;
    }

    $currentBalance = $wallet["balance"];

    if ($currentBalance < $amount) {
        echo "Insufficient balance.";
        exit;
    }

    $newBalance = $currentBalance - $amount;
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE user_id = ?");
    $stmt->execute([$newBalance, $userId]);

    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'withdraw', $amount, 'completed']);

    $pdo->commit();
    echo "Withdrawal successful!";

} catch (PDOException $e) {
    $pdo->rollBack(); // if error rollback to avoid cheating or technichal errors
    echo "Withdrawal error: " . $e->getMessage();
}
?>