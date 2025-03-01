document.addEventListener('DOMContentLoaded', () => {
    const messageDiv = document.getElementById('message');
    const transactionTableBody = document.querySelector('#transactionTable tbody');

    async function fetchTransactionHistory() {
        try {
            const response = await axios.get('../../EcoPay_backend/V2/transactions_history.php');

            if (response.data) {
                if (response.data.length === 0) {
                    messageDiv.textContent = 'No transaction history found.';
                    return;
                }

                response.data.forEach(transaction => {
                    const row = document.createElement('tr');
                    let receiverInfo = '';
                    if (transaction.type === 'transfer') {
                        receiverInfo = ` to ${transaction.receiver}`;
                    }
                    row.innerHTML = `
                        <td>${transaction.type}${receiverInfo}</td>
                        <td>${transaction.amount}</td>
                        <td>${transaction.status}</td>
                        <td>${transaction.timestamp}</td>
                    `;
                    transactionTableBody.appendChild(row);
                });
            } else {
                messageDiv.textContent = 'Failed to fetch transaction history.';
            }
        } catch (error) {
            console.error('Error fetching transaction history:', error);
            messageDiv.textContent = 'Failed to fetch transaction history. Please try again.';
        }
    }

    fetchTransactionHistory();

    async function fetchWallets() {
        try {
            const response = await axios.get('../../EcoPay_backend/V2/get_wallets.php');

            if (!response.data || !Array.isArray(response.data.wallets)) {
                throw new Error("Invalid wallet data received");
            }

            const walletSelect = document.getElementById('wallet');
            if (!walletSelect) {
                console.error('Wallet select element not found');
                return;
            }
            walletSelect.innerHTML = '<option value="">Select a wallet</option>'; // Default option

            response.data.wallets.forEach(wallet => {
                const option = document.createElement('option');
                option.value = wallet.id; // Ensure this matches backend field name
                option.textContent = `${wallet.wallet_name} (${wallet.currency})`;
                walletSelect.appendChild(option);
            });

        } catch (error) {
            console.error("Error fetching wallets:", error);
            document.getElementById('message').textContent = "Error fetching wallets.";
        }
    }

    fetchWallets();
});
