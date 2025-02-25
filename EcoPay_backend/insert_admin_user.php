<?php
require_once 'db_connection.php';

try {
    $sql = "INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES ('7', 'admin', 'admin@gmail.com', 'admintest', current_timestamp())";
    $pdo->exec($sql);
    echo "Admin user inserted successfully!";
} catch (PDOException $e) {
    echo "Error inserting admin user: " . $e->getMessage();

}


//manually adds an admin user to the database , should be removed and replaced with admin adding admin system


?>
