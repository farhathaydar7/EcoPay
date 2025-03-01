document.addEventListener('DOMContentLoaded', () => {
    const messageDiv = document.getElementById('message');
    const sentP2pTransactionTableBody = document.querySelector('#sentP2pTransactionTable tbody');
    const receivedP2pTransactionTableBody = document.querySelector('#receivedP2pTransactionTable tbody');
    const otherTransactionTableBody = document.querySelector('#otherTransactionTable tbody');

    async function fetchTransactionHistory() {
        try {
            const [regularResponse, receivedP2pResponse, sentP2pResponse] = await Promise.all([
                axios.get('../../EcoPay_backend/V2/transactions_history_regular.php'),
                axios.get('../../EcoPay_backend/V2/transactions_history_received_p2p.php'),
                axios.get('../../EcoPay_backend/V2/transactions_history_sent_p2p.php')
            ]);

            if (regularResponse.data) {
                regularResponse.data.forEach(transaction => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${transaction.type}</td>
                        <td>${transaction.amount}</td>
                        <td>${transaction.status}</td>
                        <td>${transaction.timestamp}</td>
                    `;
                    otherTransactionTableBody.appendChild(row);
                });
            }

            if (receivedP2pResponse.data) {
                receivedP2pResponse.data.forEach(transaction => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${transaction.type} from ${transaction.receiver}</td>
                        <td>${transaction.amount}</td>
                        <td>${transaction.status}</td>
                        <td>${transaction.timestamp}</td>
                        <td>(${transaction.receiver_email})</td>
                    `;
                    receivedP2pTransactionTableBody.appendChild(row);
                });
            }

            if (sentP2pResponse.data) {
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
