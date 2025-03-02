<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://192.168.137.1");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db_connection.php';

$response = [];  // Initialize response array

// Handle both GET and POST requests
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

$qrCodeId = $_GET["data"] ?? $data["data"] ?? null;  // Support both GET and POST

// Validate required fields
if (!$qrCodeId || !is_numeric($qrCodeId)) {
    $response = ["error" => "QR Code ID is missing or invalid"];
    echo json_encode($response);
    exit;
}

try {
    // Prepare and execute the SQL query
    $stmt = $pdo->prepare("SELECT user_id, wallet_id, amount FROM QRCodes WHERE id = ?");
    $stmt->execute([$qrCodeId]);

    // Fetch the data
    $qrCodeData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($qrCodeData) {
        $response = ["success" => true, "qr_code" => $qrCodeData];
    } else {
        $response = ["error" => "QR Code not found"];
    }
} catch (PDOException $e) {
    error_log("QR Code fetching error: " . $e->getMessage());
    $response = ["error" => "QR Code fetching failed: " . $e->getMessage()];
}

// Send the JSON response once
echo json_encode($response);
exit;
?>
