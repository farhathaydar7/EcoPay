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
    // Fetch user info and verification status
    $stmt = $pdo->prepare("
        SELECT
            Users.userName,
            Users.fName,
            Users.lName,
            Users.email,
            UserProfiles.address,
            UserProfiles.dob,
            UserProfiles.profile_pic,
            VerificationStatuses.document_verified,
            VerificationStatuses.super_verified
        FROM
            Users
        LEFT JOIN
            UserProfiles ON Users.id = UserProfiles.user_id
        LEFT JOIN
            VerificationStatuses ON Users.id = VerificationStatuses.user_id
        WHERE
            Users.id = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Structure the data
        $userData = [
            'userName' => $result['userName'],
            'fName' => $result['fName'],
            'lName' => $result['lName'],
            'email' => $result['email'],
            'address' => $result['address'],
            'dob' => $result['dob'],
            'profile_pic' => $result['profile_pic'],
            'document_verified' => $result['document_verified'],
            'super_verified' => $result['super_verified'],
            'name' => $result['fName'] . ' ' . $result['lName']
        ];

        $response_data = [
            'status' => 'success',
            'user' => $userData
        ];
    } else {
        $response_data = ['status' => 'error', 'message' => 'User profile not found.'];
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log('PDOException in profile.php: ' . $e->getMessage() . ', User ID: ' . $userId);
    $response_data = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
} finally {
    echo json_encode($response_data);
    exit;
}
