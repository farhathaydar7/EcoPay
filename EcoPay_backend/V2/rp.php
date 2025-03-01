<?php
require_once 'db_connection.php';
require_once 'User.php';
require_once 'Wallet.php';
require_once 'Transaction.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

$response = [];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response = ['status' => 'error', 'message' => 'POST requests only.'];
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    $response = ['status' => 'error', 'message' => 'User not logged in.'];
    echo json_encode($response);
    exit;
}

$userId = $_SESSION["user_id"];

// --- Input Parameters ---
$senderWalletId = $_POST["sender_wallet_id"] ?? null;
$receiverEmail = $_POST["receiver_email"] ?? null;
$amount = $_POST["amount"] ?? null;
$frequency = $_POST["frequency"] ?? null; // e.g., daily, weekly, monthly, yearly

// --- Input Validation ---
if (empty($senderWalletId) || !is_numeric($senderWalletId)) {
    $response = ['status' => 'error', 'message' => 'Invalid sender wallet ID.'];
    echo json_encode($response);
    exit;
}

if (empty($receiverEmail) || !filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
    $response = ['status' => 'error', 'message' => 'Invalid receiver email.'];
    echo json_encode($response);
    exit;
}

if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
    $response = ['status' => 'error', 'message' => 'Invalid amount.'];
    echo json_encode($response);
    exit;
}

if (empty($frequency) || !in_array($frequency, ['daily', 'weekly', 'monthly', 'yearly'])) {
    $response = ['status' => 'error', 'message' => 'Invalid frequency.'];
    echo json_encode($response);
    exit;
}

$amount = floatval($amount);

try {
    $pdo->beginTransaction();

    //  Get Sender's Wallet
    $senderWallet = Wallet::getById($senderWalletId, $pdo);
    if (!$senderWallet || $senderWallet->user_id !== $userId) {
        $response = ['status' => 'error', 'message' => 'Invalid sender wallet.'];
        echo json_encode($response);
        $pdo->rollBack();
        exit;
    }

    //Get Receiver's User ID
    $receiverUser = User::getByEmail($receiverEmail, $pdo);
    if (!$receiverUser) {
        $response = ['status' => 'error', 'message' => 'Receiver not found.'];
        echo json_encode($response);
        $pdo->rollBack();
        exit;
    }

    // Get Receiver's Default Wallet
    $stmt = $pdo->prepare("SELECT id FROM Wallets WHERE user_id = ? AND is_default = 1");
    $stmt->execute([$receiverUser->id]);
    $receiverWalletData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receiverWalletData) {
        $response = ['status' => 'error', 'message' => 'Receiver does not have a default wallet.'];
        echo json_encode($response);
        $pdo->rollBack();
        exit;
    }
    $receiverWalletId = $receiverWalletData['id'];

    // Validate Sender's Balance
    if ($senderWallet->balance < $amount) {
        $response = ['status' => 'error', 'message' => 'Insufficient balance.'];
        echo json_encode($response);
        $pdo->rollBack();
        exit;
    }

    // Perform the transfer using p2p_transfer.php
    $transferData = http_build_query([
        'receiver_identifier' => $receiverEmail,
        'sender_wallet_id' => $senderWalletId,
        'amount' => $amount
    ]);

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $transferData
        )
    );
    $context  = stream_context_create($opts);
    $transferResponse = file_get_contents('p2p_transfer.php', false, $context);

    $transferResponseData = json_decode($transferResponse, true);

    if ($transferResponseData && $transferResponseData['status'] === 'success') {
        // 6. Create Transaction Records
        $senderTransactionData = [
            'user_id' => $userId,
            'type' => 'recurring_payment',
            'amount' => -$amount, // Negative for debit
            'status' => 'completed',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $senderTransaction = new Transaction($senderTransactionData, $pdo);
        $senderTransaction->create();

        $receiverTransactionData = [
            'user_id' => $receiverUser->id,
            'type' => 'recurring_payment',
            'amount' => $amount,
            'status' => 'completed',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $receiverTransaction = new Transaction($receiverTransactionData, $pdo);
        $receiverTransaction->create();

        // 7. Schedule Recurring Payment
        $nextExecution = date('Y-m-d H:i:s'); // For simplicity, schedule immediately
        switch ($frequency) {
            case 'daily':
                $nextExecution = date('Y-m-d H:i:s', strtotime('+1 day'));
                break;
            case 'weekly':
                $nextExecution = date('Y-m-d H:i:s', strtotime('+1 week'));
                break;
            case 'monthly':
                $nextExecution = date('Y-m-d H:i:s', strtotime('+1 month'));
                break;
            case 'yearly':
                $nextExecution = date('Y-m-d H:i:s', strtotime('+1 year'));
                break;
        }

        $stmt = $pdo->prepare("INSERT INTO PaymentSchedules (user_id, amount, frequency, next_execution) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $amount, $frequency, $nextExecution]);

        $pdo->commit();
        $response = ['status' => 'success', 'message' => 'Recurring payment scheduled successfully.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Transfer failed: ' . ($transferResponseData ? $transferResponseData['message'] : 'Unknown error')];
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
}

echo json_encode($response);
?>
