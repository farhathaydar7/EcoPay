<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';
require_once '/var/www/html/EcoPay/EcoPay_backend/V2/models/receipt.model.php'; // Include Receipt model
require_once 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if ($data === null) {
    die(json_encode(["error" => "Invalid JSON format"]));
}

$action = $data["action"] ?? null;
if (!$action) {
    die(json_encode(["error" => "Missing action parameter."]));
}

if (!isset($pdo) || !$pdo instanceof PDO) {
    die(json_encode(["error" => "Database connection error"]));
}

try {
    if ($action === 'p2pTransfer') {
        // Extract and validate common inputs
        $senderWalletId = $data["sender_wallet_id"] ?? null;
        $amount = $data["amount"] ?? null;
        
        if (!$senderWalletId || !is_numeric($senderWalletId)) {
            die(json_encode(["error" => "Invalid sender wallet ID"]));
        }
        if (!$amount || !is_numeric($amount) || $amount <= 0) {
            die(json_encode(["error" => "Invalid transfer amount"]));
        }
        
        $amount = floatval($amount);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get sender details
        $stmt = $pdo->prepare("SELECT user_id, balance FROM Wallets WHERE id = ?");
        $stmt->execute([$senderWalletId]);
        $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$senderWallet) {
            $pdo->rollBack();
            die(json_encode(["error" => "Sender wallet not found"]));
        }
        $senderId = $senderWallet["user_id"];
        
        // Determine receiver method: by QR code or by receiver email.
        $receiverId = null;
        $receiverWalletId = null;
        
        // Option 1: Using QR Code
        if (isset($data["qr_code_id"]) && is_numeric($data["qr_code_id"]) && $data["qr_code_id"] > 0) {
            $qrCodeId = $data["qr_code_id"];
            
            // Get receiver ID from QR code
            $stmt = $pdo->prepare("SELECT user_id FROM QRCodes WHERE id = ? AND status = 'pending'");
            $stmt->execute([$qrCodeId]);
            $qrCodeData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$qrCodeData) {
                $pdo->rollBack();
                die(json_encode(["error" => "Invalid or already used QR code"]));
            }
            $receiverId = $qrCodeData["user_id"];
            
            // Mark QR code as used later after successful transfer.
        }
        // Option 2: Using Receiver Email
        if (isset($data["receiver_email"]) && filter_var($data["receiver_email"], FILTER_VALIDATE_EMAIL)) {
            $receiverEmail = $data["receiver_email"];
        } elseif (isset($data["receiver_identifier"]) && filter_var($data["receiver_identifier"], FILTER_VALIDATE_EMAIL)) {
            $receiverEmail = $data["receiver_identifier"];
        }
        
        if (isset($receiverEmail)) {
            // Look up receiver by email in Users table
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
            $stmt->execute([$receiverEmail]);
            $receiverData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$receiverData) {
                $pdo->rollBack();
                die(json_encode(["error" => "Receiver email not found."]));
            }
            $receiverId = $receiverData["id"];
        }
        else {
            $pdo->rollBack();
            die(json_encode(["error" => "No valid QR code or receiver email provided."]));
        }
        
        // Prevent self-transfer
        if ($senderId == $receiverId) {
            $pdo->rollBack();
            die(json_encode(["error" => "Cannot transfer to yourself"]));
        }
        
        // Check sender balance
        if ($senderWallet["balance"] < $amount) {
            $pdo->rollBack();
            die(json_encode(["error" => "Insufficient balance"]));
        }
        
        // Deduct from sender's wallet
        $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $senderWalletId]);
        
        // Get receiver's default wallet
        $stmt = $pdo->prepare("SELECT id FROM Wallets WHERE user_id = ? AND is_default = 1");
        $stmt->execute([$receiverId]);
        $receiverWallet = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$receiverWallet) {
            $pdo->rollBack();
            die(json_encode(["error" => "Receiver's default wallet not found"]));
        }
        $receiverWalletId = $receiverWallet["id"];
        
        // Add to receiver's wallet
        $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $receiverWalletId]);
        
        // Record transaction (for sender, type 'p2p')
        $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, wallet_id, type, amount, status, timestamp)
                               VALUES (?, ?, 'p2p', ?, 'completed', NOW())");
        $stmt->execute([$senderId, $senderWalletId, $amount]);
        $transactionId = $pdo->lastInsertId();
        if (!$transactionId) {
            $pdo->rollBack();
            die(json_encode(["error" => "Failed to record transaction"]));
        }
        
        // Record P2P transfer record
        $stmt = $pdo->prepare("INSERT INTO P2P_Transfers (transaction_id, sender_id, receiver_id)
                               VALUES (?, ?, ?)");
        $stmt->execute([$transactionId, $senderId, $receiverId]);
        
        // If using QR code, mark it as used
        if (isset($qrCodeId)) {
            $stmt = $pdo->prepare("UPDATE QRCodes SET status = 'completed', sender_id = ? WHERE id = ?");
            $stmt->execute([$senderId, $qrCodeId]);
        }
        
        $pdo->commit();
        
        // Create and store receipt using the Receipt model
        $receiptModel = new Receipt($pdo);
        $receiptData = $receiptModel->createReceipt(
            'p2p',
            $senderId,
            $senderWalletId,
            $amount,
            $transactionId,
            [
                "receiver_id" => $receiverId,
                "receiver_wallet_id" => $receiverWalletId,
                "method" => isset($qrCodeId) ? "QR Code" : "Receiver Email",
                "status" => "Completed"
            ]
        );
        if (!$receiptData) {
            die(json_encode(["success" => false, "message" => "Failed to generate receipt."]));
        }
        
        echo json_encode([
            "status" => "success",
            "message" => "Transfer successful!",
            "transaction_id" => $transactionId,
            "receipt" => $receiptData
        ]);
    } else {
        die(json_encode(["error" => "Invalid action provided."]));
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
