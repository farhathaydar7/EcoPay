<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_connection.php';

class Wallet {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function setDefaultWallet($userId, $walletId) {
        try {
            // Begin transaction
            $this->pdo->beginTransaction();

            // Set all wallets to not default
            $stmt = $this->pdo->prepare("UPDATE Wallets SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Set the selected wallet to default
            $stmt = $this->pdo->prepare("UPDATE Wallets SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$walletId, $userId]);

            if ($stmt->rowCount() > 0) {
                $this->pdo->commit();
                return ['success' => true, 'message' => 'Default wallet updated successfully'];
            } else {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Failed to update default wallet'];
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("setDefaultWallet error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error updating default wallet', 'error' => $e->getMessage()];
        }
    }

    public function renameWallet($walletId, $newWalletName) {
        try {
            $stmt = $this->pdo->prepare("UPDATE Wallets SET wallet_name = ? WHERE id = ?");
            $stmt->execute([$newWalletName, $walletId]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Wallet renamed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to rename wallet'];
            }
        } catch (PDOException $e) {
            error_log("renameWallet error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error renaming wallet', 'error' => $e->getMessage()];
        }
    }
    
    public function createWallet($userId, $walletName, $currency = 'USD') {
        try {
            // Check if the user has less than 3 wallets
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $walletCount = $stmt->fetchColumn();
    
            if ($walletCount >= 3) {
                return ['success' => false, 'message' => 'You can only create up to 3 wallets.'];
            }
    
            // Determine if this is the first wallet for the user
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $isFirstWallet = ($stmt->fetchColumn() == 0);
    
            // Check if wallet with the same name already exists for the user
            $stmt = $this->pdo->prepare("SELECT id FROM Wallets WHERE user_id = ? AND wallet_name = ?");
            $stmt->execute([$userId, $walletName]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'A wallet with this name already exists.'];
            }
    
            // Insert the new wallet with the correct value for is_default
            $stmt = $this->pdo->prepare("INSERT INTO Wallets (user_id, wallet_name, currency, is_default) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $walletName, $currency, (int)$isFirstWallet]); // Cast to integer
    
            $walletId = $this->pdo->lastInsertId();
    
            if ($walletId) {
                return ['success' => true, 'message' => 'Wallet created successfully', 'wallet_id' => $walletId];
            } else {
                return ['success' => false, 'message' => 'Failed to create wallet'];
            }
        } catch (PDOException $e) {
            error_log("createWallet error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating wallet', 'error' => $e->getMessage()];
        }
    }
    

    public function deleteWallet($walletId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM Wallets WHERE id = ?");
            $stmt->execute([$walletId]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Wallet deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete wallet'];
            }
        } catch (PDOException $e) {
            error_log("deleteWallet error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error deleting wallet', 'error' => $e->getMessage()];
        }
    }

    public function switchBalance($fromWalletId, $toWalletId, $amount) {
        try {
            // Begin transaction
            $this->pdo->beginTransaction();

            // Check if the from wallet has enough balance
            $stmt = $this->pdo->prepare("SELECT balance FROM Wallets WHERE id = ?");
            $stmt->execute([$fromWalletId]);
            $fromWalletBalance = $stmt->fetchColumn();

            if ($fromWalletBalance < $amount) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Insufficient balance in the source wallet'];
            }

            // Deduct the amount from the from wallet
            $stmt = $this->pdo->prepare("UPDATE Wallets SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $fromWalletId]);

            // Add the amount to the to wallet
            $stmt = $this->pdo->prepare("UPDATE Wallets SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $toWalletId]);

            // Commit transaction
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Balance switched successfully'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("switchBalance error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error switching balance', 'error' => $e->getMessage()];
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);

    if ($data === null) {
        echo json_encode(["error" => "Invalid JSON format"]);
        exit;
    }

    $wallet = new Wallet($pdo);

    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'setDefaultWallet':
                $userId = $data['user_id'] ?? null;
                $walletId = $data['wallet_id'] ?? null;
                if ($userId && $walletId) {
                    echo json_encode($wallet->setDefaultWallet($userId, $walletId));
                } else {
                    echo json_encode(["success" => false, "message" => "Missing user_id or wallet_id"]);
                }
                break;
            case 'renameWallet':
                $walletId = $data['wallet_id'] ?? null;
                $newWalletName = $data['new_wallet_name'] ?? null;
                if ($walletId && $newWalletName) {
                    echo json_encode($wallet->renameWallet($walletId, $newWalletName));
                } else {
                    echo json_encode(["success" => false, "message" => "Missing wallet_id or new_wallet_name"]);
                }
                break;
            case 'createWallet':
                // Get user ID from session or request
                $userId = $_SESSION['user_id'] ?? $data['user_id'] ?? null;
                $walletName = $data['wallet_name'] ?? null;
                $currency = $data['currency'] ?? 'USD';
                if ($userId && $walletName) {
                    echo json_encode($wallet->createWallet($userId, $walletName, $currency));
                } else {
                    echo json_encode(["success" => false, "message" => "Missing user_id or wallet_name"]);
                }
                break;
            case 'deleteWallet':
                $walletId = $data['wallet_id'] ?? null;
                if ($walletId) {
                    echo json_encode($wallet->deleteWallet($walletId));
                } else {
                    echo json_encode(["success" => false, "message" => "Missing wallet_id"]);
                }
                break;
            case 'switchBalance':
                $fromWalletId = $data['from_wallet_id'] ?? null;
                $toWalletId = $data['to_wallet_id'] ?? null;
                $amount = $data['amount'] ?? null;
                if ($fromWalletId && $toWalletId && $amount) {
                    echo json_encode($wallet->switchBalance($fromWalletId, $toWalletId, $amount));
                } else {
                    echo json_encode(["success" => false, "message" => "Missing from_wallet_id, to_wallet_id, or amount"]);
                }
                break;
            default:
                echo json_encode(["error" => "Invalid action"]);
        }
    } else {
        echo json_encode(["error" => "Missing action"]);
    }
}
?>
