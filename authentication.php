<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "ecommerce_db");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function handleSignup() {
    $conn = getDBConnection();
    $_SESSION['errors'] = [];
    $_SESSION['form_data'] = $_POST;

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || !preg_match("/^[a-zA-Z]+$/", $first_name)) {
        $_SESSION['errors']['first_name'] = "First name must contain only letters and cannot be empty";
    }
    if (empty($last_name) || !preg_match("/^[a-zA-Z]+$/", $last_name)) {
        $_SESSION['errors']['last_name'] = "Last name must contain only letters and cannot be empty";
    }
    if (strlen($username) > 15) {
        $_SESSION['errors']['username'] = "Username must be 15 characters or less";
    } elseif (empty($username)) {
        $_SESSION['errors']['username'] = "Username cannot be empty";
    }
    if (strlen($password) > 15) {
        $_SESSION['errors']['password'] = "Password must be 15 characters or less";
    } elseif (strlen($password) < 8) {
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
    
    while ($row = $result->fetch_assoc()) {
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
            $_SESSION['errors']['general'] = "Registration failed";
        }
        $stmt->close();
    }
    $conn->close();
}

function handleLogin() {
    $conn = getDBConnection();
    $_SESSION['errors'] = [];

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    if (strlen($username) > 15) {
        $_SESSION['errors']['login'] = "Username must be 15 characters or less";
    }
    if (strlen($password) > 15) {
        $_SESSION['errors']['login'] = "Password must be 15 characters or less";
    }

    if (empty($_SESSION['errors'])) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                
                if ($remember) {
                    $token = bin2hex(random_bytes(16));
                    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                    setcookie('remember_me', $token, [
                        'expires' => time() + (30 * 24 * 60 * 60),
                        'path' => '/',
                        'secure' => $secure,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                    
                    $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE username = ?");
                    $stmt->bind_param("ss", $token, $username);
                    $stmt->execute();
                    $stmt->close();
                }
                
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
    }
    $conn->close();
}

function checkRememberMe() {
    if (isset($_COOKIE['remember_me']) && !isset($_SESSION['logged_in'])) {
        $conn = getDBConnection();
        $token = filter_var($_COOKIE['remember_me'], FILTER_SANITIZE_STRING);
        
        $stmt = $conn->prepare("SELECT username FROM users WHERE remember_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $row['username'];
            header("Location: welcome.php");
            exit;
        }
        $stmt->close();
        $conn->close();
        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    }
}

function handleReset() {
    $conn = getDBConnection();
    $_SESSION['errors'] = [];

    if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] === 'request') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errors']['reset'] = "Invalid email format";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $reset_code = sprintf("%06d", mt_rand(0, 999999));
                $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
                $stmt->bind_param("sss", $reset_code, $expires, $email);
                $stmt->execute();
                $stmt->close();

                // Simulate email (replace with PHPMailer logic if desired)
                $_SESSION['message'] = "Reset code sent to $email: $reset_code (simulated)";
                $_SESSION['reset_step'] = 'verify';
                $_SESSION['reset_email'] = $email;
            } else {
                $_SESSION['errors']['reset'] = "Email not registered";
            }
        }
    } elseif ($_SESSION['reset_step'] === 'verify') {
        $reset_code = trim($_POST['reset_code'] ?? '');
        $email = $_SESSION['reset_email'];

        $stmt = $conn->prepare("SELECT reset_token, reset_token_expires FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && $row['reset_token'] === $reset_code && strtotime($row['reset_token_expires']) > time()) {
            $_SESSION['reset_step'] = 'reset';
        } else {
            $_SESSION['errors']['reset'] = "Invalid or expired reset code";
        }
        $stmt->close();
    } elseif ($_SESSION['reset_step'] === 'reset') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $email = $_SESSION['reset_email'];

        if (strlen($new_password) > 15) {
            $_SESSION['errors']['reset'] = "Password must be 15 characters or less";
        } elseif (strlen($new_password) < 8) {
            $_SESSION['errors']['reset'] = "Password must be at least 8 characters long";
        } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/", $new_password)) {
            $_SESSION['errors']['reset'] = "Password must include uppercase, lowercase, a number, and a special character";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['errors']['reset'] = "Passwords do not match";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            if ($stmt->execute()) {
                unset($_SESSION['reset_step']);
                unset($_SESSION['reset_email']);
                $_SESSION['message'] = "Password reset successful! Please log in.";
                header("Location: index.php?form=login");
                exit;
            } else {
                $_SESSION['errors']['reset'] = "Failed to reset password";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
