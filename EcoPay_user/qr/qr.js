// qr.js - This handles fetching wallets, QR data, and submitting a QR transfer.
document.addEventListener('DOMContentLoaded', function () {
    const qrForm = document.getElementById('qrForm');
    const messageDiv = document.getElementById('message');
    const senderWalletSelect = document.getElementById('sender_wallet_id');
    const amountInput = document.getElementById('amount');
    const API_ENDPOINT = 'http://localhost/Project_EcoPay/EcoPay_backend/V2/';
  
    // Helper: Show message
    function showMessage(message, type = 'error') {
      messageDiv.textContent = message;
      messageDiv.className = 'message ' + type;
    }
  
    // Helper: Extract query parameter by name
    function getParameterByName(name, url = window.location.href) {
      name = name.replace(/[\[\]]/g, '\\$&');
      const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
      const results = regex.exec(url);
      if (!results) return null;
      if (!results[2]) return '';
      return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
  
    // Get QR code ID from URL parameter
const qrCodeId = getParameterByName('data');
    if (!qrCodeId) {
      showMessage('QR code ID missing from URL.');
      return;
    }
  
    // Fetch QR code data and populate amount field
    fetch(`${API_ENDPOINT}get_qr_code_data.php?data=${qrCodeId}`, {
        method: "GET",
        headers: {
            "Content-Type": "application/json"
        }
    })
    .then(response => response.json())
    .then(function(response) {
      console.log("✅ QR API Response:", response);
      if (response.success && response.qr_code) {
        const qrData = response.qr_code;
        if (amountInput) {
          amountInput.value = qrData.amount;
          amountInput.readOnly = true;
        }
      } else {
        showMessage(response.error || 'Failed to load QR code data.');
      }
    })
    .catch(function(error) {
      console.error('Error fetching QR code data:', error);
      showMessage('Error loading QR data.');
    });
  
    // Populate sender wallets
    function populateWallets() {
      axios.get(`http://192.168.137.1/Project_EcoPay/EcoPay_backend/V2/get_wallets.php`)
      .then(function(response) {
        console.log("✅ Wallets API Response:", response.data);
        if (response.data.status === 'success' && Array.isArray(response.data.wallets)) {
          response.data.wallets.forEach(function(wallet) {
            const option = document.createElement('option');
            option.value = wallet.wallet_id;
            option.textContent = `${wallet.wallet_name} (${wallet.currency})`;
            senderWalletSelect.appendChild(option);
          });
        } else {
          showMessage(response.data.error || 'Failed to load wallets.');
        }
      })
      .catch(function(error) {
        console.error('Error fetching wallets:', error);
        showMessage('Error loading wallets.');
      });
    }
    
    populateWallets();
  
    // Handle form submission for QR transfer
    if (qrForm) {
      qrForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const senderWalletId = senderWalletSelect.value;
        const amount = amountInput.value;
        
        if (!senderWalletId) {
          showMessage('Please select a wallet.');
          return;
        }
        
        // Prepare request data for QR transfer
        const requestData = {
          qr_code_id: qrCodeId,
          sender_wallet_id: senderWalletId,
          amount: amount  // Value comes from the QR code data
        };
        
        axios.post(`${API_ENDPOINT}qr.php`, requestData, {
          headers: { 'Content-Type': 'application/json' }
        })
        .then(function(response) {
          if (response.data && response.data.status === 'success') {
            showMessage('QR transfer successful!', 'success');
            // Optionally, perform redirection or form reset here.
          } else {
            showMessage('QR transfer successful!', 'success');

          }
        })
        .catch(function(error) {
          console.error('Error processing QR transfer:', error);
          showMessage('QR transfer submission failed.');
        });
      });
    } else {
      console.error("QR form element with id 'qrForm' not found.");
    }
  });
