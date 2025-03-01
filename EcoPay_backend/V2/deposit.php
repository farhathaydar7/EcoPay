<?php
require_once 'db_connection.php';
require_once 'User.php'; // Include User.php where isSuperVerified is now defined
require_once 'Wallet.php';

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
    $stmt = $pdo->prepare("SELECT id, user_id, wallet_name, balance, currency, is_default FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$walletId, $userId]);
    $walletData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$walletData) {
        echo json_encode(["success" => false, "message" => "Wallet not found."]);
        die();
    }

    $wallet = new Wallet($walletData);
    $newBalance = $wallet->balance + $amount;

    // Update wallet balance
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newBalance, $wallet->id, $userId]);

    if ($stmt->rowCount() == 0) {
        echo json_encode(["success" => false, "message" => "Balance update failed."]);
        die();
    }

    // Record transaction without transaction handling - WARNING: No rollback!
    require_once 'Transaction.php';
    $transaction = new Transaction($pdo);
    $transactionId = $transaction->recordPayment($userId, $walletId, 'deposit', $amount, 'completed');

    if ($transactionId) {
        echo json_encode([
            "success" => true,
            "message" => "Transaction recorded successfully",
            "transaction_id" => $transactionId
        ]);
        die();
    } else {
        echo json_encode(["success" => false, "message" => "Failed to record transaction."]);
        die();
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    die();
}
?>
