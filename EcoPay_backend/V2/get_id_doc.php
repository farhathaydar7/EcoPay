<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

require_once 'db_connection.php';
require_once 'IDDocument.php';

try {
    $idDocument = IDDocument::getByUserId($userId, $pdo);

    if ($idDocument) {
        echo json_encode(['status' => 'success', 'id_document' => $idDocument->link]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No ID document found for this user']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
