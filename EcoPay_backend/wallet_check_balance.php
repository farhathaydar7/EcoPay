<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    echo "User not logged in.";
    exit;
}

$userId = $_SESSION["user_id"];

// Super Verification Check
if (!isSuperVerified($pdo, $userId)) {
    echo "User is not super verified.";
    exit;
}

try {
    // --- Fetch Wallet Balance from the first wallet (wallet_number = 1) ---
    $stmt = $pdo->prepare("SELECT balance, currency FROM Wallets WHERE user_id = ? AND wallet_number = 1");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($wallet) {
        echo "Your balance is: " . htmlspecialchars($wallet["balance"]) . " " . htmlspecialchars($wallet["currency"]);
    } else {
        echo "Wallet not found.";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>