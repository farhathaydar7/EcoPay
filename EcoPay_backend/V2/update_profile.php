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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response_data = ['status' => 'error', 'message' => 'Invalid request method. POST required.'];
    echo json_encode($response_data);
    exit;
}

$userId = $_SESSION["user_id"];
$response_data = [];

$uploadsDir = '/var/www/html/EcoPay/uploads/';

// Ensure directories exist
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0775, true);
}

try {
    $address = $_POST['address'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $profilePicPath = null;

    // Validate address and DOB
    if ($address && strlen($address) > 255) {
        throw new Exception('Address is too long.');
    }
    if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD.');
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_pic'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid profile picture type. Only JPG, PNG, and GIF are allowed.');
        }
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxFileSize) {
            throw new Exception('Profile picture size exceeds the limit of 2MB.');
        }
        $uniqueName = uniqid('profile_pic_', true) . '-' . basename($file['name']);
        $profilePicPath = '/' . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $uniqueName)) {
            throw new Exception('Failed to move uploaded profile picture.');
        }
    }

    // Handle ID document upload
    if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['id_document'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid ID document type. Only JPG, PNG, GIF, and PDF are allowed.');
        }
         $maxFileSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxFileSize) {
            throw new Exception('ID document size exceeds the limit of 5MB.');
        }
        $uniqueName = uniqid('id_doc_', true) . '-' . basename($file['name']);
        $idDocumentLink = '/' . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $uniqueName)) {
            throw new Exception('Failed to move uploaded ID document.');
        }
        $stmt = $pdo->prepare("INSERT INTO IDDocuments (user_id, link) VALUES (?, ?)");
        $stmt->execute([$userId, $idDocumentLink]);
    }

    // Update user profile in database
    $stmt = $pdo->prepare("
        INSERT INTO UserProfiles (user_id, address, dob, profile_pic)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            address = VALUES(address),
            dob = VALUES(dob),
            profile_pic = VALUES(profile_pic)
    ");
    $stmt->execute([$userId, $address, $dob, $profilePicPath]);

    // Fetch updated user data **only once**
    // Fetch updated user data **only once**
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
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $response_data = [
        'status' => 'success',
        'message' => 'Profile updated successfully.',
        'user' => $user,
    ];

} catch (Exception $e) {
    http_response_code(400);
    $response_data = ['status' => 'error', 'message' => $e->getMessage()];
} catch (PDOException $e) {
    http_response_code(500);
    error_log('PDOException in update_profile.php: ' . $e->getMessage() . ', User ID: ' . $userId);
    $response_data = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
}

echo json_encode($response_data);
exit;