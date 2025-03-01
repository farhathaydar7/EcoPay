<?php
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';
require_once 'User.php';

$data = json_decode(file_get_contents("php://input"), true);
$response = [];

if (!isset($data['userName'], $data['fName'], $data['lName'], $data['email'], $data['password'])) {
    $response = ['status' => 'error', 'message' => 'Missing required fields.'];
    echo json_encode($response);
    exit;
}

$username = trim($data['userName']);
$fname = trim($data['fName']);
$lname = trim($data['lName']);
$email = trim($data['email']);
$password = $data['password'];


// Basic input validation
if (empty($username) || empty($fname) || empty($lname) || empty($email) || empty($password)) {
    $response = ['status' => 'error', 'message' => 'All fields are required.'];
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = ['status' => 'error', 'message' => 'Invalid email format.'];
    echo json_encode($response);
    exit;
}

// Check if username or email already exists
try {
    $stmt = $pdo->prepare("SELECT id FROM Users WHERE userName = ? OR email = ? LIMIT 1");
    $stmt->execute([$username, $email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        if ($existingUser['userName'] === $username) {
            $response = ['status' => 'error', 'message' => 'Username already taken.'];
        } else {
            $response = ['status' => 'error', 'message' => 'Email already registered.'];
        }
        echo json_encode($response);
        exit;
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Generate OTP
    $otp = rand(100000, 999999);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Create User object
    $userData = [
        'userName' => $username,
        'fName' => $fname,
        'lName' => $lname,
        'email' => $email,
        'password' => $hashedPassword,
        'otp' => $otp,
        'otp_expiry' => $otp_expiry,
        'activatedAcc' => 0 // Assuming new users are not activated
    ];
    $user = new User($userData);

    error_log("Register.php: About to start transaction for user: " . $username);
    // Start transaction
    $pdo->beginTransaction();

    // Insert user data
    $stmt = $pdo->prepare("INSERT INTO Users (userName, fName, lName, email, password, otp, otp_expiry, activatedAcc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user->userName, $user->fName, $user->lName, $user->email, $user->password, $user->otp, $user->otp_expiry, $user->activatedAcc]);
    $userId = $pdo->lastInsertId();

    // Create default wallet
    $stmt = $pdo->prepare("INSERT INTO Wallets (user_id, wallet_name, balance, currency, is_default) VALUES (?, 'Main Wallet', 0.00, 'USD', TRUE)");
    $stmt->execute([$userId]);

     $stmt = $pdo->prepare("INSERT INTO VerificationStatuses (user_id) VALUES (?)");
    $stmt->execute([$userId]);

    // Send OTP email
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUsername;
    $mail->Password   = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($smtpUsername, 'EcoPay');
    $mail->addAddress($email, "$fname $lname");
    $mail->isHTML(true);
    $mail->Subject = 'Your EcoPay OTP';
    $mail->Body    = "Your OTP is: <b>$otp</b>. It expires in 15 minutes.";

    $mail->send();
    $response = ['status' => 'success', 'message' => 'User registered successfully. OTP sent to your email.', 'user_id' => $userId];
    $pdo->commit();
    echo json_encode($response);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    } else {
        error_log("Register.php: No active transaction to rollback for user: " . $username);
    }
    $response = ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    echo json_encode($response);
}
