<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    echo "User not logged in.";
    exit;
}

$userId = $_SESSION["user_id"];
$walletId = $_GET["wallet_id"]; // Get wallet_id from GET request

if (!isset($walletId) || !is_numeric($walletId)) {
    echo "Invalid wallet ID.";
    exit;
}

// Super Verification Check

try {
    // --- Fetch Wallet Balance from the specified wallet ---
    $stmt = $pdo->prepare("SELECT balance, currency FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->execute([$walletId, $userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($wallet) {
        echo "Wallet balance: " . htmlspecialchars($wallet["balance"]) . " " . htmlspecialchars($wallet["currency"]);
    } else {
        echo "Wallet not found or does not belong to user.";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
