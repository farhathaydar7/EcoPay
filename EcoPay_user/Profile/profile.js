document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profile-form');
    const messageDiv = document.getElementById('message');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const balanceInput = document.getElementById('balance');
    const addressInput = document.getElementById('address');
    const dobInput = document.getElementById('dob');
    const profilePicInput = document.getElementById('profile_pic');
    const idDocumentInput = document.getElementById('id_document');

    // Fetch user profile data on page load
    axios.post('../../EcoPay_backend/profile.php')
        .then(response => {
            if (response.data.status === 'success') {
                const user = response.data.user;
                nameInput.value = user.name;
                emailInput.value = user.email;
                balanceInput.value = user.balance;
                addressInput.value = user.address || '';
                dobInput.value = user.dob || '';
            } else {
                messageDiv.textContent = 'Error loading profile: ' + response.data.message;
                messageDiv.classList.add('error');
            }
        })
        .catch(error => {
            console.error('Error fetching profile data:', error);
            messageDiv.textContent = 'Failed to fetch profile data.';
            messageDiv.classList.add('error');
        });

    profileForm.addEventListener('submit', function(event) {
        event.preventDefault();
        messageDiv.textContent = '';
        messageDiv.classList.remove('success', 'error');

        const formData = new FormData(profileForm);

        axios.post('../../EcoPay_backend/profile.php', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then(response => {
            if (response.data.status === 'success') {
                messageDiv.textContent = response.data.message;
                messageDiv.classList.add('success');

                // Update displayed user info after successful update
                const user = response.data.user;
                nameInput.value = user.name;
                emailInput.value = user.email;
                balanceInput.value = user.balance;
            } else {
                messageDiv.textContent = response.data.message;
                messageDiv.classList.add('error');
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            messageDiv.textContent = 'Failed to update profile.';
            messageDiv.classList.add('error');
        });
    });
});