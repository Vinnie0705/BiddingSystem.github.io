function openSignupModal() {
    document.getElementById("signupModal").style.display = "block";
    document.getElementById("signinModal").style.display = "none";
    document.getElementById("forgotPasswordModal").style.display = "none";
}

function openSigninModal() {
    document.getElementById("signinModal").style.display = "block";
    document.getElementById("signupModal").style.display = "none";
    document.getElementById("forgotPasswordModal").style.display = "none";
}

function openForgotPasswordModal() {
    document.getElementById("forgotPasswordModal").style.display = "block";
    document.getElementById("signinModal").style.display = "none";
    document.getElementById("signupModal").style.display = "none";
}

function closeSignupModal() {
    document.getElementById("signupModal").style.display = "none";
}

function closeSigninModal() {
    document.getElementById("signinModal").style.display = "none";
}

function closeForgotPasswordModal() {
    document.getElementById("forgotPasswordModal").style.display = "none";
}

// Close modals when clicking outside them
window.onclick = function(event) {
    const modals = document.getElementsByClassName("modal");
    for (let i = 0; i < modals.length; i++) {
        if (event.target == modals[i]) {
            modals[i].style.display = "none";
        }
    }
};
