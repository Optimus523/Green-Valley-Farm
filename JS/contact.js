const form = document.getElementById("contact-form");
const first_name = document.getElementById("first_name");
const last_name = document.getElementById("last_name");
const email = document.getElementById("email");
const message = document.getElementById("message");

function showError(fieldId, text) {
    document.getElementById("error-" + fieldId).textContent = text;
    document.getElementById(fieldId).classList.add("is-invalid");
}
function clearError(fieldId) {
    document.getElementById("error-" + fieldId).textContent = "";
    document.getElementById(fieldId).classList.remove("is-invalid");
}

function validateForm(event) {
    // stop form from submitting normally
    event.preventDefault();

    // clear all errors first
    clearError("first_name");
    clearError("last_name");
    clearError("email");
    clearError("message");

    // Assume valid until proven
    let valid = true;

    // firstname
    if (first_name.value.trim() === "") {
        showError("first_name", "First Name is required.");
        valid = false;
    } else if (first_name.value.trim().length < 2) {
        showError("first_name", "At least 2 characters");
        valid = false;
    }

    // last name
    if (last_name.value.trim() === "") {
        showError("last_name", "Last Name is required.");
        valid = false;
    } else if (last_name.value.trim().length < 2) {
        showError("last_name", "At least 2 characters");
        valid = false;
    }

    //email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.value.trim() === "") {
        showError("email", "Email is required.");
        valid = false;
    } else if (!emailRegex.test(email.value.trim())) {
        showError("email", "Please enter a valid email");
        valid = false;
    }

    //message
    if (message.value.trim() === "") {
        showError("message", "Message is required.");
        valid = false;
    } else if (message.value.trim().length < 10) {
        showError("message", "Message must be at least 10 characters");
        valid = false;
    }

    // if the check passes
    if (valid) {
        form.submit();
    }
}

form.addEventListener("submit", validateForm);

["first_name", "last_name", "email", "message"].forEach(function (id) {
    document.getElementById(id).addEventListener("input", function () {
        clearError(id);
    });
});
