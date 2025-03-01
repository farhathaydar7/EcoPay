<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

if (isset($_SESSION['user_id'])) {
    echo json_encode(['userId' => $_SESSION['user_id']]);
} else {
    echo json_encode(['error' => 'User not logged in']);
}
?>