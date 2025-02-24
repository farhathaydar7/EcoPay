<?php
$host = 'localhost';
$dbName = 'project_ecopay'; 
$username = 'root'; 
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die(); 
}
?>