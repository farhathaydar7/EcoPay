<?php
require_once 'db_connection.php';



try {
    $pdo->beginTransaction();

  
    $currentTimestamp = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM PaymentSchedules WHERE next_execution <= ?");
    $stmt->execute([$currentTimestamp]);
    $paymentSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($paymentSchedules)) {
        echo "No payment schedules due for execution." . PHP_EOL;
    } else {
        echo "Processing " . count($paymentSchedules) . " payment schedules." . PHP_EOL;

        foreach ($paymentSchedules as $schedule) {
            $userId = $schedule['user_id'];
            $amount = $schedule['amount'];
            $frequency = $schedule['frequency'];
            $scheduleId = $schedule['id'];

            echo "Processing schedule ID: " . $scheduleId . ", User ID: " . $userId . ", Amount: " . $amount . ", Frequency: " . $frequency . PHP_EOL;


            // --- Check Sender Balance ---
            $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$senderWallet || $senderWallet["balance"] < $amount) {
                echo "Insufficient balance for User ID: " . $userId . ", Schedule ID: " . $scheduleId . ". Skipping." . PHP_EOL;
                continue; // Skip to the next schedule
            }

            $newSenderBalance = $senderWallet["balance"] - $amount;
       
            $stmt = $pdo->prepare("UPDATE Wallets SET balance = ? WHERE user_id = ? AND wallet_number = 1");
            $stmt->execute([$newSenderBalance, $userId]);

      
            $stmt = $pdo->prepare("INSERT INTO Transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, 'recurring_payment', -$amount, 'completed']); 


         
            $nextExecutionTime = null;
            switch ($frequency) {
                case 'daily':
                    $nextExecutionTime = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($schedule['next_execution'])));
                    break;
                case 'weekly':
                    $nextExecutionTime = date('Y-m-d H:i:s', strtotime('+1 week', strtotime($schedule['next_execution'])));
                    break;
                case 'monthly':
                    $nextExecutionTime = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($schedule['next_execution'])));
                    break;
                case 'yearly':
                    $nextExecutionTime = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($schedule['next_execution'])));
                    break;
            }

            if ($nextExecutionTime) {
                $stmt = $pdo->prepare("UPDATE PaymentSchedules SET next_execution = ? WHERE id = ?");
                $stmt->execute([$nextExecutionTime, $scheduleId]);
                echo "Schedule ID: " . $scheduleId . " processed successfully. Next execution: " . $nextExecutionTime . PHP_EOL;
            } else {
                echo "Error updating next execution time for Schedule ID: " . $scheduleId . ". Frequency: " . $frequency . PHP_EOL;
            }
        }
    }


    $pdo->commit();
    echo "Recurring payments processing completed." . PHP_EOL;

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Recurring payments processing error: " . $e->getMessage() . PHP_EOL;
}
?>