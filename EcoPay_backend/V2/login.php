<?php
require_once 'db_connection.php';
require_once 'User.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = [];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response = ['status' => 'error', 'message' => 'POST requests only.'];
    echo json_encode($response);
    exit;
}

// Sanitize input to prevent SQL Injection and XSS
$email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
$pass = $_POST["password"];

if (empty($email) || empty($pass)) {
    $response = ['status' => 'error', 'message' => 'Email and password are required.'];
    echo json_encode($response);
    exit;
}

try {
    // Ensure the database connection is valid
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id, userName, fName, lName, email, password FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            if (password_verify($pass, $userData["password"])) {
                $user = new User($userData);
                $_SESSION["user_id"] = $user->id;  // Store user ID in session

                $response = [
                    'status' => 'success',
                    'message' => 'Login successful!',
                    'user' => [
                        'id' => $user->id,
                        'userName' => $user->userName,
                        'fName' => $user->fName,
                        'lName' => $user->lName,
                        'email' => $user->email
                    ],
                    'user_id' => $user->id // Include user ID in the response
                ];
                $_SESSION["user_id"] = $user->id;
            } else {
                $response = ['status' => 'error', 'message' => 'Incorrect password.'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'User not found.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Database connection failed.'];
    }
} catch (PDOException $e) {
    $response = ['status' => 'error', 'message' => 'Login error: ' . $e->getMessage()];
}

echo json_encode($response);
?>
