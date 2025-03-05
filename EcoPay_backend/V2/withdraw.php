<?php
require_once 'db_connection.php';
require_once '/var/www/html/EcoPay/EcoPay_backend/V2/models/receipt.model.php';

session_start();

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$userId = $_SESSION["user_id"];

// Validate input
if (empty($_POST["wallet_id"]) || empty($_POST["amount"])) {
    echo json_encode(["success" => false, "message" => "Missing wallet_id or amount."]);
    exit;
}

$walletId = $_POST["wallet_id"];
$amount = $_POST["amount"];

// Validate numeric values
if (!is_numeric($walletId) || !is_numeric($amount) || $amount <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid wallet ID or amount."]);
    exit;
}

$amount = floatval($amount);

try {
    // Start transaction
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    // Check Wallet Balance
    $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$walletId, $userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        throw new Exception("Wallet not found or does not belong to user.");
    }

    $currentBalance = $wallet["balance"];
    if ($currentBalance < $amount) {
        throw new Exception("Insufficient balance in selected wallet.");
    }

    // Deduct from Wallet
    $newBalance = $currentBalance - $amount;
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newBalance, $walletId, $userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Failed to update balance.");
    }

    // Record Transaction
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp) 
                           VALUES (?, ?, 'withdraw', ?, 'completed', NOW())");
    $stmt->execute([$userId, $walletId, -$amount]);
    $transactionId = $pdo->lastInsertId();

    if (!$transactionId) {
        throw new Exception("Failed to record transaction.");
    }

    // Generate receipt
    $receiptModel = new Receipt($pdo);
    $receiptData = $receiptModel->createReceipt('withdrawal', $userId, $walletId, $amount, $transactionId, [
        "method" => "Bank Transfer",
        "status" => "Completed"
    ]);

    if (!$receiptData) {
        throw new Exception("Failed to generate receipt.");
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Withdrawal successful!",
        "transaction_id" => $transactionId,
        "receipt" => $receiptData
    ]);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Withdrawal error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit();
}
?>
