<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'db_connection.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$userId = $_SESSION["user_id"];

$profilePicDir = 'C:/xampp/htdocs/Project_EcoPay/EcoPay_backend/uploads/profile_pics/';
$idDocumentDir = 'C:/xampp/htdocs/Project_EcoPay/EcoPay_backend/uploads/id_documents/';

// Ensure directories exist
if (!is_dir($profilePicDir)) {
    mkdir($profilePicDir, 0775, true);
}
if (!is_dir($idDocumentDir)) {
    mkdir($idDocumentDir, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $address = $_POST['address'] ?? null;
        $dob = $_POST['dob'] ?? null;

        $profilePicPath = null;
        $idDocumentPath = null;

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid profile picture type. Only JPG, PNG, and GIF are allowed.');
            }
            $uniqueName = uniqid('profile_pic_', true) . '-' . basename($file['name']);
            $profilePicPath = 'uploads/profile_pics/' . $uniqueName; // Relative path for DB
            if (!move_uploaded_file($file['tmp_name'], $profilePicDir . $uniqueName)) {
                throw new Exception('Failed to move uploaded profile picture.');
            }
        }

        // Handle ID document upload
        if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['id_document'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']; // Added PDF
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid ID document type. Only JPG, PNG, GIF, and PDF are allowed.');
            }
            $uniqueName = uniqid('id_doc_', true) . '-' . basename($file['name']);
            $idDocumentPath = 'uploads/id_documents/' . $uniqueName; // Relative path for DB
            if (!move_uploaded_file($file['tmp_name'], $idDocumentDir . $uniqueName)) {
                throw new Exception('Failed to move uploaded ID document.');
            }
        }

        // Update user profile in database
        $stmt = $pdo->prepare("INSERT INTO UserProfiles (user_id, address, dob, profile_pic, id_document) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE address = VALUES(address), dob = VALUES(dob), profile_pic = VALUES(profile_pic), id_document = VALUES(id_document)");
        $stmt->execute([$userId, $address, $dob, $profilePicPath, $idDocumentPath]);

        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. POST required.']);
}
?>