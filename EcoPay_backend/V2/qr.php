<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Include necessary files
require_once 'db_connection.php';
require_once '../V2/models/receipt.model.php'; // Include Receipt Model

try {
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);
    
    if ($data === null) {
        http_response_code(400);
        die(json_encode(["error" => "Invalid JSON format."]));
    }
    
    $qr_code_id = $data['qr_code_id'] ?? null;
    $sender_wallet_id = $data['sender_wallet_id'] ?? null;
    
    if (!$qr_code_id || !$sender_wallet_id) {
        http_response_code(400);
        die(json_encode(["error" => "Missing required fields."]));
    }
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("SELECT * FROM QRCodes WHERE id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$qr_code_id]);
    $qrCode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$qrCode) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Invalid or already used QR code."]));
    }
    
    $receiver_id = $qrCode['user_id'];
    $qr_amount = floatval($qrCode['amount']);
    
    $stmt = $pdo->prepare("SELECT * FROM Wallets WHERE id = ?");
    $stmt->execute([$sender_wallet_id]);
    $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$senderWallet) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Sender wallet not found."]));
    }
    
    $sender_id = $senderWallet['user_id'];
    
    if ($sender_id == $receiver_id) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Cannot transfer to yourself."]));
    }
    
    if (floatval($senderWallet['balance']) < $qr_amount) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Insufficient balance."]));
    }
    
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$qr_amount, $sender_wallet_id]);
    
    $stmt = $pdo->prepare("SELECT id FROM Wallets WHERE user_id = ? AND is_default = 1 LIMIT 1");
    $stmt->execute([$receiver_id]);
    $receiverWallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiverWallet) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Receiver's default wallet not found."]));
    }
    $receiver_wallet_id = $receiverWallet['id'];
    
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$qr_amount, $receiver_wallet_id]);
    
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp)
                           VALUES (?, ?, 'qr_transfer', ?, 'completed', NOW())");
    $stmt->execute([$sender_id, $sender_wallet_id, $qr_amount]);
    $transaction_id = $pdo->lastInsertId();
    
    if (!$transaction_id) {
        $pdo->rollBack();
        http_response_code(500);
        die(json_encode(["error" => "Failed to record transaction."]));
    }
    
    $stmt = $pdo->prepare("INSERT INTO P2P_Transfers (transaction_id, sender_id, receiver_id)
                           VALUES (?, ?, ?)");
    $stmt->execute([$transaction_id, $sender_id, $receiver_id]);
    
    $stmt = $pdo->prepare("UPDATE QRCodes SET status = 'completed', sender_id = ? WHERE id = ?");
    $stmt->execute([$sender_id, $qr_code_id]);
    
    // Add extra data (you can customize these fields as needed)
    $extraData = [
        "receiver_id" => $receiver_id,
        "status" => "Completed",
        "sender_wallet_balance_after" => $senderWallet['balance'] - $qr_amount, // Example of sender's balance after transfer
        "receiver_wallet_balance_after" => $receiverWallet['balance'] + $qr_amount, // Example of receiver's balance after transfer
        "transaction_timestamp" => date("Y-m-d H:i:s"), // Add timestamp of transaction
        "transaction_id" => $transaction_id // Including transaction ID for clarity
    ];

    // Create receipt
    $receiptModel = new Receipt($pdo);
    $receiptData = $receiptModel->createReceipt('p2p', $sender_id, $sender_wallet_id, $qr_amount, $transaction_id, $extraData);
    
    if (!$receiptData) {
        $pdo->rollBack();
        http_response_code(500);
        die(json_encode(["error" => "Failed to generate receipt."]));
    }
 
    $pdo->commit();
    
    echo json_encode([
        "status" => "success",
        "message" => "QR transfer successful.",
        "transaction_id" => $transaction_id,
        "receipt" => $receiptData
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
}
?>
