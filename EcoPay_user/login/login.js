document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = '../../EcoPay_backend/';
    const loginForm = document.getElementById('login-form');
    const messageDiv = document.getElementById('message');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!email || !password) {
            messageDiv.textContent = 'Email and password are required.';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);

            const response = await axios.post(API_BASE_URL + 'login.php', formData);

            if (response.data.status === 'success') {
                messageDiv.textContent = response.data.message;
                const user = response.data.user;
                localStorage.setItem('user', JSON.stringify(user));
                window.location.href = '../dashboard/dashboard.html';
            } else {
                messageDiv.textContent = response.data.message;
            }
        } catch (error) {
            console.error('Login failed:', error);
            messageDiv.textContent = 'Login failed. Please check your credentials and try again.';
        }
    });
});
