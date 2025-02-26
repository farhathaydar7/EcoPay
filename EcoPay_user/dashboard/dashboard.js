document.addEventListener('DOMContentLoaded', () => {
    const userNameSpan = document.getElementById('user-name');
    const userEmailSpan = document.getElementById('user-email');
    const userBalanceSpan = document.getElementById('user-balance');

    // Fetch user info and wallet balance from backend
    axios.post('../../EcoPay_backend/profile.php')
        .then(response => {
            console.log("Full API Response:", response.data); // Debugging log

            if (response.data && response.data.user) {
                const userData = response.data.user;
                userNameSpan.textContent = userData.name;
                userEmailSpan.textContent = userData.email;
                userBalanceSpan.textContent = userData.balance;
            } else {
                throw new Error("User data not found in response.");
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            alert('Could not load dashboard data. Please check the console for details.');
            console.log('Full error response:', error.response); // Log full error response
        });
    });