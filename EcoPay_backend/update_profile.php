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

$profilePicDir = 'C:/xampp/htdocs/Project_EcoPay/EcoPay_backend/uploads/profile_pics/';
$idDocumentDir = 'C:/xampp/htdocs/Project_EcoPay/EcoPay_backend/uploads/id_documents/';

// Ensure directories exist
if (!is_dir($profilePicDir)) {
    mkdir($profilePicDir, 0775, true);
}
if (!is_dir($idDocumentDir)) {
    mkdir($idDocumentDir, 0775, true);
}

try {
    $address = $_POST['address'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $profilePicPath = null;

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_pic'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid profile picture type. Only JPG, PNG, and GIF are allowed.');
        }
        $uniqueName = uniqid('profile_pic_', true) . '-' . basename($file['name']);
        $profilePicPath = 'uploads/profile_pics/' . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $profilePicDir . $uniqueName)) {
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
        $uniqueName = uniqid('id_doc_', true) . '-' . basename($file['name']);
        $idDocumentLink = 'uploads/id_documents/' . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $idDocumentDir . $uniqueName)) {
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
    $stmt = $pdo->prepare("
        SELECT Users.name, Users.email, Wallets.balance
        FROM Users
        INNER JOIN Wallets ON Users.id = Wallets.user_id
        WHERE Users.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $response_data = [
        'status' => 'success',
        'message' => 'Profile updated successfully.',
        'user' => $user
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