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
$qrCodeId = $data["qr_code_id"] ?? null;

// Validate required fields
if (!$qrCodeId || !is_numeric($qrCodeId)) {
    die(json_encode(["error" => "qr_code_id is missing or invalid"]));
}

try {
    // Prepare and execute the SQL query
    $stmt = $pdo->prepare("SELECT user_id, wallet_id, amount FROM QRCodes WHERE id = ?");
    $stmt->execute([$qrCodeId]);

    // Fetch the data
    $qrCodeData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($qrCodeData) {
        // Return the data as a JSON response
        echo json_encode(["success" => true, "qr_code" => $qrCodeData]);
    } else {
        die(json_encode(["error" => "QR Code not found"]));
    }
} catch (PDOException $e) {
    error_log("QR Code fetching error: " . $e->getMessage());
    die(json_encode(["error" => "QR Code fetching failed: " . $e->getMessage()]));
}
?>