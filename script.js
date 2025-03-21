function showSignup() {
    document.getElementById('signup-form').classList.remove('hidden');
    document.getElementById('login-form').classList.add('hidden');
    document.getElementById('forgot-password-form').classList.add('hidden');
}

function showLogin() {
    document.getElementById('login-form').classList.remove('hidden');
    document.getElementById('signup-form').classList.add('hidden');
    document.getElementById('forgot-password-form').classList.add('hidden');
}

function showForgotPassword() {
    document.getElementById('forgot-password-form').classList.remove('hidden');
    document.getElementById('login-form').classList.add('hidden');
    document.getElementById('signup-form').classList.add('hidden');
}

function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const toggle = input.nextElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        toggle.innerHTML = '👀';
    } else {
        input.type = 'password';
        toggle.innerHTML = '👁';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const signupForm = document.getElementById('signupForm');
    const signupButton = document.getElementById('signupButton');
    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');
    const resetForm = document.getElementById('resetForm');
    const resetButton = document.getElementById('resetButton');

    function checkForm(form, button) {
        if (!form || !button) return;
        const inputs = form.querySelectorAll('input[required]');
        let allFilled = true;
        inputs.forEach(input => {
            if (!input.value.trim()) {
                allFilled = false;
            }
        });
        button.disabled = !allFilled;
    }

    if (signupForm) {
        signupForm.addEventListener('input', () => checkForm(signupForm, signupButton));
        checkForm(signupForm, signupButton);
    }

    if (loginForm) {
        loginForm.addEventListener('input', () => checkForm(loginForm, loginButton));
        checkForm(loginForm, loginButton);
    }

    if (resetForm) {
        resetForm.addEventListener('input', () => checkForm(resetForm, resetButton));
        checkForm(resetForm, resetButton);
    }
});
