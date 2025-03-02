<?php
require_once 'db_connection.php';
require_once '../V2/models/receipt.model.php'; // Include the Receipt model

session_start();

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "POST requests only."]);
    exit;
}

$userId = $_SESSION["user_id"];

// Validate input
if (!isset($_POST["wallet_id"]) || !isset($_POST["amount"])) {
    echo json_encode(["success" => false, "message" => "Missing wallet_id or amount."]);
    exit;
}

$walletId = $_POST["wallet_id"];
$amount = $_POST["amount"];

if (empty($walletId) || !is_numeric($walletId) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid request parameters."]);
    exit;
}

$amount = floatval($amount);

try {
    // Begin transaction if not already active
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    // Check Wallet Balance
    $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$walletId, $userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        echo json_encode(["success" => false, "message" => "Wallet not found or does not belong to user."]);
        exit;
    }

    $currentBalance = $wallet["balance"];
    if ($currentBalance < $amount) {
        echo json_encode(["success" => false, "message" => "Insufficient balance in selected wallet."]);
        exit;
    }

    $newBalance = $currentBalance - $amount;

    // Deduct from Wallet
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newBalance, $walletId, $userId]);
    if ($stmt->rowCount() == 0) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(["success" => false, "message" => "Failed to update balance."]);
        exit;
    }

    // Record Transaction (withdrawals recorded as negative amount)
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp) 
                           VALUES (?, ?, 'withdraw', ?, 'completed', NOW())");
    // Amount is negative for withdrawal
    $stmt->execute([$userId, $walletId, -$amount]);
    $transactionId = $pdo->lastInsertId();

    if (!$transactionId) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(["success" => false, "message" => "Failed to record transaction."]);
        exit;
    }

    // Create and store receipt using the Receipt model
    $receiptModel = new Receipt($pdo);
    $receiptData = $receiptModel->createReceipt('withdrawal', $userId, $walletId, $amount, $transactionId, [
        "method" => "Bank Transfer",
        "status" => "Completed"
    ]);

    if (!$receiptData) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(["success" => false, "message" => "Failed to generate receipt."]);
        exit;
    }

    // Commit the transaction
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Withdrawal successful!",
        "transaction_id" => $transactionId,
        "receipt" => $receiptData
    ]);
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Withdrawal error: " . $e->getMessage()]);
    exit();
}
?>
