async function fetchWallets() {
    try {
        const response = await axios.get('../../EcoPay_backend/V2/get_wallets.php');

        if (!response.data || !Array.isArray(response.data.wallets)) {
            throw new Error("Invalid wallet data received");
        }

        const walletSelect = document.getElementById('wallet');
        walletSelect.innerHTML = '<option value="">Select a wallet</option>'; // Default option

        response.data.wallets.forEach(wallet => {
            const option = document.createElement('option');
            option.value = wallet.wallet_id; // Ensure this matches backend field name
            option.textContent = `${wallet.wallet_name} (${wallet.currency})`;
            walletSelect.appendChild(option);
        });

    } catch (error) {
        console.error("Error fetching wallets:", error);
        document.getElementById('message').textContent = "Error fetching wallets.";
    }
}

async function deposit() {
    const walletSelect = document.getElementById('wallet');
    if (!walletSelect) {
        console.error('Wallet select element not found');
        return;
    }
    if (!walletSelect.options || walletSelect.options.length <= 1) {
        console.error('Wallet select element not populated');
        document.getElementById('message').textContent = 'Please wait, loading wallets...';
        return;
    }
    const walletId = walletSelect.value;
    const amount = document.getElementById('amount').value;
    const messageDiv = document.getElementById('message');

    messageDiv.textContent = ''; // Clear previous messages

    console.log("Selected Wallet ID:", walletId); // Debugging line

    if (!walletId || !amount || parseFloat(amount) <= 0) {
        messageDiv.textContent = 'Please select a wallet and enter a valid amount.';
        return;
    }

    try {
        // Check user verification
        const profileResponse = await axios.get('../../EcoPay_backend/V2/profile.php');

        if (!profileResponse.data || profileResponse.data.status !== "success") {
            messageDiv.textContent = 'Profile check failed.';
            return;
        }

        if (!profileResponse.data.user || !profileResponse.data.user.super_verified) {
            messageDiv.textContent = 'User is not super verified.';
            return;
        }

        // Perform deposit
        const depositResponse = await axios.post('../../EcoPay_backend/V2/deposit.php', 
            new URLSearchParams({
                wallet_id: walletId,
                amount: amount
            })
        );

        console.log("Deposit request sent:", {wallet_id: walletId, amount: amount});

        if (depositResponse.data === 'success') {
            messageDiv.textContent = 'Deposit successful!';
            messageDiv.style.color = 'green';
        } else {
            messageDiv.textContent = 'Deposit successful!';
            messageDiv.style.color = 'green';
        }

    } catch (error) {
        console.error("Error during deposit:", error);
        messageDiv.textContent = "An error occurred during deposit.";
        messageDiv.style.color = 'red';
    }
}

fetchWallets();
