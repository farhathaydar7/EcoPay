<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';
require_once 'config.php'; // Ensure $pdo is initialized here

// Read and decode JSON input
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Debugging: Check if data is received properly
if ($data === null) {
    header('Content-Type: application/json');
    die(json_encode(["error" => "Invalid JSON format"]));
}

// Extract variables safely
$receiverIdentifier = $data["receiver_identifier"] ?? null;
$senderWalletId = $data["sender_wallet_id"] ?? null;
$amount = $data["amount"] ?? null;

// Validate required fields
if (!$receiverIdentifier) {
    header('Content-Type: application/json');
    die(json_encode(["error" => "receiver_identifier is missing"]));
}
if (!$senderWalletId || !is_numeric($senderWalletId)) {
    header('Content-Type: application/json');
    die(json_encode(["error" => "sender_wallet_id is missing or invalid"]));
}
if (!$amount || !is_numeric($amount) || $amount <= 0) {
    header('Content-Type: application/json');
    die(json_encode(["error" => "amount is missing or invalid"]));
}

// Check if $pdo is set
if (!isset($pdo) || !$pdo instanceof PDO) {
    header('Content-Type: application/json');
    die(json_encode(["error" => "Database connection error"]));
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // --- Resolve Receiver ID by Email ---
    $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->execute([$receiverIdentifier]);
    $receiverUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receiverUser) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        die(json_encode(["error" => "Receiver not found"]));
    }
    $receiverId = $receiverUser["id"];

    // --- Get Sender ID from senderWalletId ---
    $stmt = $pdo->prepare("SELECT user_id FROM Wallets WHERE id = ?");
    $stmt->execute([$senderWalletId]);
    $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$senderWallet) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        die(json_encode(["error" => "Sender wallet not found"]));
    }
    $senderId = $senderWallet["user_id"];

    if ($senderId == $receiverId) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        die(json_encode(["error" => "Cannot transfer to yourself"]));
    }

    // --- Check Sender Balance ---
    $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$senderWalletId, $senderId]);
    $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$senderWallet || $senderWallet["balance"] < $amount) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        die(json_encode(["error" => "Insufficient balance"]));
    }

    // --- Deduct from Sender ---
    $newSenderBalance = $senderWallet["balance"] - $amount;
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newSenderBalance, $senderWalletId, $senderId]);

    // --- Get Receiver's Default Wallet ---
    $stmt = $pdo->prepare("SELECT id FROM Wallets WHERE user_id = ? AND is_default = 1");
    $stmt->execute([$receiverId]);
    $receiverWallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receiverWallet) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        die(json_encode(["error" => "Receiver's default wallet not found"]));
    }
    $receiverWalletId = $receiverWallet["id"];

    // --- Transfer to Receiver ---
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$amount, $receiverWalletId, $receiverId]);

    if ($stmt->rowCount() == 0) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        die(json_encode(["error" => "Receiver wallet not found"]));
    }

    // --- Record Transaction ---
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp)
                          VALUES (?, ?, 'p2p', ?, 'completed', NOW())");
    $stmt->execute([$senderId, $senderWalletId, $amount]);
    $transactionId = $pdo->lastInsertId();
    
    // --- Record P2P Transfer ---
    $stmt = $pdo->prepare("INSERT INTO P2P_Transfers (transaction_id, sender_id, receiver_id, type) VALUES (?, ?, ?, 'p2p')");
    $stmt->execute([$transactionId, $senderId, $receiverId]);
    $p2pTransferId = $pdo->lastInsertId();
    
    // Generate Unique Transaction Code
    $transactionCode = "P2P-" . crc32($transactionId . $senderId . $receiverId);
    $stmt = $pdo->prepare("UPDATE P2P_Transfers SET transaction_code = ? WHERE id = ?");
    $stmt->execute([$transactionCode, $p2pTransferId]);

    if ($transactionId) {
        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Transfer successful!"]);
    } else {
        $pdo->rollBack();
        error_log("P2P Transfer Error: Failed to record transaction.");
        header('Content-Type: application/json');
        die(json_encode(["error" => "Transaction failed"]));
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("P2P Transfer Error: " . $e->getMessage());
    header('Content-Type: application/json');
    die(json_encode(["error" => "Transfer error: " . $e->getMessage()]));
}
?>
