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
            const userData = {
                name: name,
                email: email,
                phone: phone,
                password: password
            };

            const response = await axios.post('../../EcoPay_backend/register.php', userData, {
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            // Ensure response is an object and check `status`
            if (response.data && response.data.status === 'success') {
                messageDiv.textContent = response.data.message;
                messageDiv.classList.remove('error');
                messageDiv.classList.add('success');
                // Redirect to login or dashboard if needed
                // window.location.href = 'login.html';
            } else {
                messageDiv.textContent = response.data.message || 'Registration failed.';
                messageDiv.classList.remove('success');
                messageDiv.classList.add('error');
            }
        } catch (error) {
            console.error('Registration failed:', error);
            messageDiv.textContent = 'Registration failed. Please check your information and try again.';
            messageDiv.classList.remove('success');
            messageDiv.classList.add('error');
        }
    });
});
