<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$response = [];

if (!isset($data['otp'], $data['email'])) {
    $response = ['status' => 'error', 'message' => 'Missing required fields.'];
    echo json_encode($response);
    exit;
}

$otp = trim($data['otp']);
$email = trim($data['email']);

// Basic input validation
if (empty($otp) || empty($email)) {
    $response = ['status' => 'error', 'message' => 'OTP and email are required.'];
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, otp, otp_expiry, activatedAcc FROM Users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $response = ['status' => 'error', 'message' => 'User not found.'];
        echo json_encode($response);
        exit;
    }

    if ($user['activatedAcc'] == 1) {
        $response = ['status' => 'success', 'message' => 'Account already activated.'];
        echo json_encode($response);
        exit;
    }

    if (strtotime($user['otp_expiry']) < time()) {
        $response = ['status' => 'error', 'message' => 'OTP has expired.'];
        echo json_encode($response);
        exit;
    }

    if ($otp !== $user['otp']) {
        $response = ['status' => 'error', 'message' => 'Invalid OTP.'];
        echo json_encode($response);
        exit;
    }

    // Activate the account
    $stmt = $pdo->prepare("UPDATE Users SET activatedAcc = 1, otp = NULL, otp_expiry = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Update email_verified status in VerificationStatuses table
    $stmt = $pdo->prepare("UPDATE VerificationStatuses SET email_verified = 1 WHERE user_id = ?");
    $stmt->execute([$user['id']]);

    $response = ['status' => 'success', 'message' => 'Account activated successfully.'];
    echo json_encode($response);

} catch (PDOException $e) {
    $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    echo json_encode($response);
}
