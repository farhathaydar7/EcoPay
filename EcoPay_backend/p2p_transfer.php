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

$senderId = $_SESSION["user_id"];
$receiverIdentifier = $_POST["receiver_identifier"]; // Should be email
$amount = $_POST["amount"];

if (empty($receiverIdentifier) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo "Invalid input.";
    exit;
}

$amount = floatval($amount);

try {
    $pdo->beginTransaction();

    // --- Resolve Receiver ID by Email ---
    $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->execute([$receiverIdentifier]);
    $receiverUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receiverUser) {
        echo "Receiver not found.";
        $pdo->rollBack();
        exit;
    }
    $receiverId = $receiverUser["id"];

    if ($senderId == $receiverId) {
        echo "Cannot transfer to yourself.";
        $pdo->rollBack();
        exit;
    }

    // --- Check Sender Balance ---
    $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE user_id = ?");
    $stmt->execute([$senderId]);
    $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$senderWallet || $senderWallet["balance"] < $amount) {
        echo "Insufficient balance.";
        $pdo->rollBack();
        exit;
    }

    // --- Update Balances ---
    $newSenderBalance = $senderWallet["balance"] - $amount;
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE user_id = ?");
    $stmt->execute([$newSenderBalance, $senderId]);

    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE user_id = ?");
    $stmt->execute([$amount, $receiverId]);

    // --- Record Transactions ---
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$senderId, 'transfer', -$amount, 'completed']); // Negative amount for sender

    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$receiverId, 'transfer', $amount, 'completed']); // Positive amount for receiver

    // --- Record Transfer Details ---
    $stmt = $pdo->prepare("INSERT INTO Transfers (sender_id, receiver_id, amount, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$senderId, $receiverId, $amount, 'completed']);

    $pdo->commit();
    echo "Transfer successful!";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Transfer error: " . $e->getMessage();
}
?>