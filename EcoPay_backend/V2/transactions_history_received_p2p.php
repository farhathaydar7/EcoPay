<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';

session_start(); // Start session if using authentication

// Check for user_id from session (recommended) or GET request (fallback)
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["error" => "User ID is required"], JSON_PRETTY_PRINT);
    exit;
}

$query = "SELECT p2p.transaction_id, p2p.sender_id, p2p.receiver_id, 
                 t.type, t.amount, t.status, t.timestamp, 
                 u.fName, u.lName, u.email AS sender_email
          FROM P2P_Transfers p2p
          JOIN Transactions t ON p2p.transaction_id = t.id
          JOIN Users u ON p2p.sender_id = u.id
          WHERE p2p.receiver_id = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$receivedP2pTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
echo json_encode($receivedP2pTransactions, JSON_PRETTY_PRINT);
exit;
