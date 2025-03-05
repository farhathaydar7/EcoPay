<?php
require_once 'db_connection.php';

session_start();
if (!isset($_SESSION["admin_id"])) { 
    echo "Admin not logged in.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_POST["user_id"];
    $verificationType = $_POST["verification_type"]; // e.g., 'document_verified', 'super_verified'
    $status = $_POST["status"]; // 'true' or 'false'

    if (empty($userId) || empty($verificationType) || !in_array($verificationType, ['document_verified', 'super_verified']) || !in_array($status, ['true', 'false'])) {
        echo "Invalid input.";
        exit;
    }

    $statusBool = filter_var($status, FILTER_VALIDATE_BOOLEAN);

    try {
        $stmt = $pdo->prepare("SELECT user_id FROM VerificationStatuses WHERE user_id = ?");
        $stmt->execute([$userId]);
        $verificationStatusExists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($verificationStatusExists) {
            $stmt = $pdo->prepare("UPDATE VerificationStatuses SET $verificationType = ? WHERE user_id = ?");
        } else {
            $stmt = $pdo->prepare("INSERT INTO VerificationStatuses (user_id, $verificationType) VALUES (?, ?)");
        }
        $stmt->execute([$statusBool, $userId]);

        echo "Verification status updated successfully.";

    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }

} else {
    echo "Invalid request method.";
}
?>