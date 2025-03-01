<?php
require_once 'db_connection.php';

session_start();
if (!isset($_SESSION["admin_id"])) {
    echo "Admin not logged in.";
    exit;
}

// --- Filtering and Search Logic ---
$searchKeyword = $_GET['search_keyword'] ?? ''; // Search by name or email
$filterVerified = $_GET['filter_verified'] ?? ''; // Filter by verification status (true/false - needs to be joined with VerificationStatuses table)
$sortColumn = $_GET['sort_column'] ?? 'created_at'; // Default sorting
$sortOrder = $_GET['sort_order'] ?? 'DESC'; // Default sorting order

$whereClauses = [];
$params = [];

if (!empty($searchKeyword)) {
    $whereClauses[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%" . $searchKeyword . "%";
    $params[] = "%" . $searchKeyword . "%";
    $params[] = "%" . $searchKeyword . "%";
}

if ($filterVerified != '') { // Handle both 'true' and 'false' filter values
    $whereClauses[] = "VerificationStatuses.email_verified = ?"; // Filtering by email_verified for example
    $params[] = filter_var($filterVerified, FILTER_VALIDATE_BOOLEAN); // Convert string to boolean
}


$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
$orderBySql = "ORDER BY " . $sortColumn . " " . $sortOrder; // Basic sorting - sanitize input in real app!


// --- Fetch Users from Database ---
try {
    // Join Users table with VerificationStatuses to include verification status in results
    $sql = "SELECT Users.*, VerificationStatuses.email_verified, VerificationStatuses.document_verified, VerificationStatuses.super_verified 
            FROM Users 
            LEFT JOIN VerificationStatuses ON Users.id = VerificationStatuses.user_id 
            $whereSql 
            $orderBySql";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($users);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>