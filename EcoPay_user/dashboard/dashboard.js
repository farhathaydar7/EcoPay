document.addEventListener('DOMContentLoaded', () => {
    const userNameSpan = document.getElementById('user-name');
    const userEmailSpan = document.getElementById('user-email');
    const userBalanceSpan = document.getElementById('user-balance');

    // Fetch user info and wallet balance from backend
    axios.post('../../EcoPay_backend/profile.php')
        .then(response => {
            const userData = response.data;
            userNameSpan.textContent = userData.name;
            userEmailSpan.textContent = userData.email;
            userBalanceSpan.textContent = userData.balance; // Make sure backend returns balance
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            alert('Could not load dashboard data. Please check the console for details.');
            console.log('Full error response:', error.response); // Log full error response
        });
    });