<?php
require_once 'db_connection.php';

try {
    // Hash the password
    $hashedPassword = password_hash('admintest', PASSWORD_BCRYPT);

    // Insert admin user with hashed password
    $sql = "INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES (NULL, 'admin', 'admin@gmail.com', '$hashedPassword', current_timestamp())";
    $pdo->exec($sql);
    echo "Admin user inserted successfully (HASHED PASSWORD)!";
} catch (PDOException $e) {
    echo "Error inserting admin user: " . $e->getMessage();
}
?>
