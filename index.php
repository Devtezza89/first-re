<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'authentication.php';

checkRememberMe();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signup'])) {
        handleSignup();
    } elseif (isset($_POST['login'])) {
        handleLogin();
    } elseif (isset($_POST['reset'])) {
        handleReset();
    }
}

$form = filter_var($_GET['form'] ?? 'signup', FILTER_SANITIZE_STRING);
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: welcome.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <section class="auth-container">
        <div class="form-box <?php echo $form !== 'signup' ? 'hidden' : ''; ?>" id="signup-form">
            <h2>Sign Up</h2>
            <form method="POST" id="signupForm">
                <input type="hidden" name="signup" value="1">
                <div class="input-group">
                    <label for="first-name">First Name</label>
                    <input type="text" id="first-name" name="first_name" placeholder="First Name" pattern="[A-Za-z]+" title="Only letters are allowed" required value="<?php echo isset($_SESSION['form_data']['first_name']) ? htmlspecialchars($_SESSION['form_data']['first_name']) : ''; ?>">
                    <?php if (isset($_SESSION['errors']['first_name'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['first_name']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <label for="last-name">Last Name</label>
                    <input type="text" id="last-name" name="last_name" placeholder="Last Name" pattern="[A-Za-z]+" title="Only letters are allowed" required value="<?php echo isset($_SESSION['form_data']['last_name']) ? htmlspecialchars($_SESSION['form_data']['last_name']) : ''; ?>">
                    <?php if (isset($_SESSION['errors']['last_name'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['last_name']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Email" required value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
                    <?php if (isset($_SESSION['errors']['email'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['email']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Username (max 15 chars)" maxlength="15" required value="<?php echo isset($_SESSION['form_data']['username']) ? htmlspecialchars($_SESSION['form_data']['username']) : ''; ?>">
                    <?php if (isset($_SESSION['errors']['username'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['username']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group password-container">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password (8-15 chars, with upper, lower, number, special)" maxlength="15" required>
                    <span class="toggle-password" onclick="togglePassword('password')">👁</span>
                    <?php if (isset($_SESSION['errors']['password'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['password']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group password-container">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" maxlength="15" required>
                    <span class="toggle-password" onclick="togglePassword('confirm-password')">👁</span>
                </div>
                <button type="submit" id="signupButton" disabled>Sign Up</button>
                <p>Already have an account? <a href="#" onclick="showLogin()">Log In</a></p>
            </form>
        </div>

        <div class="form-box <?php echo $form !== 'login' ? 'hidden' : ''; ?>" id="login-form">
            <h2>Log In</h2>
            <form method="POST" id="loginForm">
                <input type="hidden" name="login" value="1">
                <div class="input-group">
                    <label for="login-username">Username</label>
                    <input type="text" id="login-username" name="username" placeholder="Username" maxlength="15" required>
                </div>
                <div class="input-group password-container">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" placeholder="Password" maxlength="15" required>
                    <span class="toggle-password" onclick="togglePassword('login-password')">👁</span>
                    <?php if (isset($_SESSION['errors']['login'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['login']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="remember" id="remember-me">
                    <label for="remember-me">Remember Me</label>
                </div>
                <button type="submit" id="loginButton" disabled>Log In</button>
                <p><a href="#" onclick="showForgotPassword()">Forgot Password?</a></p>
                <p>No account? <a href="#" onclick="showSignup()">Sign Up</a></p>
            </form>
        </div>

        <div class="form-box <?php echo $form !== 'reset' ? 'hidden' : ''; ?>" id="forgot-password-form">
            <h2>Reset Password</h2>
            <form method="POST" id="resetForm">
                <input type="hidden" name="reset" value="1">
                <?php if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] === 'request'): ?>
                    <div class="input-group">
                        <label for="reset-email">Email</label>
                        <input type="email" id="reset-email" name="email" placeholder="Enter your email" required>
                        <?php if (isset($_SESSION['errors']['reset'])): ?>
                            <span class="error"><?php echo $_SESSION['errors']['reset']; ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" id="resetButton">Send Reset Code</button>
                <?php elseif ($_SESSION['reset_step'] === 'verify'): ?>
                    <div class="input-group">
                        <label for="reset-code">Reset Code</label>
                        <input type="text" id="reset-code" name="reset_code" placeholder="Enter 6-digit code" maxlength="6" required>
                        <?php if (isset($_SESSION['errors']['reset'])): ?>
                            <span class="error"><?php echo $_SESSION['errors']['reset']; ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" id="resetButton">Verify Code</button>
                <?php elseif ($_SESSION['reset_step'] === 'reset'): ?>
                    <div class="input-group password-container">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new_password" placeholder="New Password (8-15 chars)" maxlength="15" required>
                        <span class="toggle-password" onclick="togglePassword('new-password')">👁</span>
                    </div>
                    <div class="input-group password-container">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" maxlength="15" required>
                        <span class="toggle-password" onclick="togglePassword('confirm-password')">👁</span>
                        <?php if (isset($_SESSION['errors']['reset'])): ?>
                            <span class="error"><?php echo $_SESSION['errors']['reset']; ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" id="resetButton" disabled>Reset Password</button>
                <?php endif; ?>
                <p><a href="#" onclick="showLogin()">Back to Log In</a></p>
            </form>
        </div>
    </section>

    <?php
    unset($_SESSION['errors']);
    unset($_SESSION['form_data']);
    if (isset($_SESSION['message'])) {
        echo "<script>alert('" . htmlspecialchars($_SESSION['message']) . "');</script>";
        unset($_SESSION['message']);
    }
    ?>
</body>
</html>
