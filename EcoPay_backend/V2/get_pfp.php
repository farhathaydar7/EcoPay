<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';
require_once 'UserProfile.php';

session_start();

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$userId = $_SESSION["user_id"];

try {
    $userProfile = UserProfile::getByUserId($userId, $pdo);

    if ($userProfile && $userProfile->profile_pic) {
        echo json_encode(['status' => 'success', 'profile_pic_path' => $userProfile->profile_pic]);
    } else {
        echo json_encode(['status' => 'success', 'profile_pic_path' => null]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
