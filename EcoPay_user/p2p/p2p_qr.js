document.addEventListener('DOMContentLoaded', function () {
    const p2pForm = document.getElementById('p2pForm');
    const messageDiv = document.getElementById('message');
    const senderWalletSelect = document.getElementById('sender_wallet_id');
    const amountInput = document.getElementById('amount');
    const API_ENDPOINT = 'http://52.47.95.15/EcoPay_backend/V2/';

    function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        let regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    function fetchUserId() {
        const userDataStr = localStorage.getItem("userData");
        if (!userDataStr) {
            console.error("User data is missing from localStorage.");
            return null;
        }
        try {
            const userData = JSON.parse(userDataStr);
            return userData.id || null;
        } catch (e) {
            console.error("Error parsing userData from localStorage:", e);
            return null;
        }
    }

    async function populateWallets() {
        if (!senderWalletSelect) return;

        try {
            const response = await axios.get(`${API_ENDPOINT}get_wallets.php`);
            if (response.data.status === 'success' && Array.isArray(response.data.wallets)) {
                response.data.wallets.forEach(wallet => {
                    const option = document.createElement('option');
                    option.value = wallet.wallet_id;
                    option.textContent = `${wallet.wallet_name} (${wallet.currency})`;
                    senderWalletSelect.appendChild(option);
                });
            } else {
                showMessage('Failed to load wallets.', 'error');
            }
        } catch (error) {
            console.error('Error fetching wallets:', error);
            showMessage('Error loading wallets.', 'error');
        }
    }

    function showMessage(message, type = 'error') {
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = `message ${type}`;
        }
    }

    const qrCodeId = getParameterByName('qr_code_id');

    if (qrCodeId) {
        axios.post(`${API_ENDPOINT}get_qr_code_data.php`, { qr_code_id: qrCodeId }, {
            headers: { 'Content-Type': 'application/json' }
        })
        .then(qrCodeResponse => {
            if (qrCodeResponse.data?.success && qrCodeResponse.data.qr_code) {
                const qrCodeData = qrCodeResponse.data.qr_code;
                if (amountInput) {
                    amountInput.value = qrCodeData.amount;
                    amountInput.readOnly = true;
                }
            } else {
                showMessage('Failed to load QR code data.', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching QR code data:', error);
            showMessage('Error loading QR data.', 'error');
        });
    }

    if (senderWalletSelect) {
        populateWallets();
    }

    if (p2pForm) {
        p2pForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const senderWalletId = senderWalletSelect?.value || null;
            const amount = amountInput?.value || '';

            if (!qrCodeId) return showMessage('QR Code ID is missing.');
            if (!senderWalletId) return showMessage('Please select a wallet.');

            try {
                const requestData = {
                    sender_wallet_id: senderWalletId,
                    qr_code_id: qrCodeId,
                    amount: amount,
                    user_id: fetchUserId()
                };

                const response = await axios.post(`${API_ENDPOINT}process_transaction.php`, requestData, {
                    headers: { 'Content-Type': 'application/json' }
                });

                if (response.data.status === 'success') {
                    showMessage('Transaction successful!', 'success');
                    // Optionally reset the form or redirect
                } else {
                    showMessage(response.data.message || 'Transaction failed.');
                }
            } catch (error) {
                console.error('Error submitting the transaction:', error);
                showMessage('Transaction submission failed.');
            }
        });
    }
});
