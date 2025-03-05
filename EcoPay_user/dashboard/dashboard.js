// wallet_dashboard.js
document.addEventListener('DOMContentLoaded', () => {
  const userNameSpan = document.getElementById('user-name');
  const userEmailSpan = document.getElementById('user-email');
  const walletsList = document.getElementById('wallets-list');
  const createWalletButton = document.getElementById('create-wallet-button');

  // Elements for QR generation
  const generateRequestButton = document.getElementById('generate-request-button');
  const qrSendModal = document.getElementById('qrSendModal');
  const qrSendWalletIdSelect = document.getElementById('qrSendWalletId');
  const qrSendAmountInput = document.getElementById('qrSendAmount');
  const generateQrCodeButton = document.getElementById('generateQrCodeButton');
  const closeQrSendModalButton = document.getElementById('closeQrSendModalButton');
  const qrCodeContainer = document.getElementById('qr-code-container');

  // Elements for switching balance
  const switchBalanceModal = document.getElementById('switchBalanceModal');
  const switchBalanceSubmitButton = document.getElementById('switchBalanceSubmit');
  const toWalletIdSelect = document.getElementById('toWalletId');
  const switchAmountInput = document.getElementById('switchAmount');

  let userId;
  let walletsData = [];

  function fetchUserId() {
    const storedUser = localStorage.getItem('user');
    console.log('Stored user:', storedUser);
    const userData = storedUser ? JSON.parse(storedUser) : null;
    if (userData && userData.id) {
      userId = userData.id;
      return Promise.resolve(userId);
    } else {
      console.error('User data is missing from localStorage.');
      alert('Could not load user ID. Please check the console for details.');
      return Promise.reject(new Error("User data is missing from localStorage."));
    }
  }

  function fetchUserInfo() {
    axios.get('http://52.47.95.15/EcoPay_backend/V2/profile.php')
      .then(response => {
        if (response.data && response.data.user) {
          const userData = response.data.user;
          if (userNameSpan) userNameSpan.textContent = userData.name;
          if (userEmailSpan) userEmailSpan.textContent = userData.email;
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
    return axios.get('http://52.47.95.15/EcoPay_backend/V2/get_wallets.php')
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

            // Create buttons for wallet actions
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

          addWalletEventListeners();
        }
      })
      .catch(error => {
        console.error('Error fetching wallet data:', error);
        alert('Could not load wallet data. Please check the console for details.');
      });
  }

  function addWalletEventListeners() {
    // Delete wallet
    document.querySelectorAll('.delete-wallet-button').forEach(button => {
      button.addEventListener('click', (event) => {
        const walletId = event.target.dataset.walletId;
        if (confirm('Are you sure you want to delete this wallet?')) {
          axios.post('http://52.47.95.15/EcoPay_backend/V2/wallets.php', {
            action: 'deleteWallet',
            wallet_id: walletId,
            user_id: userId
          })
          .then(response => {
            if (response.data && response.data.success) {
              alert('Wallet deleted successfully!');
              fetchUserWallets();
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

    // Rename wallet
    document.querySelectorAll('.rename-wallet-button').forEach(button => {
      button.addEventListener('click', (event) => {
        const walletId = event.target.dataset.walletId;
        const newWalletName = prompt('Enter new wallet name:');
        if (newWalletName) {
          axios.post('http://52.47.95.15/EcoPay_backend/V2/wallets.php', {
            action: 'renameWallet',
            wallet_id: walletId,
            new_name: newWalletName,
            user_id: userId
          })
          .then(response => {
            if (response.data && response.data.success) {
              alert('Wallet renamed successfully!');
              fetchUserWallets();
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

    // Set default wallet
    document.querySelectorAll('.set-default-wallet-button').forEach(button => {
      button.addEventListener('click', (event) => {
        const walletId = event.target.dataset.walletId;
        axios.post('http://52.47.95.15/EcoPay_backend/V2/wallets.php', {
          action: 'setDefaultWallet',
          wallet_id: walletId,
          user_id: userId
        })
        .then(response => {
          if (response.data && response.data.success) {
            alert('Default wallet set successfully!');
            fetchUserWallets();
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

    // Switch balance between wallets
    document.querySelectorAll('.switch-balance-wallet-button').forEach(button => {
      button.addEventListener('click', (event) => {
        const fromWalletId = event.target.dataset.walletId;
        // Show modal for switching balance
        switchBalanceModal.style.display = 'block';
        // Populate target wallet dropdown (exclude current wallet)
        toWalletIdSelect.innerHTML = '';
        walletsData.forEach(wallet => {
          if (wallet.wallet_id != fromWalletId) {
            const option = document.createElement('option');
            option.value = wallet.wallet_id;
            option.text = `${wallet.wallet_name} (${wallet.currency})`;
            toWalletIdSelect.add(option);
          }
        });
        // Listen for switch balance submit
        switchBalanceSubmitButton.onclick = () => {
          const toWalletId = toWalletIdSelect.value;
          const amount = switchAmountInput.value;
          if (toWalletId && amount) {
            axios.post('http://52.47.95.15/EcoPay_backend/V2/wallets.php', {
              action: 'switchBalance',
              from_wallet_id: fromWalletId,
              to_wallet_id: toWalletId,
              amount: amount
            })
            .then(response => {
              if (response.data && response.data.success) {
                alert('Balance switched successfully!');
                fetchUserWallets();
              } else {
                alert('Failed to switch balance: ' + (response.data.message || 'Unknown error'));
              }
            })
            .catch(error => {
              console.error('Error switching balance:', error);
              alert('Could not switch balance. Please check the console for details.');
            });
          } else {
            alert('Please select a target wallet and specify an amount.');
          }
          switchBalanceModal.style.display = 'none';
        };
      });
    });

    // Disable deletion and balance switching if only one wallet exists
    if (walletsData.length <= 1) {
      document.querySelectorAll('.delete-wallet-button, .switch-balance-wallet-button').forEach(button => {
        button.disabled = true;
        button.classList.add('disabled');
      });
    }
  }

  // Create new wallet event
  createWalletButton.addEventListener('click', () => {
    const walletName = prompt('Enter wallet name:');
    if (walletName) {
      axios.post('http://52.47.95.15/EcoPay_backend/V2/wallets.php', {
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

  // -------------------------------
  // QR Code Generation and Linking
  // -------------------------------
  generateRequestButton.addEventListener('click', () => {
    // Open the QR send modal and populate wallet dropdown
    qrSendModal.style.display = 'block';
    qrSendWalletIdSelect.innerHTML = '';
    walletsData.forEach(wallet => {
      const option = document.createElement('option');
      option.value = wallet.wallet_id;
      option.text = `${wallet.wallet_name} (${wallet.currency})`;
      qrSendWalletIdSelect.add(option);
    });
  });

  generateQrCodeButton.addEventListener('click', () => {
    // Clear any existing QR code
    qrCodeContainer.innerHTML = "";
    const selectedWalletId = qrSendWalletIdSelect.value;
    const amount = qrSendAmountInput.value;
    // Request the backend to create a new QR code
    axios.post('http://52.47.95.15/EcoPay_backend/V2/create_qr_code.php', {
      user_id: userId,
      wallet_id: selectedWalletId,
      amount: amount
    })
    .then(response => {
      if (response.data && response.data.qr_code_id) {
        const qrCodeId = response.data.qr_code_id;
        // Construct the new link format for the QR page.
        const apiLink = `http://52.47.95.15/EcoPay_user/qr/qr.html?data=${qrCodeId}`;

        console.log("Generated QR Code URL:", apiLink);
        // Generate the QR code using the QRCode library
        new QRCode(qrCodeContainer, {
          text: apiLink,
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

  closeQrSendModalButton.addEventListener('click', () => {
    qrSendModal.style.display = 'none';
    qrCodeContainer.innerHTML = ''; // Clear QR code when closing modal
  });

  // Initialize data
  fetchUserId().then(() => {
    fetchUserInfo();
    fetchUserWallets();
  });
});
