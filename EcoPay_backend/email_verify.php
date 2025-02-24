<?php
require_once 'db_connection.php';

function generateDummyOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= rand(0, 9);
    }
    return $otp;
}

// Empty mail function for now 
function sendEmail($to, $subject, $message, $headers) {
 
    return true;
}

session_start();

if (!isset($_SESSION["user_id"])) {
    echo "User not logged in. Cannot verify email.";
    exit;
}

$userId = $_SESSION["user_id"];


$otp = generateDummyOTP();

//otp should be stored in database
try {
    $stmt = $pdo->prepare("UPDATE Users SET verification_otp = ? WHERE id = ?");
    $stmt->execute([$otp, $userId]);
} catch (PDOException $e) {
    echo "Error storing OTP: " . $e->getMessage();
    exit;
}

//SMTP variables
$to = "user@example.com"; // Replace with mail from db
$subject = "Email Verification OTP";
$message = "Your OTP is: " . $otp;
$headers = "From: noreply@ecopay.com"; // Replace with my mail from aws


if (sendEmail($to, $subject, $message, $headers)) {
    echo "Dummy email sent with OTP.";
} else {
    echo "Failed to send dummy email.";
}

?>