<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    $response_data = ['status' => 'error', 'message' => 'User not logged in.'];
    echo json_encode($response_data);
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $response_data = ['status' => 'error', 'message' => 'Invalid request method. GET required.'];
    echo json_encode($response_data);
    exit;
}

$userId = $_SESSION["user_id"];

// Super Verification Check
$response_data = [];

try {
    // Fetch balance from the first wallet (wallet_number = 1) and verification status
    $stmt = $pdo->prepare("
        SELECT Users.name, Users.email, Wallets.balance,
               UserProfiles.address, UserProfiles.dob, UserProfiles.profile_pic,
               VerificationStatuses.document_verified
        FROM Users
        INNER JOIN Wallets ON Users.id = Wallets.user_id AND Wallets.wallet_number = 1
        LEFT JOIN UserProfiles ON Users.id = UserProfiles.user_id
        LEFT JOIN VerificationStatuses ON Users.id = VerificationStatuses.user_id
        WHERE Users.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $response_data = ['status' => 'success', 'user' => $user];
    } else {
        $response_data = ['status' => 'error', 'message' => 'User profile not found.'];
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log('PDOException in profile.php: ' . $e->getMessage() . ', User ID: ' . $userId);
    $response_data = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
}

echo json_encode($response_data);
exit;