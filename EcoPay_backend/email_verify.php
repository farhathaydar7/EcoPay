<?php
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';

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
    $stmt = $pdo->prepare("SELECT id, fName, lName FROM Users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        exit;
    }
    $userId = $user['id'];
    $fname = $user['fName'];
    $lname = $user['lName'];

    // Generate new OTP
    $otp = rand(100000, 999999);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Update OTP and expiry in database
    $stmt = $pdo->prepare("UPDATE Users SET otp = ?, otp_expiry = ? WHERE id = ?");
    $stmt->execute([$otp, $otp_expiry, $userId]);

    // Send OTP email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'haydarfarhat70pubg@gmail.com'; 
        $mail->Password   = 'bfsmnvmzjnqqwfin'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('haydarfarhat70pubg@gmail.com', 'EcoPay'); 
        $mail->addAddress($email, "$fname $lname");
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
