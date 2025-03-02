document.addEventListener('DOMContentLoaded', function () {
    const p2pForm = document.getElementById('p2pForm');
    const messageDiv = document.getElementById('message');
    const senderWalletSelect = document.getElementById('sender_wallet_id');
    const receiverEmailInput = document.getElementById('receiver_email');
    const amountInput = document.getElementById('amount');
    let API_ENDPOINT = 'http://192.168.137.1/Project_EcoPay/EcoPay_backend/V2/';

    function fetchUserId() {
        const userId = sessionStorage.getItem("user_id");
        if (!userId) {
            window.location.href = '../login/login.html';
        }
        return userId;
    }

    async function populateWallets() {
        try {
            const response = await axios.get(API_ENDPOINT + 'get_wallets.php');
            if (response.data.status === 'success' && Array.isArray(response.data.wallets)) {
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
            messageDiv.textContent = 'Error loading wallets.';
            messageDiv.className = 'message error';
        }
    }

    if (senderWalletSelect) {
        populateWallets();
    }

    if (p2pForm) {
        p2pForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const senderWalletId = senderWalletSelect.value;
            const receiverEmail = receiverEmailInput.value;
            const amount = amountInput.value;

            if (!receiverEmail || !receiverEmail.includes('@')) {
                messageDiv.textContent = 'Please enter a valid receiver email.';
                messageDiv.className = 'message error';
                return;
            }

            try {
                const requestData = {
                    sender_wallet_id: senderWalletId,
                    receiver_identifier: receiverEmail,
                    amount: amount
                };

                const response = await axios.post(API_ENDPOINT + 'p2p_transfer.php', {
                    action: 'p2pTransfer',
                    ...requestData
                }, {
                    headers: { "Content-Type": "application/json" }
                });

                if (response.data.status === 'success') {
                    messageDiv.textContent = 'Transfer successful!';
                    messageDiv.className = 'message success';
                    p2pForm.reset();
                } else {
                    messageDiv.textContent = response.data.message || 'Transfer failed.';
                    messageDiv.className = 'message error';
                }
            } catch (error) {
                console.error('P2P Email transfer error:', error);
                messageDiv.textContent = 'Error processing transfer.';
                messageDiv.className = 'message error';
            }
        });
    }
});
