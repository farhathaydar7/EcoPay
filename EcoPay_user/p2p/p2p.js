document.addEventListener('DOMContentLoaded', function () {
    const p2pForm = document.getElementById('p2pForm');
    const messageDiv = document.getElementById('message');
    const senderWalletSelect = document.getElementById('sender_wallet_id');
    const receivedP2pTransactionTableBody = document.getElementById('receivedP2pTransactionTableBody');
    const receiverIdentifierInput = document.getElementById('receiver_identifier');
    const amountInput = document.getElementById('amount');
    const receiverWalletIdInput = document.getElementById('receiver_wallet_id');
    let API_ENDPOINT = 'http://192.168.137.1/Project_EcoPay/EcoPay_backend/V2/';

    // Function to extract parameters from URL
    function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        let regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    // Check if user is logged in
    axios.get(API_ENDPOINT + 'get_user_id.php')
        .then(response => {
            if (!response.data || !response.data.userId) {
                // Redirect to login page if not logged in
                window.location.href = '../login/login.html';
                return;
            }

            const userId = response.data.userId;

            // Fetch user info to get email
            axios.get(API_ENDPOINT + 'profile.php')
                .then(profileResponse => {
                    if (profileResponse.data?.user?.email) {
                        const userEmail = profileResponse.data.user.email;
                        if (receiverIdentifierInput) {
                            receiverIdentifierInput.value = userEmail;
                            receiverIdentifierInput.readOnly = true;
                        }
                    } else if (messageDiv) {
                        messageDiv.textContent = 'Failed to load user profile.';
                        messageDiv.className = 'message error';
                    }
                })
                .catch(error => {
                    console.error('Error fetching user profile:', error);
                    if (messageDiv) {
                        messageDiv.textContent = 'Failed to load user profile.';
                        messageDiv.className = 'message error';
                    }
                });

            // Pre-fill receiver identifier and amount from QR code if available
            const qrCodeId = getParameterByName('qr_code_id');

            if (qrCodeId) {
                axios.post(API_ENDPOINT + 'get_qr_code_data.php', { qr_code_id: qrCodeId }, {
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(qrCodeResponse => {
                    if (qrCodeResponse.data?.success && qrCodeResponse.data.qr_code) {
                        const qrCodeData = qrCodeResponse.data.qr_code;
                        if (amountInput) {
                            amountInput.value = qrCodeData.amount;
                            amountInput.readOnly = true;
                        }
                        if (receiverWalletIdInput) {
                            receiverWalletIdInput.value = qrCodeData.wallet_id;
                        }
                    } else if (messageDiv) {
                        messageDiv.textContent = 'Failed to load QR code data.';
                        messageDiv.className = 'message error';
                    }
                })
                .catch(error => {
                    console.error('Error fetching QR code data:', error);
                    if (messageDiv) {
                        messageDiv.textContent = 'Failed to load QR code data.';
                        messageDiv.className = 'message error';
                    }
                });
            }

            // Fetch wallets and populate the select element
            async function populateWallets() {
                try {
                    const response = await axios.get(API_ENDPOINT + 'get_wallets.php');
                    console.log('Wallets response:', response.data);
                    if (response.data.status === 'success' && Array.isArray(response.data.wallets)) {
                        response.data.wallets.forEach(wallet => {
                            const option = document.createElement('option');
                            option.value = wallet.wallet_id;
                            option.textContent = `${wallet.wallet_name} (${wallet.currency})`;
                            if (senderWalletSelect) {
                                senderWalletSelect.appendChild(option);
                            }
                        });
                    } else if (messageDiv) {
                        messageDiv.textContent = 'Failed to load wallets.';
                        messageDiv.className = 'message error';
                    }
                } catch (error) {
                    console.error('Error fetching wallets:', error);
                    if (messageDiv) {
                        messageDiv.textContent = 'An error occurred while loading wallets.';
                        messageDiv.className = 'message error';
                    }
                }
            }

            if (senderWalletSelect) {
                populateWallets();
            }

            if (p2pForm) {
                p2pForm.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    const receiverIdentifier = receiverIdentifierInput?.value;
                    const senderWalletId = senderWalletSelect?.value;
                    const amount = amountInput?.value;

                    console.log("Form submitted:", { receiverIdentifier, senderWalletId, amount });

                    try {
                        const response = await axios.post(API_ENDPOINT + 'p2p_transfer.php', {
                                receiver_identifier: receiverIdentifier,
                                sender_wallet_id: senderWalletId,
                                amount: amount
                            }, {
                                headers: { "Content-Type": "application/json" }
                            });

                        console.log('P2P transfer response:', response.data);

                        if (response.data.status === 'success') {
                            if (messageDiv) {
                                messageDiv.textContent = 'Transfer successful!';
                                messageDiv.className = 'message success';
                            }
                            p2pForm.reset();
                        } else if (messageDiv) {
                            messageDiv.textContent = response.data.message || response.data.error || 'Transfer failed.';
                            messageDiv.className = 'message error';
                        }
                    } catch (error) {
                        console.error('P2P transfer error:', error);
                        if (messageDiv) {
                            messageDiv.textContent = 'An error occurred during the transfer.';
                            messageDiv.className = 'message error';
                        }
                    }
                });
            }

            // Fetch and display received P2P transactions
            async function fetchReceivedP2pTransactions() {
                try {
                    const response = await axios.get(API_ENDPOINT + 'transactions_history_received_p2p.php');
                    console.log('Received P2P transactions:', response.data);

                    if (response.data && Array.isArray(response.data)) {
                        response.data.forEach(transaction => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${transaction.type} from ${transaction.sender}</td>
                                <td>${transaction.amount}</td>
                                <td>${transaction.status}</td>
                                <td>${transaction.timestamp}</td>
                                <td>(${transaction.sender_email})</td>
                            `;
                            if (receivedP2pTransactionTableBody) {
                                receivedP2pTransactionTableBody.appendChild(row);
                            }
                        });
                    } else {
                        console.error('Response data is not an array:', response.data);
                        if (messageDiv) {
                            messageDiv.textContent = 'Failed to load received transactions.';
                            messageDiv.className = 'message error';
                        }
                    }
                } catch (error) {
                    console.error('Error fetching received P2P transactions:', error);
                    if (messageDiv) {
                        messageDiv.textContent = 'An error occurred while fetching received transactions.';
                        messageDiv.className = 'message error';
                    }
                }
            }

            fetchReceivedP2pTransactions();
        })
        .catch(error => {
            console.error('Error checking login status:', error);
            if (messageDiv) {
                messageDiv.textContent = 'An error occurred while checking login status.';
                messageDiv.className = 'message error';
            }
        });
});
