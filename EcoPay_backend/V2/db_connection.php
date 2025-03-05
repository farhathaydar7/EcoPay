<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    error_log('DB connection successful');
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}
?>
