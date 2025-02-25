<?php
require_once 'db_connection.php';

try {
    // Insert password in plain text - INSECURE
    $sql = "INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES (NULL, 'admin', 'admin@gmail.com', 'admintest', current_timestamp())";
    $pdo->exec($sql);
    echo "Admin user inserted successfully (PLAIN TEXT PASSWORD)!";
} catch (PDOException $e) {
    echo "Error inserting admin user: " . $e->getMessage();
}
?>
