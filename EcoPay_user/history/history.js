document.addEventListener('DOMContentLoaded', () => {
    const messageDiv = document.getElementById('message');
    const sentP2pTransactionTableBody = document.querySelector('#sentP2pTransactionTable tbody');
    const receivedP2pTransactionTableBody = document.querySelector('#receivedP2pTransactionTable tbody');
    const otherTransactionTableBody = document.querySelector('#otherTransactionTable tbody');

    async function fetchTransactionHistory() {
        try {
            const [regularResponse, receivedP2pResponse, sentP2pResponse] = await Promise.all([
                axios.get('http://52.47.95.15/EcoPay_backend/V2/transactions_history_regular.php'),
                axios.get('http://52.47.95.15/EcoPay_backend/V2/transactions_history_received_p2p.php'),
                axios.get('http://52.47.95.15/EcoPay_backend/V2/transactions_history_sent_p2p.php')
            ]);

            console.log("Received P2P Response:", receivedP2pResponse.data);

            if (regularResponse.data) {
                regularResponse.data.forEach(transaction => {
                    const row = document.createElement('tr');
                    let receiverInfo = '';
                    if (transaction.receiver) {
                        receiverInfo = ` from ${transaction.receiver}`;
                    }
                    row.innerHTML = `
                        <td>${transaction.type}${receiverInfo}</td>
                        <td>${transaction.amount}</td>
                        <td>${transaction.status}</td>
                        <td>${transaction.timestamp}</td>
                    `;
                    otherTransactionTableBody.appendChild(row);
                });
            }

            if (Array.isArray(receivedP2pResponse.data) && receivedP2pResponse.data.length > 0) {
                receivedP2pResponse.data.forEach(transaction => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${transaction.type} from ${transaction.fName} ${transaction.lName}</td>
                        <td>${transaction.amount}</td>
                        <td>${transaction.status}</td>
                        <td>${transaction.timestamp}</td>
                        <td>(${transaction.sender_email})</td>
                    `;
                    receivedP2pTransactionTableBody.appendChild(row);
                });
            } else {
                console.warn("No received P2P transactions found:", receivedP2pResponse.data);
            }

            if (Array.isArray(sentP2pResponse.data)) {
                sentP2pResponse.data.forEach(transaction => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${transaction.type} to ${transaction.receiver}</td>
                        <td>${transaction.amount}</td>
                        <td>${transaction.status}</td>
                        <td>${transaction.timestamp}</td>
                        <td>(${transaction.receiver_email})</td>
                    `;
                    sentP2pTransactionTableBody.appendChild(row);
                });
            } else {
                console.error('sentP2pResponse.data is not an array:', sentP2pResponse.data);
            }

            if (
                regularResponse.data.length === 0 &&
                receivedP2pResponse.data.length === 0 &&
                sentP2pResponse.data.length === 0
            ) {
                messageDiv.textContent = 'No transaction history found.';
            }
        } catch (error) {
            console.error('Error fetching transaction history:', error);
            messageDiv.textContent = 'Failed to fetch transaction history. Please try again.';
        }
    }

    fetchTransactionHistory();
});
