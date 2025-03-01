<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

require_once 'config.php';

// Create connection
$conn = new mysqli($host, $username, $password, $dbName);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Prepare SQL query to fetch ID document link
$sql = "SELECT link FROM IDDocuments WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the ID document link
    $row = $result->fetch_assoc();
    $idDocumentLink = $row['link'];

    echo json_encode(['status' => 'success', 'id_document' => $idDocumentLink]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No ID document found for this user']);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>