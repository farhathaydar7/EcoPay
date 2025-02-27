document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profile-form');
    const messageDiv = document.getElementById('message');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const addressInput = document.getElementById('address');
    const dobInput = document.getElementById('dob');
    const profilePicInput = document.getElementById('profile_pic');
    const idDocumentInput = document.getElementById('id_document');

    // Fetch user profile data on page load
    axios.get('../../EcoPay_backend/profile.php')
        .then(response => {
            if (response.data.status === 'success') {
                const userData = response.data.user;
                nameInput.value = userData.name;
                emailInput.value = userData.email;
                addressInput.value = userData.address || '';
                dobInput.value = userData.dob || '';

                // Fetch and display profile picture
                axios.get('../../EcoPay_backend/get_pfp.php')
                    .then(pfpResponse => {
                        if (pfpResponse.data.status === 'success' && pfpResponse.data.profile_pic_path) {
                            const profilePic = document.getElementById('profile-pic-display');
                            profilePic.src = '../../uploads' + pfpResponse.data.profile_pic_path;
                            profilePic.style.display = 'block';
                        } else {
                            document.getElementById('profile-pic-display').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching profile picture:', error);
                    });

                // Check and display document verification status (rest of your code)
                const idDocumentInput = document.getElementById('id_document');
                if (userData.document_verified === 1) {
                    messageDiv.textContent = 'ID Document Verified.';
                    messageDiv.classList.add('success');
                    idDocumentInput.disabled = true;
                } else if (userData.document_verified === 0) {
                    messageDiv.textContent = 'ID Document Pending Verification.';
                    messageDiv.classList.add('error'); // Or use a different class like 'warning'
                    idDocumentInput.disabled = true; // Disable file input if pending
                } else {
                    idDocumentInput.disabled = false; // Enable if no verification status
                }

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

        // Send profile update data (POST request to update_profile.php)
        axios.post('../../EcoPay_backend/update_profile.php', formData, {
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
                addressInput.value = user.address || '';
                dobInput.value = user.dob || '';

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

    const editProfileBtn = document.getElementById('edit-profile-btn');
    const saveProfileBtn = document.getElementById('save-profile-btn');
    const editableInputs = document.querySelectorAll('.editable-input');

    editProfileBtn.addEventListener('click', function() {
        editableInputs.forEach(input => {
            input.removeAttribute('readonly');
        });
        editProfileBtn.style.display = 'none';
        saveProfileBtn.style.display = 'block';
    });

    saveProfileBtn.addEventListener('click', function() {
        profileForm.requestSubmit(); // Programmatically submit the form
    });
});