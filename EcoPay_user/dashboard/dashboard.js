document.addEventListener('DOMContentLoaded', () => {
    const userNameSpan = document.getElementById('user-name');
    const userEmailSpan = document.getElementById('user-email');
    const walletsContainer = document.getElementById('user-wallets'); // Container for wallets

    // Fetch user info
    axios.get('../../EcoPay_backend/profile.php')
        .then(response => {
            if (response.data && response.data.user) {
                const userData = response.data.user;
                userNameSpan.textContent = userData.name;
                userEmailSpan.textContent = userData.email;
            } else {
                throw new Error("User data not found in response.");
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            alert('Could not load user data. Please check the console for details.');
        });

    // Fetch user wallets
    axios.get('../../EcoPay_backend/get_wallets.php')
        .then(response => {
            if (response.data && response.data.wallets) {
                const walletsData = response.data.wallets;

                // Display each wallet
                walletsData.forEach(wallet => {
                    const walletDiv = document.createElement('div');
                    walletDiv.classList.add('wallet');
                    walletDiv.innerHTML = `
                        <p>Wallet Name: ${wallet.wallet_name}</p>
                        <p>Balance: ${wallet.balance} ${wallet.currency}</p>
                    `;
                    walletsContainer.appendChild(walletDiv);
                });
            } else {
                throw new Error("Wallets data not found in response.");
            }
        })
        .catch(error => {
            console.error('Error fetching wallet data:', error);
            alert('Could not load wallet data. Please check the console for details.');
        });
});
