<?php
// Include database connection
require_once 'db_connection.php';

// Get QR code ID and sender wallet ID from the query parameters
$qr_code_id = $_GET['qr_code'] ?? null;
$sender_wallet_id = $_GET['sender_wallet_id'] ?? null; // Pass this from client

if (!$qr_code_id) {
    die(json_encode(["error" => "QR code ID missing"]));
}

if (!$sender_wallet_id) {
    die(json_encode(["error" => "Sender wallet ID missing"]));
}

// Helper function to get QR code data from your API
function get_qr_code_data($qr_code_id) {
    require_once 'db_connection.php'; 

    $url = 'http://localhost/Project_EcoPay/EcoPay_backend/V2/get_qr_code_data.php';
    $data = ['qr_code_id' => $qr_code_id];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $qr_data = json_decode($result, true);

    if (isset($qr_data['success']) && $qr_data['success'] === true && isset($qr_data['qr_code'])) {
        return $qr_data['qr_code'];
    } else {
        error_log("get_qr_code_data failed: " . print_r($qr_data, true));
        return false;
    }
}

$qr_data = get_qr_code_data($qr_code_id);

if (!$qr_data) {
    die(json_encode(["error" => "Invalid QR code"]));
}

// Extract the necessary data from the QR code record
$receiver_id = $qr_data['user_id'];         // The QR creator (receiver)
$receiver_wallet_id = $qr_data['wallet_id'];  // Wallet that should receive funds
$amount = $qr_data['amount'];

// Prepare data for the transfer request to your API
$p2p_transfer_url = 'http://localhost/Project_EcoPay/EcoPay_backend/V2/p2p_transfer.php';
$p2p_transfer_data = [
    'action'           => 'p2pTransfer',
    'receiver_identifier' => $receiver_id, // In QR transfer, receiver is the QR creator
    'sender_wallet_id' => $sender_wallet_id, // Use the wallet id passed from the client
    'amount'           => $amount,
    'qr_code_id'       => $qr_code_id
];

$p2p_transfer_options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($p2p_transfer_data)
    ]
];

$p2p_transfer_context  = stream_context_create($p2p_transfer_options);
$p2p_transfer_result = file_get_contents($p2p_transfer_url, false, $p2p_transfer_context);
$p2p_transfer_response = json_decode($p2p_transfer_result, true);

if ($p2p_transfer_response && isset($p2p_transfer_response['status']) && $p2p_transfer_response['status'] === 'success') {
    // Optionally, log the transaction in your own table
    $stmt = $pdo->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, type, status, timestamp) VALUES (?, ?, ?, 'qr_transfer', 'completed', NOW())");
    $stmt->execute([$sender_wallet_id, $receiver_id, $amount]); 

    echo json_encode(["success" => true, "message" => "QR transfer successful"]);
} else {
    error_log("p2p_transfer failed: " . print_r($p2p_transfer_response, true));
    echo json_encode(["error" => "QR transfer failed: " . ($p2p_transfer_response['message'] ?? 'Unknown error')]);
}
?>
