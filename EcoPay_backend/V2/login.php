<?php
require_once 'db_connection.php';
require_once 'User.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

$response = [];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response = ['status' => 'error', 'message' => 'POST requests only.'];
    echo json_encode($response);
    exit;
}

$email = $_POST["email"];
$pass = $_POST["password"];

if (empty($email) || empty($pass)) {
    $response = ['status' => 'error', 'message' => 'Email and password required.'];
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, userName, fName, lName, email, password FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        if (password_verify($pass, $userData["password"])) {
            $user = new User($userData);
            $_SESSION["user_id"] = $user->id;
            $response = [
                'status' => 'success',
                'message' => 'Login successful!',
                'user' => [
                    'id' => $user->id,
                    'userName' => $user->userName,
                    'fName' => $user->fName,
                    'lName' => $user->lName,
                    'email' => $user->email
                ]
            ];
        } else {
            $response = ['status' => 'error', 'message' => 'Incorrect password.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'User not found.'];
    }
} catch (PDOException $e) {
    $response = ['status' => 'error', 'message' => 'Login error: ' . $e->getMessage()];
}

echo json_encode($response);
?>
