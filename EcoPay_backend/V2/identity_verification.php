<?php
require_once 'db_connection.php';
require_once 'VerificationStatus.php';

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
        $verificationStatus = VerificationStatus::getByUserId($userId, $pdo);

        if (!$verificationStatus) {
            $verificationStatus = new VerificationStatus(['user_id' => $userId], $pdo);
        }

        if ($verificationType === 'document_verified') {
            $verificationStatus->document_verified = $statusBool;
        } elseif ($verificationType === 'super_verified') {
            $verificationStatus->super_verified = $statusBool;
        }

        if ($verificationStatus->save()) {
            echo "Verification status updated successfully.";
        } else {
            echo "Failed to update verification status.";
        }

    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }

} else {
    echo "Invalid request method.";
}
?>
