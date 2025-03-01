<?php
require_once 'db_connection.php';

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

    // Insert admin user with hashed password
    $stmt = $pdo->prepare("INSERT INTO Admins (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $hashedPassword]);
    echo "Admin registered!";
} catch (PDOException $e) {
    echo "Registration error: " . $e->getMessage();
}
?>