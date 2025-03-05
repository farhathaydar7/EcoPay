<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php'; // Ensure this file connects to your database

// Read JSON input
$input = json_decode(file_get_contents("php://input"), true);

// Validate required parameters
if (!isset($input['sender_wallet_id'], $input['qr_code_id'], $input['amount'], $input['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
    exit;
}

$sender_wallet_id = intval($input['sender_wallet_id']);
$qr_code_id = intval($input['qr_code_id']);
$amount = floatval($input['amount']);
$user_id = intval($input['user_id']);

if ($amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid amount."]);
    exit;
}

// Check if sender's wallet exists and get balance
$query = "SELECT balance FROM wallets WHERE wallet_id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $sender_wallet_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Wallet not found."]);
    exit;
}
$wallet = $result->fetch_assoc();
$sender_balance = floatval($wallet['balance']);
$stmt->close();

// Check if sender has enough balance
if ($sender_balance < $amount) {
    echo json_encode(["status" => "error", "message" => "Insufficient balance."]);
    exit;
}

// Get recipient wallet ID from QR Code
$query = "SELECT recipient_wallet_id FROM qr_codes WHERE qr_code_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $qr_code_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid QR Code."]);
    exit;
}
$qr_data = $result->fetch_assoc();
$recipient_wallet_id = intval($qr_data['recipient_wallet_id']);
$stmt->close();

// Deduct amount from sender
$query = "UPDATE wallets SET balance = balance - ? WHERE wallet_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("di", $amount, $sender_wallet_id);
$stmt->execute();
$stmt->close();

// Add amount to recipient
$query = "UPDATE wallets SET balance = balance + ? WHERE wallet_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("di", $amount, $recipient_wallet_id);
$stmt->execute();
$stmt->close();

// Log transaction
$query = "INSERT INTO transactions (sender_wallet_id, recipient_wallet_id, amount, transaction_date) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("iid", $sender_wallet_id, $recipient_wallet_id, $amount);
$stmt->execute();
$stmt->close();

// Return success response
echo json_encode(["status" => "success", "message" => "Transaction successful!"]);
exit;
