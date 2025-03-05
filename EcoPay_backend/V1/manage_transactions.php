<?php
require_once 'db_connection.php';

session_start();
if (!isset($_SESSION["admin_id"])) {
    echo "Admin not logged in.";
    exit;
}

// --- Filtering and Search ---
$filterType = $_GET['filter_type'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';
$searchUserId = $_GET['search_user_id'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$whereClauses = [];
$params = [];

if (!empty($filterType)) {
    $whereClauses[] = "type = ?";
    $params[] = $filterType;
}
if (!empty($filterStatus)) {
    $whereClauses[] = "status = ?";
    $params[] = $filterStatus;
}
if (!empty($searchUserId)) {
    $whereClauses[] = "user_id = ?";
    $params[] = $searchUserId;
}
if (!empty($startDate) && !empty($endDate)) {
    $whereClauses[] = "timestamp BETWEEN ? AND ?";
    $params[] = $startDate . ' 00:00:00'; // Assuming time format
    $params[] = $endDate . ' 23:59:59';
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// --- Fetch Transactions from Database ---
try {
    $sql = "SELECT * FROM Transactions $whereSql ORDER BY timestamp DESC"; // Order by latest first
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($transactions);


} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>