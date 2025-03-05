<?php
require '/var/www/html/EcoPay/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';
require_once 'User.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
    exit;
}

$email = trim($data['email']);

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email cannot be empty.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}
try {
    $user = User::getByEmail($email, $pdo);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        exit;
    }

    // Generate new OTP
    $otp = rand(100000, 999999);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Update OTP and expiry in database
    $user->otp = $otp;
    $user->otp_expiry = $otp_expiry;
    if (!$user->update()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update OTP.']);
        exit;
    }

    // Send OTP email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($smtpUsername, 'EcoPay');
        $mail->addAddress($email, "{$user->fName} {$user->lName}");
        $mail->isHTML(true);
        $mail->Subject = 'Your EcoPay OTP';
        $emailBody = 'Your new OTP is: <b>'.$otp.'</b>. It expires in 15 minutes.';
        $mail->Body    = $emailBody;

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'New OTP sent to your email.']);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
