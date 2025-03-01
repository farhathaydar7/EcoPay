document.addEventListener('DOMContentLoaded', function() {
    const p2pForm = document.getElementById('p2pForm');
    const messageDiv = document.getElementById('message');
    const senderWalletSelect = document.getElementById('sender_wallet_id');
    const receivedP2pTransactionTableBody = document.getElementById('receivedP2pTransactionTableBody');

    // Fetch wallets and populate the select element
    async function populateWallets() {
        try {
            const response = await axios.get('../../EcoPay_backend/V2/get_wallets.php');
            console.log('Wallets response:', response.data);
            if (response.data.status === 'success' && response.data.wallets) {
                response.data.wallets.forEach(wallet => {
                    const option = document.createElement('option');
                    option.value = wallet.wallet_id;
                    option.textContent = `${wallet.wallet_name} (${wallet.currency})`;
                    senderWalletSelect.appendChild(option);
                });
            } else {
                messageDiv.textContent = 'Failed to load wallets.';
                messageDiv.className = 'message error';
            }
        } catch (error) {
            console.error('Error fetching wallets:', error);
            messageDiv.textContent = 'An error occurred while loading wallets.';
            messageDiv.className = 'message error';
        }
    }

    populateWallets();

    p2pForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const receiverEmail = document.getElementById('receiver_email').value;
        const senderWalletId = document.getElementById('sender_wallet_id').value;
        const amount = document.getElementById('amount').value;

        console.log("Form submitted:", { receiverEmail, senderWalletId, amount });

        try {
            const response = await axios.post('../../EcoPay_backend/V2/p2p_transfer.php', 
                {
                    receiver_identifier: receiverEmail,
                    sender_wallet_id: senderWalletId,
                    amount: amount
                }, 
                {
                    headers: { "Content-Type": "application/json" }
                }
            );

            console.log('P2P transfer response:', response.data);

            if (response.data.status && response.data.status === 'success') {
                messageDiv.textContent = 'Transfer successful!';
                messageDiv.className = 'message success';
                p2pForm.reset();
            } else {
                messageDiv.textContent = response.data;
                messageDiv.className = 'message error';
            }
        } catch (error) {
            console.error('P2P transfer error:', error.response ? error.response.data : error.message);
            messageDiv.textContent = error.response?.data?.error || 'An error occurred during the transfer.';
            messageDiv.className = 'message error';
        }
    });

    // Fetch and display received P2P transactions
    async function fetchReceivedP2pTransactions() {
        try {
            const response = await axios.get('../../EcoPay_backend/V2/transactions_history_received_p2p.php');
            const receivedP2pResponse = response.data;
            console.log('Received P2P transactions:', receivedP2pResponse);

            if (Array.isArray(receivedP2pResponse.data)) {
                receivedP2pResponse.data.forEach(transaction => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${transaction.type} from ${transaction.sender}</td>
                        <td>${transaction.amount}</td>
                        <td>${transaction.status}</td>
                        <td>${transaction.timestamp}</td>
                        <td>(${transaction.sender_email})</td>
                    `;
                    receivedP2pTransactionTableBody.appendChild(row);
                });
            } else {
                console.error('receivedP2pResponse.data is not an array:', receivedP2pResponse.data);
            }
        } catch (error) {
            console.error('Error fetching received P2P transactions:', error);
        }
    }

    fetchReceivedP2pTransactions();
});
