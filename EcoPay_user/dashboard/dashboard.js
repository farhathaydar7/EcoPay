document.addEventListener('DOMContentLoaded', () => {

    const userNameSpan = document.getElementById('user-name');
    const userEmailSpan = document.getElementById('user-email');
    const walletsContainer = document.getElementById('user-wallets');
    const walletsList = document.getElementById('wallets-list');
    const createWalletButton = document.getElementById('create-wallet-button');

    let userId;
    let walletsData;

    function fetchUserId() {
        return axios.get('../../EcoPay_backend/V2/get_user_id.php')
            .then(response => {
                if (response.data && response.data.userId) {
                    userId = response.data.userId;
                    return userId;
                } else {
                    throw new Error("User ID not found in response.");
                }
            })
            .catch(error => {
                console.error('Error fetching user ID:', error);
                alert('Could not load user ID. Please check the console for details.');
            });
    }

    function fetchUserInfo() {
        axios.get('../../EcoPay_backend/V2/profile.php')
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
    }

    function fetchUserWallets() {
        return axios.get('../../EcoPay_backend/V2/get_wallets.php')
            .then(response => {
                if (response.data && response.data.wallets) {
                    walletsData = response.data.wallets;
                    walletsList.innerHTML = '';

                    walletsData.forEach(wallet => {
                        const walletDiv = document.createElement('div');
                        walletDiv.classList.add('wallet');
                        walletsList.appendChild(walletDiv);

                        const walletName = document.createElement('p');
                        walletName.textContent = `Wallet Name: ${wallet.wallet_name}`;
                        walletDiv.appendChild(walletName);

                        const walletBalance = document.createElement('p');
                        walletBalance.textContent = `Balance: ${wallet.balance} ${wallet.currency}`;
                        walletDiv.appendChild(walletBalance);

                        const deleteButton = document.createElement('button');
                        deleteButton.classList.add('delete-wallet-button');
                        deleteButton.dataset.walletId = wallet.wallet_id;
                        deleteButton.textContent = 'Delete';
                        walletDiv.appendChild(deleteButton);

                        const renameButton = document.createElement('button');
                        renameButton.classList.add('rename-wallet-button');
                        renameButton.dataset.walletId = wallet.wallet_id;
                        renameButton.textContent = 'Rename';
                        walletDiv.appendChild(renameButton);

                        const setDefaultButton = document.createElement('button');
                        setDefaultButton.classList.add('set-default-wallet-button');
                        setDefaultButton.dataset.walletId = wallet.wallet_id;
                        setDefaultButton.textContent = 'Set Default';
                        walletDiv.appendChild(setDefaultButton);

                        const switchBalanceButton = document.createElement('button');
                        switchBalanceButton.classList.add('switch-balance-wallet-button');
                        switchBalanceButton.dataset.walletId = wallet.wallet_id;
                        switchBalanceButton.textContent = 'Switch Balance';
                        walletDiv.appendChild(switchBalanceButton);
                    });

                    // Add event listeners for delete buttons
                    document.querySelectorAll('.delete-wallet-button').forEach(button => {
                        button.addEventListener('click', (event) => {
                            const walletId = event.target.dataset.walletId;
                            if (confirm('Are you sure you want to delete this wallet?')) {
                                axios.post('../../EcoPay_backend/V2/wallets.php', {
                                    action: 'deleteWallet',
                                    wallet_id: walletId,
                                    user_id: userId // Add user_id
                                })
                                    .then(response => {
                                        if (response.data && response.data.success) {
                                            alert('Wallet deleted successfully!');
                                            fetchUserWallets(); // Refetch wallets
                                        } else {
                                            alert('Failed to delete wallet: ' + (response.data.message || 'Unknown error'));
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error deleting wallet:', error);
                                        alert('Could not delete wallet. Please check the console for details.');
                                    });
                            }
                        });
                    });

                    // Add event listeners for rename buttons
                    document.querySelectorAll('.rename-wallet-button').forEach(button => {
                        button.addEventListener('click', (event) => {
                            const walletId = event.target.dataset.walletId;
                            const newWalletName = prompt('Enter new wallet name:');
                            if (newWalletName) {
                                axios.post('../../EcoPay_backend/V2/wallets.php', {
                                    action: 'renameWallet',
                                    wallet_id: walletId,
                                    new_name: newWalletName,
                                    user_id: userId
                                })
                                    .then(response => {
                                        if (response.data && response.data.success) {
                                            alert('Wallet renamed successfully!');
                                            fetchUserWallets(); // Refetch wallets
                                        } else {
                                            alert('Failed to rename wallet: ' + (response.data.message || 'Unknown error'));
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error renaming wallet:', error);
                                        alert('Could not rename wallet. Please check the console for details.');
                                    });
                            }
                        });
                    });

                    // Add event listeners for set default buttons
                    document.querySelectorAll('.set-default-wallet-button').forEach(button => {
                        button.addEventListener('click', (event) => {
                            const walletId = event.target.dataset.walletId;
                            axios.post('../../EcoPay_backend/V2/wallets.php', {
                                action: 'setDefaultWallet',
                                wallet_id: walletId,
                                user_id: userId
                            })
                            .then(response => {
                                if (response.data && response.data.success) {
                                    alert('Default wallet set successfully!');
                                    fetchUserWallets(); // Refetch wallets
                                } else {
                                    alert('Failed to set default wallet: ' + (response.data.message || 'Unknown error'));
                                }
                            })
                            .catch(error => {
                                console.error('Error setting default wallet:', error);
                                alert('Could not set default wallet. Please check the console for details.');
                            });
                        });
                    });

                     // Add event listeners for switch balance buttons
                     document.querySelectorAll('.switch-balance-wallet-button').forEach(button => {
                        button.addEventListener('click', (event) => {
                            console.log("Switch balance button clicked"); // Add this line
                            const fromWalletId = event.target.dataset.walletId;

                            // Show modal
                            const switchBalanceModal = document.getElementById('switchBalanceModal');
                            switchBalanceModal.style.display = 'block';

                            // Populate dropdown list of wallets
                            let dropdown = document.getElementById('toWalletId');
                            dropdown.innerHTML = '';
                            walletsData.forEach(wallet => {
                                if (wallet.wallet_id != fromWalletId) {
                                    let option = document.createElement('option');
                                    option.value = wallet.wallet_id;
                                    option.text = `${wallet.wallet_name} (${wallet.wallet.id})`;
                                    dropdown.add(option);
                                }
                            });

                            // Add event listener for submit button
                            document.getElementById('switchBalanceSubmit').addEventListener('click', () => {
                                const toWalletId = document.getElementById('toWalletId').value;
                                const amount = document.getElementById('amount').value;

                                if (toWalletId && amount) {
                                    axios.post('../../EcoPay_backend/V2/wallets.php', {
                                        action: 'switchBalance',
                                        from_wallet_id: fromWalletId,
                                        to_wallet_id: toWalletId,
                                        amount: amount
                                    })
                                    .then(response => {
                                        if (response.data && response.data.success) {
                                            alert('Balance switched successfully!');
                                            fetchUserWallets(); // Refetch wallets
                                        } else {
                                            alert('Failed to switch balance: ' + (response.data.message || 'Unknown error'));
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error switching balance:', error);
                                        alert('Could not switch balance. Please check the console for details.');
                                    });
                                } else {
                                    alert('Please enter the wallet ID and amount to transfer.');
                                }

                                // Remove modal
                                switchBalanceModal.style.display = 'none';
                            });
                        });
                    });

                    if (walletsData.length <= 1) {
                        document.querySelectorAll('.delete-wallet-button').forEach(button => {
                            button.disabled = true;
                            button.classList.add('disabled');
                        });
                        document.querySelectorAll('.switch-balance-wallet-button').forEach(button => {
                            button.disabled = true;
                            button.classList.add('disabled');
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching wallet data:', error);
                alert('Could not load wallet data. Please check the console for details.');
            });
    }

    fetchUserId()
        .then(() => {
            fetchUserInfo();
            fetchUserWallets()
        });

    createWalletButton.addEventListener('click', () => {
        let walletName = prompt('Enter wallet name:');
        if (walletName) {
            axios.post('../../EcoPay_backend/V2/wallets.php', {
                action: 'createWallet',
                wallet_name: walletName,
                user_id: userId
            })
            .then(response => {
                if (response.data && response.data.success) {
                    alert('Wallet created successfully!');
                    fetchUserWallets();
                } else {
                    alert('Failed to create wallet: ' + (response.data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error creating wallet:', error);
                alert('Could not create wallet. Please check the console for details.');
            });
        }
    });

    const generateRequestButton = document.getElementById('generate-request-button');
    const qrCodeContainer = document.getElementById('qr-code-container');
    const qrSendModal = document.getElementById('qrSendModal');
    const qrSendWalletIdSelect = document.getElementById('qrSendWalletId');
    const qrSendAmountInput = document.getElementById('qrSendAmount');
    const generateQrCodeButton = document.getElementById('generateQrCodeButton');
    const closeQrSendModalButton = document.getElementById('closeQrSendModalButton');

    generateRequestButton.addEventListener('click', () => {
        qrSendModal.style.display = 'block';
        qrSendWalletIdSelect.innerHTML = ''; // Clear previous options
        walletsData.forEach(wallet => {
            let option = document.createElement('option');
            option.value = wallet.wallet_id;
            option.text = `${wallet.wallet_name} (${wallet.currency})`;
            qrSendWalletIdSelect.add(option);
        });

        document.getElementById('generateQrCodeButton').addEventListener('click', () => {
            qrCodeContainer.innerHTML = ""; // Clear previous QR code
            const selectedWalletId = qrSendWalletIdSelect.value;
            const amount = qrSendAmountInput.value;

            axios.post('../../EcoPay_backend/V2/create_qr_code.php', {
                user_id: userId,
                wallet_id: selectedWalletId,
                amount: amount
            })
            .then(response => {
                if (response.data && response.data.qr_code_id) {
                    const qrCodeId = response.data.qr_code_id;
                    // Construct the full URL with parameters
                    const apiLink = `http://192.168.137.1/Project_EcoPay/EcoPay_user/p2p/p2p.html?qr_code_id=${qrCodeId}`;

                    const qrData = apiLink;
                    console.log("Generated QR Code URL:", apiLink);
                    const qrcode = new QRCode(qrCodeContainer, {
                        text: qrData,
                        width: 128,
                        height: 128,
                        colorDark: "#000",
                        colorLight: "#fff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } else {
                    alert('Failed to generate QR code.');
                }
            })
            .catch(error => {
                console.error('Error generating QR code:', error);
                alert('Could not generate QR code. Please check the console for details.');
            });
        });
    });

    closeQrSendModalButton.addEventListener('click', () => {
        qrSendModal.style.display = 'none';
        qrCodeContainer.innerHTML = ''; // Clear QR code when modal is closed
    });

    // The QR Receive functionality will be implemented in p2p.js
});
