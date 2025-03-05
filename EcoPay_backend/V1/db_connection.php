<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}

function isSuperVerified($pdo, $userId) {
    error_log("Checking if user {$userId} is super verified.");
    $stmt = $pdo->prepare("SELECT super_verified FROM VerificationStatuses WHERE user_id = ?");
    $stmt->execute([$userId]);
    $verificationStatus = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("is_super_verified: " . print_r($verificationStatus, true));
    return $verificationStatus && $verificationStatus['super_verified'];
}
?>