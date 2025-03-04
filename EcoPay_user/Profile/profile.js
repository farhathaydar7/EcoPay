document.addEventListener("DOMContentLoaded", () => {
  const profileForm = document.getElementById("profile-form");
  const messageDiv = document.getElementById("message");
  const nameInput = document.getElementById("name");
  const emailInput = document.getElementById("email");
  const addressInput = document.getElementById("address");
  const dobInput = document.getElementById("dob");
  const profilePicInput = document.getElementById("profile_pic");
  const idDocumentInput = document.getElementById("id_document");
  const idDocumentContainer = document.getElementById("id-document-container");
  const idDocumentDisplay = document.getElementById("id-document-display");
  const profilePicDisplay = document.getElementById("profile-pic-display");

  // Function to display ID Document
  function displayIdDocument(idDocumentLink) {
    if (idDocumentLink) {
      const fileExtension = idDocumentLink.split(".").pop().toLowerCase();
      if (["jpg", "jpeg", "png"].includes(fileExtension)) {
        const idDocumentDisplay = document.getElementById(
          "id-document-display"
        );
        idDocumentDisplay.innerHTML = `
                <div style="max-width: 100%; overflow: hidden; display: flex; justify-content: center; align-items: center;">
                    <img src="../../uploads/${idDocumentLink}" 
                         alt="Uploaded ID" 
                         style="margin: 20px auto;
                                padding: 15px;
                                background-color: #f0f0f0;
                                border: 1px solid #ced4da;
                                border-radius: 3;
                                max-width: 90%;
                                min-width: 200px;
                                display: block;
                                box-sizing: border-box;">
                </div>`;
      } else if (fileExtension === "pdf") {
        idDocumentDisplay.innerHTML = `<a href="../../uploads/${idDocumentLink}" target="_blank">View Uploaded ID (PDF)</a>`;
      }
      idDocumentInput.disabled = true; // Disable input after upload
    } else {
      // No document: show input field
      idDocumentDisplay.innerHTML = "";
      idDocumentInput.disabled = false; // Enable input if no document
    }
  }

  // Fetch user profile data on page load
  axios
    .get("../../EcoPay_backend/V2/profile.php")
    .then((response) => {
      if (response.data.status === "success") {
        const userData = response.data.user;

        // Set name and email at the top (Non-editable)
        nameInput.value = `${userData.fName} ${userData.lName}`;
        emailInput.value = userData.email;
        addressInput.value = userData.address || "";
        dobInput.value = userData.dob || "";

        // Profile Picture (Non-Editable)
        if (userData.profile_pic) {
          profilePicDisplay.src = `../../uploads/${userData.profile_pic}`;
          profilePicDisplay.style.display = "block";
        } else {
          profilePicDisplay.style.display = "none";
        }

        // ID Document (Like Profile Picture)
        axios
          .get("../../EcoPay_backend/V2/get_id_doc.php")
          .then((response) => {
            if (response.data.status === "success") {
              const idDocumentLink = response.data.id_document;
              displayIdDocument(idDocumentLink);
            } else {
              console.error(
                "Error fetching ID document:",
                response.data.message
              );
              messageDiv.textContent =
                "Error loading ID document: " + response.data.message;
              messageDiv.classList.add("error");
            }
          })
          .catch((error) => {
            console.error("Error fetching ID document:", error);
            messageDiv.textContent = "Failed to fetch ID document.";
            messageDiv.classList.add("error");
          });
      } else {
        messageDiv.textContent =
          "Error loading profile: " + response.data.message;
        messageDiv.classList.add("error");
      }
    })
    .catch((error) => {
      console.error("Error fetching profile data:", error);
      messageDiv.textContent = "Failed to fetch profile data.";
      messageDiv.classList.add("error");
    });

  // Form Submission with DOB Validation (Must be over 18)
  profileForm.addEventListener("submit", function (event) {
    event.preventDefault();
    messageDiv.textContent = "";
    messageDiv.classList.remove("success", "error", "warning");

    // Validate DOB (User must be over 18)
    const dob = new Date(dobInput.value);
    const today = new Date();
    const age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    if (age < 18 || (age === 18 && monthDiff < 0)) {
      messageDiv.textContent = "You must be at least 18 years old.";
      messageDiv.classList.add("error");
      return;
    }

    const formData = new FormData(profileForm);

    axios
      .post("../../EcoPay_backend/V2/update_profile.php", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      })
      .then((response) => {
        if (response.data.status === "success") {
          messageDiv.textContent = response.data.message;
          messageDiv.classList.add("success");

          // Refresh Document Display
          axios
            .get("../../EcoPay_backend/V2/get_id_doc.php")
            .then((response) => {
              if (response.data.status === "success") {
                const idDocumentLink = response.data.id_document;
                displayIdDocument(idDocumentLink);
              } else {
                console.error(
                  "Error fetching ID document:",
                  response.data.message
                );
                messageDiv.textContent =
                  "Error loading ID document: " + response.data.message;
                messageDiv.classList.add("error");
              }
            })
            .catch((error) => {
              console.error("Error fetching ID document:", error);
              messageDiv.textContent = "Failed to fetch ID document.";
              messageDiv.classList.add("error");
            });
        } else {
          messageDiv.textContent = response.data.message;
          messageDiv.classList.add("error");
        }
      })
      .catch((error) => {
        console.error("Error updating profile:", error);
        messageDiv.textContent = "Failed to update profile.";
        messageDiv.classList.add("error");
      });
  });

  const editProfileBtn = document.getElementById("edit-profile-btn");
  const saveProfileBtn = document.getElementById("save-profile-btn");
  const editableInputs = document.querySelectorAll(".editable-input");

  editProfileBtn.addEventListener("click", function () {
    editableInputs.forEach((input) => {
      if (input !== profilePicInput) {
        // Prevent profile pic change
        input.removeAttribute("readonly");
      }
    });
    editProfileBtn.style.display = "none";
    saveProfileBtn.style.display = "block";
  });

  saveProfileBtn.addEventListener("click", function () {
    profileForm.requestSubmit(); // Submit form programmatically
  });
});
