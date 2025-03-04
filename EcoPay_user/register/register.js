document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('register-form');
    const messageDiv = document.getElementById('message');
    const registerButton = document.getElementById('register-button');
    const otpGroup = document.getElementById('otp-group');
    const verifyOtpButton = document.getElementById('verify-otp-button');
    const resendOtpButton = document.getElementById('resend-otp-button');
    const userEmailInput = document.getElementById('userEmail');

   registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const userName = document.getElementById('userName').value;
        const fName = document.getElementById('fName').value;
        const lName = document.getElementById('lName').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!userName || !fName || !lName || !email || !password) {
            messageDiv.textContent = 'All fields are required.';
            return;
        }

        try {
            const userData = {
                userName: userName,
                fName: fName,
                lName: lName,
                email: email,
                password: password
            };

            const response = await axios.post('52.47.95.15/Ecopay/EcoPay_backend/V2/register.php', userData, {
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.data && response.data.status === 'success') {
                messageDiv.textContent = response.data.message;
                messageDiv.classList.remove('error');
                messageDiv.classList.add('success');

                // Hide registration fields and show OTP input
                document.getElementById('userName').style.display = 'none';
                document.getElementById('fName').style.display = 'none';
                document.getElementById('lName').style.display = 'none';
                document.getElementById('email').style.display = 'none';
                document.getElementById('password').style.display = 'none';
                registerButton.style.display = 'none';
                otpGroup.style.display = 'block';

                // Store the user's email
                userEmailInput.value = email;

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

    verifyOtpButton.addEventListener('click', async () => {
        const otp = document.getElementById('otp').value;
        const email = userEmailInput.value;

        if (!otp || !email) {
            messageDiv.textContent = 'OTP and email are required.';
            return;
        }

        try {
            const otpData = {
                otp: otp,
                email: email
            };

            const response = await axios.post('52.47.95.15/EcoPay/EcoPay_backend/V2/verify_otp.php', otpData, {
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.data && response.data.status === 'success') {
                messageDiv.textContent = response.data.message;
                messageDiv.classList.remove('error');
                messageDiv.classList.add('success');

                // Redirect to login page
                window.location.href = '../login/login.html';
            } else {
                messageDiv.textContent = response.data.message || 'OTP verification failed.';
                messageDiv.classList.remove('success');
                messageDiv.classList.add('error');
            }
        } catch (error) {
            console.error('OTP verification failed:', error);
            messageDiv.textContent = 'OTP verification failed. Please try again.';
            messageDiv.classList.remove('success');
            messageDiv.classList.add('error');
        }
    });

    resendOtpButton.addEventListener('click', async () => {
        const email = userEmailInput.value;

        if (!email) {
            messageDiv.textContent = 'Email is required to resend OTP.';
            return;
        }

        try {
            const resendData = {
                email: email
            };

            const response = await axios.post('52.47.95.15/EcoPay/EcoPay_backend/V2/email_verify.php', resendData, {
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            messageDiv.textContent = response.data.message;
            if (response.data && response.data.status === 'success') {
                messageDiv.classList.remove('error');
                messageDiv.classList.add('success');
            } else {
                messageDiv.classList.remove('success');
                messageDiv.classList.add('error');
            }

        } catch (error) {
            console.error('Resend OTP failed:', error);
            messageDiv.textContent = 'Resend OTP failed. Please try again.';
            messageDiv.classList.remove('success');
            messageDiv.classList.add('error');
        }
    });
});
