<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    echo "User not logged in.";
    exit;
}

$userId = $_SESSION["user_id"];

// Super Verification Check
if (!isSuperVerified($pdo, $userId)) {
    echo "User is not super verified.";
    exit;
}

try {
    // --- Fetch  History ---
    $stmt = $pdo->prepare("SELECT * FROM Transactions WHERE user_id = ? ORDER BY timestamp DESC");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($transactions)) {
        echo "No transaction history found.";
        exit;
    }

    //(Requires TCPDF Library) ---
    function generateTransactionHistoryPDF($transactions, $userId) {
        // require_once('tcpdf/tcpdf.php'); to be implemented

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('EcoPay');
        $pdf->SetTitle('Transaction History - User ID: ' . $userId);
        $pdf->SetSubject('Transaction History');
        $pdf->SetKeywords('EcoPay, Transaction, History, PDF');

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setFont('dejavusans', '', 10);

        $pdf->AddPage();

        $html = '<h1>Transaction History</h1>';
        $html .= '<p>User ID: ' . $userId . '</p>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<thead><tr><th>ID</th><th>Type</th><th>Amount</th><th>Status</th><th>Timestamp</th></tr></thead><tbody>';

        foreach ($transactions as $transaction) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($transaction['id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($transaction['type']) . '</td>';
            $html .= '<td>' . htmlspecialchars($transaction['amount']) . '</td>';
            $html .= '<td>' . htmlspecialchars($transaction['status']) . '</td>';
            $html .= '<td>' . htmlspecialchars($transaction['timestamp']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('transaction_history_user_' . $userId . '.pdf', 'D'); // 'D' for download
    }


    //Output 
    if (isset($_GET['output']) && $_GET['output'] === 'pdf') {
        generateTransactionHistoryPDF($transactions, $userId);
        exit(); 

    } else {
        echo "Transaction history logic ready. To download PDF, use '?output=pdf' parameter.";
    }


} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>