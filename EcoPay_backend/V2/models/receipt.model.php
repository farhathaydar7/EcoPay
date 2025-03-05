<?php
require '/var/www/html/EcoPay/vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Receipt {
    private $pdo;
    private $smtpUsername;
    private $smtpPassword;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        global $smtpUsername, $smtpPassword;
        $this->smtpUsername = $smtpUsername;
        $this->smtpPassword = $smtpPassword;
    }

    /**
     * Creates a receipt, stores it in the database, and sends an email.
     */
    public function createReceipt($transactionType, $userId, $walletId, $amount, $transactionId, $extraData = []) {
        try {
            if (!$transactionId || !$userId || !$walletId || $amount <= 0) {
                error_log("Invalid receipt data: transactionId=$transactionId, userId=$userId, walletId=$walletId, amount=$amount");
                return false;
            }

            $timestamp = date("Y-m-d H:i:s");
            $extraDataJson = json_encode($extraData);
            if ($extraDataJson === false) {
                error_log("JSON encoding error for extraData: " . json_last_error_msg());
                return false;
            }

            // Insert into database
            $stmt = $this->pdo->prepare("
                INSERT INTO Receipts (transaction_id, user_id, wallet_id, transaction_type, amount, extra_data, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $transactionId, 
                $userId, 
                $walletId, 
                $transactionType, 
                $amount, 
                $extraDataJson, 
                $timestamp
            ]);

            if (!$result) {
                error_log("Failed to insert receipt: " . print_r($stmt->errorInfo(), true));
                return false;
            }

            // Send email
            $this->sendEmails($transactionType, $userId, $amount, $transactionId, $timestamp, $extraData);
            
            return [
                "date" => $timestamp,
                "transaction_type" => ucfirst($transactionType),
                "user_id" => $userId,
                "wallet_id" => $walletId,
                "amount" => number_format($amount, 2),
                "transaction_id" => $transactionId,
                "extra_data" => $extraData
            ];
        } catch (PDOException $e) {
            error_log("Receipt creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handles email sending for single and p2p transactions.
     */
    private function sendEmails($transactionType, $userId, $amount, $transactionId, $timestamp, $extraData) {
        try {
            $userEmail = $this->getUserEmail($userId);
            if ($userEmail) {
                $this->sendReceiptEmail($userEmail, $transactionType, $amount, $transactionId, $timestamp);
            }

            if ($transactionType === 'p2p' && isset($extraData['receiver_id'])) {
                $receiverEmail = $this->getUserEmail($extraData['receiver_id']);
                if ($receiverEmail) {
                    $this->sendReceiptEmail($receiverEmail, $transactionType, $amount, $transactionId, $timestamp);
                }
            }
        } catch (Exception $e) {
            error_log("Error sending emails: " . $e->getMessage());
        }
    }

    /**
     * Retrieves user email from database.
     */
    private function getUserEmail($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT email FROM Users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['email'] : null;
        } catch (PDOException $e) {
            error_log("Failed to fetch user email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sends a receipt email using PHPMailer.
     */
    private function sendReceiptEmail($email, $transactionType, $amount, $transactionId, $timestamp) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUsername;
            $mail->Password   = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($this->smtpUsername, 'EcoPay');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Transaction Receipt from EcoPay';
            $mail->Body    = "
                <h3>Transaction Receipt</h3>
                <p><strong>Transaction Type:</strong> " . ucfirst($transactionType) . "</p>
                <p><strong>Amount:</strong> $" . number_format($amount, 2) . "</p>
                <p><strong>Transaction ID:</strong> $transactionId</p>
                <p><strong>Date:</strong> $timestamp</p>
                <br><p>Thank you for using EcoPay.</p>
            ";

            $mail->send();
            error_log("Receipt email sent successfully to " . $email);
        } catch (Exception $e) {
            error_log("PHPMailer Exception: " . $e->getMessage());
        }
    }
}
?>
