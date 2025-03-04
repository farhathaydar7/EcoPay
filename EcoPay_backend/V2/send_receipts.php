<?php
require '../vendor/autoload.php';
require_once 'db_connection.php';
require_once '../V2/models/receipt.model.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['transaction_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email and Transaction ID are required.']);
    exit;
}

$email = trim($data['email']);
$transactionId = intval($data['transaction_id']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

try {
    // Fetch user ID
    $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        exit;
    }

    $userId = $user['id'];

    // Generate the receipt
    $receipt = new Receipt($pdo);
    $receiptData = $receipt->createReceipt("deposit", $userId, 1, 100.00, $transactionId);

    if (!$receiptData) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to generate receipt.']);
        exit;
    }

    // Format receipt for email
    $receiptBody = "
        <h2>Transaction Receipt</h2>
        <p><strong>Date:</strong> {$receiptData['date']}</p>
        <p><strong>Transaction Type:</strong> {$receiptData['transaction_type']}</p>
        <p><strong>User ID:</strong> {$receiptData['user_id']}</p>
        <p><strong>Wallet ID:</strong> {$receiptData['wallet_id']}</p>
        <p><strong>Amount:</strong> {$receiptData['amount']} USD</p>
        <p><strong>Transaction ID:</strong> {$receiptData['transaction_id']}</p>
    ";

    // Send email
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
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your Transaction Receipt';
        $mail->Body    = $receiptBody;

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Receipt sent to email.']);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
