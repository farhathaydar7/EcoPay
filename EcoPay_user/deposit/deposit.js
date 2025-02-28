async function fetchWallets() {
    try {
        const response = await axios.get('../../EcoPay_backend/get_wallets.php');
        
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

async function deposit() {
    const walletId = document.getElementById('wallet').value;
    const amount = document.getElementById('amount').value;
    const messageDiv = document.getElementById('message');

    messageDiv.textContent = ''; // Clear previous messages

    if (!walletId || !amount) {
        messageDiv.textContent = 'Please select a wallet and enter an amount.';
        return;
    }

    if (parseFloat(amount) <= 0) {
        messageDiv.textContent = 'Please enter a valid positive amount.';
        return;
    }

    try {
      // First, check if the user is super verified
      const profileResponse = await axios.get('../../EcoPay_backend/profile.php');

      if (profileResponse.data.status !== 'success') {
          messageDiv.textContent = profileResponse.data.message;
          return;
      }

      if (!profileResponse.data.user.super_verified) {
          messageDiv.textContent = 'User is not super verified.';
          return;
      }

        const depositResponse = await axios.post('../../EcoPay_backend/deposit.php', 
            new URLSearchParams({
                wallet_id: walletId,
                amount: amount
            })
        );

        if (depositResponse.data && depositResponse.data.error) {
            console.error("Deposit error:", depositResponse.data.error);
            messageDiv.textContent = "An error occurred during deposit.";
            messageDiv.style.color = 'red';
        } else {
            messageDiv.textContent = depositResponse.data;
            messageDiv.style.color = 'green';
        }

    } catch (error) {
        console.error("Error during deposit:", error);
        messageDiv.textContent = "An error occurred during deposit.";
         messageDiv.style.color = 'red';

    }
}
fetchWallets();
