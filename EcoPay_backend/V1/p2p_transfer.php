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

// Super Verification Check
if (!isSuperVerified($pdo, $senderId)) {
    echo "User is not super verified.";
    exit;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data) {
    echo "Invalid JSON request.";
    exit;
}

$receiverIdentifier = $data["receiver_identifier"] ?? null; // Should be email
$senderWalletId = $data["sender_wallet_id"] ?? null;
$amount = $data["amount"] ?? null;

if (
    empty($receiverIdentifier) || 
    empty($senderWalletId) || !is_numeric($senderWalletId) ||
    empty($amount) || !is_numeric($amount) || $amount <= 0
) {
    echo "Invalid request parameters.";
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
    $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$senderWalletId, $senderId]);
    $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$senderWallet || $senderWallet["balance"] < $amount) {
        echo "Insufficient balance in sender's wallet.";
        $pdo->rollBack();
        exit;
    }

    // --- Update Balances ---
    $newSenderBalance = $senderWallet["balance"] - $amount;
    // Transfer from sender's specified wallet
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newSenderBalance, $senderWalletId, $senderId]);

    // --- Get Receiver's Default Wallet ---
    $stmt = $pdo->prepare("SELECT id FROM Wallets WHERE user_id = ? AND is_default = TRUE");
    $stmt->execute([$receiverId]);
    $receiverWallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receiverWallet) {
        echo "Receiver's default wallet not found.";
        $pdo->rollBack();
        exit;
    }
    $receiverWalletId = $receiverWallet["id"];

    // --- Transfer to receiver's default wallet ---
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$amount, $receiverWalletId, $receiverId]);

    // Check if receiver wallet was updated
    if ($stmt->rowCount() == 0) {
        $pdo->rollBack();
        echo "Receiver wallet not found or does not belong to receiver.";
        exit;
    }

    // --- Record Transfer Details ---
    $stmt = $pdo->prepare("INSERT INTO Transfers (sender_id, receiver_id, amount, sender_wallet_id, receiver_wallet_id, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$senderId, $receiverId, $amount, $senderWalletId, $receiverWalletId, 'completed']);

    $pdo->commit();
    echo "Transfer successful!";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Transfer error: " . $e->getMessage();
}
?>
