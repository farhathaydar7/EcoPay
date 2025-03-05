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

$userId = $_SESSION["user_id"] ?? null;

if ($userId === null) {
    echo "User not logged in.";
    exit;
}

// Super Verification Check
if (!isSuperVerified($pdo, $userId)) {
    echo "User is not super verified.";
    exit;
}

if (!isset($_POST["wallet_id"]) || !isset($_POST["amount"])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid request parameters."]);
    error_log("Deposit error: Invalid request parameters - missing wallet_id or amount.");
    exit;
}

// Log the contents of $_POST
error_log('Deposit.php - $_POST: ' . print_r($_POST, true));

$walletId = $_POST["wallet_id"];
$amount = $_POST["amount"];

if (empty($walletId) || !is_numeric($walletId) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid request parameters."]);
    error_log("Deposit error: Invalid request parameters - invalid wallet_id or amount format.");
    exit;
}

$amount = floatval($amount);

try {
    $pdo->beginTransaction();

    // --- Depositing into the specified wallet ---
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$amount, $walletId, $userId]);

    // Check if the wallet was updated
    if ($stmt->rowCount() == 0) {
        $pdo->rollBack();
        echo "Wallet not found or does not belong to user.";
        exit;
    }

    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'deposit', $amount, 'completed']); // Credit transaction

    $pdo->commit();
    echo "Deposit successful!";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Deposit error: " . $e->getMessage();
}
?>
