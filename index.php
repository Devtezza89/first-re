<?php
// Start session only once at the top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'authentication.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signup'])) {
        handleSignup();
    } elseif (isset($_POST['login'])) {
        handleLogin();
    } elseif (isset($_POST['reset'])) {
        handleReset();
    }
}

// Determine form to show
$form = isset($_GET['form']) ? $_GET['form'] : 'signup';
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
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <section class="auth-container">
        <!-- Sign-Up Form -->
        <div class="form-box <?php echo $form !== 'signup' ? 'hidden' : ''; ?>" id="signup-form">
            <h2>Sign Up</h2>
            <form method="POST">
                <input type="hidden" name="signup" value="1">
                <div class="input-group">
                    <label for="first-name">First Name</label>
                    <input type="text" id="first-name" name="first_name" placeholder="First Name" required value="<?php echo isset($_SESSION['form_data']['first_name']) ? htmlspecialchars($_SESSION['form_data']['first_name']) : ''; ?>">
                </div>
                <div class="input-group">
                    <label for="last-name">Last Name</label>
                    <input type="text" id="last-name" name="last_name" placeholder="Last Name" required value="<?php echo isset($_SESSION['form_data']['last_name']) ? htmlspecialchars($_SESSION['form_data']['last_name']) : ''; ?>">
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
                    <input type="text" id="username" name="username" placeholder="Username" required value="<?php echo isset($_SESSION['form_data']['username']) ? htmlspecialchars($_SESSION['form_data']['username']) : ''; ?>">
                    <?php if (isset($_SESSION['errors']['username'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['username']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <?php if (isset($_SESSION['errors']['password'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['password']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button type="submit">Sign Up</button>
                <p>Already have an account? <a href="#" onclick="showLogin()">Log In</a></p>
            </form>
        </div>

        <!-- Login Form -->
        <div class="form-box <?php echo $form !== 'login' ? 'hidden' : ''; ?>" id="login-form">
            <h2>Log In</h2>
            <form method="POST">
                <input type="hidden" name="login" value="1">
                <div class="input-group">
                    <label for="login-username">Username</label>
                    <input type="text" id="login-username" name="username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" placeholder="Password" required>
                    <?php if (isset($_SESSION['errors']['login'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['login']; ?></span>
                    <?php endif; ?>
                </div>
                <button type="submit">Log In</button>
                <p><a href="#" onclick="showForgotPassword()">Forgot Password?</a></p>
                <p>No account? <a href="#" onclick="showSignup()">Sign Up</a></p>
            </form>
        </div>

        <!-- Forgot Password Form -->
        <div class="form-box <?php echo $form !== 'reset' ? 'hidden' : ''; ?>" id="forgot-password-form">
            <h2>Reset Password</h2>
            <form method="POST">
                <input type="hidden" name="reset" value="1">
                <div class="input-group">
                    <label for="reset-email">Email</label>
                    <input type="email" id="reset-email" name="email" placeholder="Enter your email" required>
                    <?php if (isset($_SESSION['errors']['reset'])): ?>
                        <span class="error"><?php echo $_SESSION['errors']['reset']; ?></span>
                    <?php endif; ?>
                </div>
                <?php if (isset($_SESSION['mfa_required']) && $_SESSION['mfa_required']): ?>
                    <div class="input-group">
                        <label for="mfa-code">MFA Code</label>
                        <input type="text" id="mfa-code" name="mfa_code" placeholder="Enter MFA Code" required>
                    </div>
                <?php endif; ?>
                <button type="submit"><?php echo isset($_SESSION['mfa_required']) && $_SESSION['mfa_required'] ? 'Verify Code' : 'Send Reset Code'; ?></button>
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