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

// Include your database connection file
require_once 'db_connection.php';

try {
    // Read and decode the incoming JSON request
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);
    
    if ($data === null) {
        http_response_code(400);
        die(json_encode(["error" => "Invalid JSON format."]));
    }
    
    // Validate required fields
    $qr_code_id = $data['qr_code_id'] ?? null;
    $sender_wallet_id = $data['sender_wallet_id'] ?? null;
    
    if (!$qr_code_id) {
        http_response_code(400);
        die(json_encode(["error" => "QR code ID is missing."]));
    }
    if (!$sender_wallet_id) {
        http_response_code(400);
        die(json_encode(["error" => "Sender wallet ID is missing."]));
    }
    
    // Begin database transaction
    $pdo->beginTransaction();
    
    // Retrieve the QR code record (only pending QR codes can be used)
    $stmt = $pdo->prepare("SELECT * FROM QRCodes WHERE id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$qr_code_id]);
    $qrCode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$qrCode) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Invalid or already used QR code."]));
    }
    
    // Extract details from the QR code
    $receiver_id = $qrCode['user_id'];      // QR creator (receiver)
    $qr_amount   = floatval($qrCode['amount']);
    
    // Retrieve the sender's wallet details
    $stmt = $pdo->prepare("SELECT * FROM Wallets WHERE id = ?");
    $stmt->execute([$sender_wallet_id]);
    $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$senderWallet) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Sender wallet not found."]));
    }
    
    $sender_id = $senderWallet['user_id'];
    
    // Prevent self-transfer
    if ($sender_id == $receiver_id) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Cannot transfer to yourself."]));
    }
    
    // Check if the sender has sufficient funds
    if (floatval($senderWallet['balance']) < $qr_amount) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Insufficient balance."]));
    }
    
    // Deduct the amount from the sender's wallet
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$qr_amount, $sender_wallet_id]);
    
    // Get the receiver's default wallet
    $stmt = $pdo->prepare("SELECT id FROM Wallets WHERE user_id = ? AND is_default = 1 LIMIT 1");
    $stmt->execute([$receiver_id]);
    $receiverWallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiverWallet) {
        $pdo->rollBack();
        http_response_code(400);
        die(json_encode(["error" => "Receiver's default wallet not found."]));
    }
    $receiver_wallet_id = $receiverWallet['id'];
    
    // Credit the amount to the receiver's wallet
    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$qr_amount, $receiver_wallet_id]);
    
    // Record the transaction
    $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp)
                           VALUES (?, ?, 'qr_transfer', ?, 'completed', NOW())");
    $stmt->execute([$sender_id, $sender_wallet_id, $qr_amount]);
    $transaction_id = $pdo->lastInsertId();
    if (!$transaction_id) {
        $pdo->rollBack();
        http_response_code(500);
        die(json_encode(["error" => "Failed to record transaction."]));
    }
    
    // Record the P2P transfer for the QR transfer
    $stmt = $pdo->prepare("INSERT INTO P2P_Transfers (transaction_id, sender_id, receiver_id)
                           VALUES (?, ?, ?)");
    $stmt->execute([$transaction_id, $sender_id, $receiver_id]);
    
    // Mark the QR code as used by updating its status and recording the sender's id
    $stmt = $pdo->prepare("UPDATE QRCodes SET status = 'completed', sender_id = ? WHERE id = ?");
    $stmt->execute([$sender_id, $qr_code_id]);
    
    // Commit the transaction
    $pdo->commit();
    
    // Return a success response
    echo json_encode([
        "status" => "success",
        "message" => "QR transfer successful.",
        "transaction_id" => $transaction_id
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
}
?>
