<?php
require_once 'db_connection.php';
require_once 'User.php';
require_once 'Wallet.php';
// Insert transaction record directly, without a separate Transaction class.
require_once '../V2/models/receipt.model.php'; // Include Receipt Model

session_start();

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    die();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    die();
}

$userId = $_SESSION["user_id"] ?? null;
if ($userId === null) {
    echo json_encode(["success" => false, "message" => "User ID not found."]);
    die();
}

if (!isset($_POST["wallet_id"]) || !isset($_POST["amount"])) {
    echo json_encode(["success" => false, "message" => "Missing wallet_id or amount."]);
    die();
}

$walletId = $_POST["wallet_id"];
$amount = $_POST["amount"];

if (empty($walletId) || !is_numeric($walletId) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid wallet ID or amount."]);
    die();
}

$amount = floatval($amount);

try {
    // Fetch wallet details
    $stmt = $pdo->prepare("SELECT id, user_id, balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$walletId, $userId]);
    $walletData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$walletData) {
        echo json_encode(["success" => false, "message" => "Wallet not found."]);
        die();
    }
    
    $newBalance = $walletData['balance'] + $amount;

    // Update wallet balance
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newBalance, $walletId, $userId]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(["success" => false, "message" => "Balance update failed."]);
        die();
    }

    // Insert transaction record directly
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp) 
                           VALUES (?, ?, 'deposit', ?, 'completed', NOW())");
    $stmt->execute([$userId, $walletId, $amount]);
    $transactionId = $pdo->lastInsertId();
    
    if (!$transactionId) {
        echo json_encode(["success" => false, "message" => "Failed to record transaction."]);
        die();
    }

    // Create receipt using the Receipt model
    $receiptModel = new Receipt($pdo);
    $receiptData = $receiptModel->createReceipt('deposit', $userId, $walletId, $amount, $transactionId, [
        "method" => "Bank Transfer",
        "status" => "Completed"
    ]);
    
    if (!$receiptData) {
        echo json_encode(["success" => false, "message" => "Failed to generate receipt."]);
        die();
    }

    echo json_encode([
        "success" => true,
        "message" => "Deposit successful",
        "transaction_id" => $transactionId,
        "receipt" => $receiptData
    ]);
    die();

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    die();
}
?>
