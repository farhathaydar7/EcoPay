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
    echo "Invalid deposit amount.";
    exit;
}

$amount = floatval($amount);

try {
    $pdo->beginTransaction();

    //direct update
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE user_id = ?");
    $stmt->execute([$amount, $userId]);

    //record transaction
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'deposit', $amount, 'completed']);

    $pdo->commit();
    echo "Deposit successful!";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Deposit error: " . $e->getMessage();
}
?>