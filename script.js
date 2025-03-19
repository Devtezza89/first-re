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
