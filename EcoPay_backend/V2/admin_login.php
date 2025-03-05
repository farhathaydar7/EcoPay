<?php
require_once 'db_connection.php';
require_once 'Admin.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "POST requests only.";
    exit;
}

$email = $_POST["email"];
$password = $_POST["password"];

if (empty($email) || empty($password)) {
    echo "Email and password required.";
    exit;
}

try {
    $admin = Admin::getByEmail($email, $pdo);

    if ($admin && password_verify($password, $admin->password)) {
        $_SESSION["admin_id"] = $admin->id;
        echo "Admin login successful!";
    } else {
        echo "Invalid admin credentials.";
    }
} catch (PDOException $e) {
    echo "Admin login error: " . $e->getMessage();
}
?>
