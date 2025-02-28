document.addEventListener('DOMContentLoaded', function() {
    const p2pForm = document.getElementById('p2pForm');
    const messageDiv = document.getElementById('message');
    const senderWalletSelect = document.getElementById('sender_wallet_id');

    // Fetch wallets and populate the select element
    async function populateWallets() {
        try {
            const response = await axios.get('../../EcoPay_backend/get_wallets.php');
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

        try {
            const response = await axios.post('../../EcoPay_backend/p2p_transfer.php', {
                receiver_identifier: receiverEmail,
                sender_wallet_id: senderWalletId,
                amount: amount
            });

            if (response.data.includes('successful')) {
                messageDiv.textContent = 'Transfer successful!';
                messageDiv.className = 'message success';
                // Optionally, clear the form
                p2pForm.reset();
            } else {
                messageDiv.textContent = response.data;
                messageDiv.className = 'message error';
            }
        } catch (error) {
            console.error('P2P transfer error:', error);
            messageDiv.textContent = 'An error occurred during the transfer.';
            messageDiv.className = 'message error';
        }
    });
});
