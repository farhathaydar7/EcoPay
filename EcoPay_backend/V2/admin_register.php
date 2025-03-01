<?php
require_once 'db_connection.php';
require_once 'Admin.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "POST requests only.";
    exit;
}

$name = $_POST["fullName"];
$email = $_POST["email"];
$password = $_POST["password"]; // Plain text password - INSECURE

if (empty($name) || empty($email) || empty($password)) {
    echo "All fields required.";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email.";
    exit;
}

try {
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $adminData = [
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword
    ];

    $admin = new Admin($adminData, $pdo);
    if ($admin->create()) {
        echo "Admin registered!";
    } else {
        echo "Registration failed.";
    }
} catch (PDOException $e) {
    echo "Registration error: " . $e->getMessage();
}
?>
