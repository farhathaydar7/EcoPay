

async function fetchWallets() {
    try {
        const response = await axios.get('../../EcoPay_backend/V2/get_wallets.php');
        
        if (response.data.status === 'success') {
            const walletSelect = document.getElementById('wallet');
            response.data.wallets.forEach(wallet => {
                const option = document.createElement('option');
                option.value = wallet.wallet_id;
                option.textContent = `${wallet.wallet_name} (${wallet.currency})`;
                walletSelect.appendChild(option);
            });
        } else {
            document.getElementById('message').textContent = response.data.message;
        }
    } catch (error) {
        console.error("Error fetching wallets:", error);
        document.getElementById('message').textContent = "Error fetching wallets.";
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const withdrawForm = document.getElementById('withdrawForm');
    const messageDiv = document.getElementById('message');

    fetchWallets();

    withdrawForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const walletId = document.getElementById('wallet').value;
        const amount = document.getElementById('amount').value;

        // Basic validation
        if (!walletId || !amount) {
            messageDiv.textContent = 'Please fill in all fields.';
            return;
        }

        // Prepare data for the API
        const data = new URLSearchParams();
        data.append('wallet_id', walletId);
        data.append('amount', amount);

        // Call the withdraw API
        try {
            const response = await fetch('../../EcoPay_backend/V2/withdraw.php', {
                method: 'POST',
                body: data,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            const responseData = await response.text();
            messageDiv.textContent = responseData;
        } catch (error) {
            console.error('There was an error!', error);
            messageDiv.textContent = 'Withdrawal failed: ' + error.message;
        }
    });
});
