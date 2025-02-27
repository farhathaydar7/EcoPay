<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$response = [];

if (!isset($data['name'], $data['email'], $data['phone'], $data['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$password = password_hash($data['password'], PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO Users (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $password]);
    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO UserProfiles (user_id) VALUES (?)");
    $stmt->execute([$userId]);

    $stmt = $pdo->prepare("INSERT INTO Wallets (user_id, wallet_number, balance, currency) VALUES (?, 1, 0.00, 'USD')");
    $stmt->execute([$userId]);

    $stmt = $pdo->prepare("INSERT INTO VerificationStatuses (user_id, email_verified, document_verified, super_verified) VALUES (?, FALSE, FALSE, FALSE)");
    $stmt->execute([$userId]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'User registered successfully.', 'user_id' => $userId]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Registration error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
