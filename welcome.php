<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: index.php?form=login");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    header("Location: index.php?form=login");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <section class="auth-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>You are now logged in.</p>
        <a href="?logout=true" class="logout-link">Log Out</a>
    </section>
</body>
</html>
