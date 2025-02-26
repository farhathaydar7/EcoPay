document.addEventListener('DOMContentLoaded', () => {
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

            const response = await axios.post('../../EcoPay_backend/login.php', formData);

            messageDiv.textContent = response.data;
            if (response.data.includes('Login successful')) {
                // Redirect to dashboard after successful login
                window.location.href = '../dashboard/dashboard.html';
            }
        } catch (error) {
            console.error('Login failed:', error);
            messageDiv.textContent = 'Login failed. Please check your credentials and try again.';
        }
    });
});