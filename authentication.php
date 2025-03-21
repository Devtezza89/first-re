<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "ecommerce_db");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function handleSignup() {
    $conn = getDBConnection();
    $_SESSION['errors'] = [];
    $_SESSION['form_data'] = $_POST;

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name) || !preg_match("/^[a-zA-Z]+$/", $first_name)) {
        $_SESSION['errors']['first_name'] = "First name must contain only letters and cannot be empty";
    }
    if (empty($last_name) || !preg_match("/^[a-zA-Z]+$/", $last_name)) {
        $_SESSION['errors']['last_name'] = "Last name must contain only letters and cannot be empty";
    }

    if (strlen($password) < 8) {
        $_SESSION['errors']['password'] = "Password must be at least 8 characters long";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/", $password)) {
        $_SESSION['errors']['password'] = "Password must include uppercase, lowercase, a number, and a special character";
    } elseif ($password !== $confirm_password) {
        $_SESSION['errors']['password'] = "Passwords do not match";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errors']['email'] = "Invalid email format";
    }

    $stmt = $conn->prepare("SELECT email, username FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['email'] === $email) {
            $_SESSION['errors']['email'] = "Email already exists";
        }
        if ($row['username'] === $username) {
            $_SESSION['errors']['username'] = "Username already taken";
        }
    }
    $stmt->close();

    if (empty($_SESSION['errors'])) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, username, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $username, $hashed_password);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Sign-up successful! Please log in.";
            unset($_SESSION['form_data']);
            header("Location: index.php?form=login");
            exit;
        } else {
            $_SESSION['errors']['general'] = "Registration failed: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}

function handleLogin() {
    $conn = getDBConnection();
    $_SESSION['errors'] = [];

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['message'] = "Login successful!";
            header("Location: welcome.php");
            exit;
        } else {
            $_SESSION['errors']['login'] = "Incorrect username or password";
        }
    } else {
        $_SESSION['errors']['login'] = "Incorrect username or password";
    }
    $stmt->close();
    $conn->close();
}

function handleReset() {
    $conn = getDBConnection();
    $_SESSION['errors'] = [];

    $email = trim($_POST['email']);

    if (!isset($_SESSION['mfa_required']) || !$_SESSION['mfa_required']) {
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['mfa_required'] = true;
            $_SESSION['reset_email'] = $email;
            $_SESSION['message'] = "MFA code sent to your email (simulated). Enter code below.";
        } else {
            $_SESSION['errors']['reset'] = "Email not found";
        }
        $stmt->close();
    } else {
        $mfa_code = trim($_POST['mfa_code']);
        if (strlen($mfa_code) === 6 && ctype_digit($mfa_code)) {
            unset($_SESSION['mfa_required']);
            unset($_SESSION['reset_email']);
            $_SESSION['message'] = "Password reset successful! Please log in with your new password.";
            header("Location: index.php?form=login");
            exit;
        } else {
            $_SESSION['errors']['reset'] = "Invalid MFA code";
        }
    }
    $conn->close();
}
?>
