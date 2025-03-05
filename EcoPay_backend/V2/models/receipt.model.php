<?php
require_once __DIR__ . '/../../../bin/vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Receipt {
    private $pdo;
    private $smtpUsername;
    private $smtpPassword;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Use the global variables defined in config.php
        global $smtpUsername, $smtpPassword;
        $this->smtpUsername = $smtpUsername;
        $this->smtpPassword = $smtpPassword;
    }

    /**
     * Creates a receipt for a transaction, stores it in the database,
     * retrieves the user's email(s), and sends receipt email(s).
     *
     * For p2p transactions, both the sender and the receiver (if provided in extraData) receive an email.
     */
    public function createReceipt($transactionType, $userId, $walletId, $amount, $transactionId, $extraData = []) {
        try {
            $timestamp = date("Y-m-d H:i:s");
            $extraDataJson = json_encode($extraData);

            // Insert receipt into the Receipts table
            $stmt = $this->pdo->prepare("
                INSERT INTO Receipts (transaction_id, user_id, wallet_id, transaction_type, amount, extra_data, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $transactionId, 
                $userId, 
                $walletId, 
                $transactionType, 
                $amount, 
                $extraDataJson, 
                $timestamp
            ]);

            if ($stmt->rowCount() > 0) {
                if ($transactionType === 'p2p') {
                    // For p2p transactions, send receipt to both parties.
                    
                    // Send email to sender
                    $senderEmail = $this->getUserEmail($userId);
                    error_log("Retrieved sender email: " . $senderEmail);
                    if ($senderEmail) {
                        $this->sendReceiptEmail($senderEmail, $transactionType, $amount, $transactionId, $timestamp);
                    }
                    
                    // Send email to receiver if provided in extraData (key: receiver_id)
                    if (isset($extraData['receiver_id'])) {
                        $receiverEmail = $this->getUserEmail($extraData['receiver_id']);
                        error_log("Retrieved receiver email: " . $receiverEmail);
                        if ($receiverEmail) {
                            $this->sendReceiptEmail($receiverEmail, $transactionType, $amount, $transactionId, $timestamp);
                        }
                    }
                } else {
                    // For other transaction types, send email to the single user.
                    $userEmail = $this->getUserEmail($userId);
                    error_log("Retrieved user email: " . $userEmail);
                    if ($userEmail) {
                        $this->sendReceiptEmail($userEmail, $transactionType, $amount, $transactionId, $timestamp);
                    }
                }
                
                return [
                    "date" => $timestamp,
                    "transaction_type" => ucfirst($transactionType),
                    "user_id" => $userId,
                    "wallet_id" => $walletId,
                    "amount" => number_format($amount, 2),
                    "transaction_id" => $transactionId,
                    "extra_data" => $extraData
                ];
            }
            return false;
        } catch (PDOException $e) {
            error_log("Receipt creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves the user's email address from the database.
     */
    private function getUserEmail($userId) {
        $stmt = $this->pdo->prepare("SELECT email FROM Users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $result ? $result['email'] : null;
        error_log("Retrieved user email: " . $email);
        return $email;
    }

    /**
     * Sends a receipt email using PHPMailer.
     */
    private function sendReceiptEmail($email, $transactionType, $amount, $transactionId, $timestamp) {
        $mail = new PHPMailer(true);
        try {
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

            if (!$mail->send()) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            } else {
                error_log("Receipt email sent successfully to " . $email);
            }
        } catch (Exception $e) {
            error_log("PHPMailer Exception: " . $e->getMessage());
        }
    }
}
?>
