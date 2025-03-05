<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Check if this request is for QR transfer (expects session user_id)
if (isset($_GET['qr']) && $_GET['qr'] == "true") {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['userId' => $_SESSION['user_id']]);
    } else {
        echo json_encode(['error' => 'User not logged in']);
    }
    exit;
}

// If request is for email transfer, get user ID using email
if (isset($_GET['email'])) {
    $email = $_GET['email'];

    // Connect to database
    $conn = new mysqli("localhost", "root", "", "your_database_name");
    
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed']));
    }

    // Find user ID by email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    if (!empty($userId)) {
        echo json_encode(['userId' => $userId]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
    exit;
}

// If no valid request type is provided
echo json_encode(['error' => 'Invalid request']);
?>
