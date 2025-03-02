<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';

// Read and decode JSON input
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Check if data is received properly
if ($data === null) {
    die(json_encode(["error" => "Invalid JSON format"]));
}

// Extract variables safely
$userId = $data["user_id"] ?? null;
$walletId = $data["wallet_id"] ?? null;
$amount = $data["amount"] ?? null;

// Validate required fields
if (!$userId || !is_numeric($userId)) {
    die(json_encode(["error" => "user_id is missing or invalid"]));
}
if (!$walletId || !is_numeric($walletId)) {
    die(json_encode(["error" => "wallet_id is missing or invalid"]));
}
if (!$amount || !is_numeric($amount) || $amount <= 0) {
    die(json_encode(["error" => "amount is missing or invalid"]));
}

try {
    // Prepare and execute the SQL query
    $stmt = $pdo->prepare("INSERT INTO QRCodes (user_id, wallet_id, amount) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $walletId, $amount]);

    // Get the last inserted ID
    $qrCodeId = $pdo->lastInsertId();

    // Return the ID as a JSON response
    echo json_encode(["success" => true, "qr_code_id" => $qrCodeId]);
} catch (PDOException $e) {
    error_log("QR Code creation error: " . $e->getMessage());
    die(json_encode(["error" => "QR Code creation failed: " . $e->getMessage()]));
}
?>