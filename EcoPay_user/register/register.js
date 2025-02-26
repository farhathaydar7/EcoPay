document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('register-form');
    const messageDiv = document.getElementById('message');

    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const password = document.getElementById('password').value;

        if (!name || !email || !phone || !password) {
            messageDiv.textContent = 'All fields are required.';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('fullName', name);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('password', password);

            const response = await axios.post('../../EcoPay_backend/register.php', formData);

            messageDiv.textContent = response.data;
            if (response.data.includes('Registration successful')) {
                // Redirect or perform actions after successful registration
                // For example, redirect to login page
                // window.location.href = 'login.html';
            }
        } catch (error) {
            console.error('Registration failed:', error);
            messageDiv.textContent = 'Registration failed. Please check your information and try again.';
        }
    });
});