<?php
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "POST requests only.";
    exit;
}

$name = $_POST["fullName"];
$email = $_POST["email"];
$phone = $_POST["phone"];
$pass = $_POST["password"];

if (empty($name) || empty($email) || empty($phone) || empty($pass)) {
    echo "All fields required.";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email.";
    exit;
}

if (!preg_match('/^[0-9]{8}$/', $phone)) {
    echo "Invalid phone (8 digits).";
    exit;
}

$hashedPass = password_hash($pass, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO Users (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $hashedPass]);
    $userId = $pdo->lastInsertId();

    // Create default entries in UserProfiles and Wallets
    $stmt = $pdo->prepare("INSERT INTO UserProfiles (user_id) VALUES (?)");
    $stmt->execute([$userId]);

    $stmt = $pdo->prepare("INSERT INTO Wallets (user_id) VALUES (?)");
    $stmt->execute([$userId]);

    echo "User registered!";

} catch (PDOException $e) {
    if ($e->getCode() == 23000 && strpos($e->getMessage(), 'phone')) {
        echo "Phone number already in use.";
    } else {
        echo "Registration error: " . $e->getMessage();
    }
}
?>
