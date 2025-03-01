<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';
require_once 'Wallet.php';

session_start();

if (!isset($_SESSION["user_id"])) {
    $response_data = ['status' => 'error', 'message' => 'User not logged in.'];
    echo json_encode($response_data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $response_data = ['status' => 'error', 'message' => 'Invalid request method. GET required.'];
    echo json_encode($response_data);
    exit;
}

$userId = $_SESSION["user_id"];

try {
    $stmt = $pdo->prepare("SELECT id, user_id, wallet_name, balance, currency, is_default FROM Wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $walletData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $wallets = [];
    foreach ($walletData as $data) {
        $wallet = new Wallet($data);
        $wallets[] = [
            'wallet_id' => $wallet->id,
            'wallet_name' => $wallet->wallet_name,
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'is_default' => $wallet->is_default
        ];
    }

    $response_data = [
        'status' => 'success',
        'wallets' => $wallets
    ];

} catch (PDOException $e) {
    http_response_code(500);
    error_log('PDOException in get_wallets.php: ' . $e->getMessage() . ', User ID: ' . $userId);
    $response_data = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
} finally {
    echo json_encode($response_data);
    exit;
}
