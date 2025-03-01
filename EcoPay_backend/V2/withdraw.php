<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
    die();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "POST requests only."]);
    exit;
    die();
}

$userId = $_SESSION["user_id"];

// Super Verification Check

$walletId = $_POST["wallet_id"];
$amount = $_POST["amount"];

if (empty($walletId) || !is_numeric($walletId) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid request parameters."]);
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
        echo json_encode(["success" => false, "message" => "Wallet not found or does not belong to user."]);
        exit;
        die();
    }

    $currentBalance = $wallet["balance"];

    if ($currentBalance < $amount) {
        echo json_encode(["success" => false, "message" => "Insufficient balance in selected wallet."]);
        exit;
        die();
    }

    $newBalance = $currentBalance - $amount;
    // Withdrawing from the specified wallet
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newBalance, $walletId, $userId]);

    if ($stmt->rowCount() == 0) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Wallet not found or does not belong to user."]);
        exit;
        die();
    }

    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp) 
                                         VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $walletId, 'withdraw', -$amount, 'completed']);
    $transactionId = $pdo->lastInsertId();

    if ($transactionId) {
        $pdo->commit();
        echo json_encode([
            "success" => true,
            "message" => "Withdrawal successful!",
            "transaction_id" => $transactionId
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Withdrawal error: Failed to record transaction."]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Withdrawal error: " . $e->getMessage()]);
}
?>
