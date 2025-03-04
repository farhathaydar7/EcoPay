<?php
require_once 'db_connection.php';
require_once 'User.php';
require_once 'Wallet.php';
// Insert transaction record directly, without a separate Transaction class.
require_once '/var/www/html/EcoPay/V2/models/receipt.model.php'; // Include Receipt Model

session_start();

if (!isset($_SESSION["user_id"])) {
    error_log("Deposit script started. User not logged in.");
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    die();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    error_log("Deposit script started. Invalid request method.");
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    die();
}

$userId = $_SESSION["user_id"] ?? null;
if ($userId === null) {
    error_log("Deposit script started. User ID not found.");
    echo json_encode(["success" => false, "message" => "User ID not found."]);
    die();
}

if (!isset($_POST["wallet_id"]) || !isset($_POST["amount"])) {
    error_log("Deposit script started. Missing wallet_id or amount.");
    echo json_encode(["success" => false, "message" => "Missing wallet_id or amount."]);
    die();
}

$walletId = $_POST["wallet_id"];
$amount = $_POST["amount"];

if (empty($walletId) || !is_numeric($walletId) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    error_log("Deposit script started. Invalid wallet ID or amount.");
    echo json_encode(["success" => false, "message" => "Invalid wallet ID or amount."]);
    die();
}

$amount = floatval($amount);

try {
    error_log("Deposit script started.");
    error_log("Fetching wallet details for user $userId, wallet $walletId.");
    $stmt = $pdo->prepare("SELECT id, user_id, balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$walletId, $userId]);
    $walletData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$walletData) {
        error_log("Wallet not found for user $userId.");
        echo json_encode(["success" => false, "message" => "Wallet not found."]);
        die();
    }
    
    $newBalance = $walletData['balance'] + $amount;
    error_log("New balance calculated: $newBalance");

    // Update wallet balance
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newBalance, $walletId, $userId]);
    
    if ($stmt->rowCount() == 0) {
        error_log("Balance update failed for wallet $walletId.");
        echo json_encode(["success" => false, "message" => "Balance update failed."]);
        die();
    }

    error_log("Balance updated successfully. Inserting transaction...");

    // Insert transaction record directly
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp) 
                           VALUES (?, ?, 'deposit', ?, 'completed', NOW())");
    $stmt->execute([$userId, $walletId, $amount]);
    $transactionId = $pdo->lastInsertId();
    
    if (!$transactionId) {
        error_log("Failed to insert transaction.");
        echo json_encode(["success" => false, "message" => "Failed to record transaction."]);
        die();
    }

    error_log("Transaction recorded with ID: $transactionId");

    // Create receipt using the Receipt model
    $receiptModel = new Receipt($pdo);
    $receiptData = $receiptModel->createReceipt('deposit', $userId, $walletId, $amount, $transactionId, [
        "method" => "Bank Transfer",
        "status" => "Completed"
    ]);
    
    if (!$receiptData) {
        error_log("Failed to generate receipt for transaction ID: $transactionId");
        echo json_encode(["success" => false, "message" => "Failed to generate receipt."]);
        die();
    }

    error_log("Receipt generated successfully: " . json_encode($receiptData));

    echo json_encode([
        "success" => true,
        "message" => "Deposit successful",
        "transaction_id" => $transactionId,
        "receipt" => $receiptData
    ]);
    die();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    die();
}
?>
