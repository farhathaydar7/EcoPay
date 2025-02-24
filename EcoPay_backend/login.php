<?php
require_once 'db_connection.php';

session_start(); 

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "POST requests only.";
    exit;
}

$email = $_POST["email"];
$pass = $_POST["password"];

if (empty($email) || empty($pass)) {
    echo "Email and password required.";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password FROM Users WHERE email = ?"); 
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($pass, $user["password"])) {
        $_SESSION["user_id"] = $user["id"]; 
        echo "Login successful!";
        
    } else {
        echo "Invalid credentials.";
    }
} catch (PDOException $e) {
    echo "Login error: " . $e->getMessage();
}
?>