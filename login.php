<?php
session_start();
require 'includes/db.php';

if (isset($_GET['registered'])) {
    $success = 'Account created! Please log in.';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Both email and password are required.';
    } else {
        $stmt = $conn->prepare(
            'SELECT id, first_name, password_hash, role FROM users WHERE email = ?'
        );

        if ($stmt === false) {
            error_log('Login prepare failed: ' . $conn->error);
            $error = 'Something went wrong. Please try again.';
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password_hash'])) {
                    // Prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['role']       = $user['role'];

                    header('Location: dashboard.php');
                    exit;
                }
            }

            // Same message whether email doesn't exist OR password is wrong
            $error = 'Invalid email or password.';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Livestock System</title>
    <link rel="stylesheet" href="CSS/register.css">
    <link rel="icon" type="image/png" href="assets/favicon.png?v=2">
</head>
<body>
    <div class="register-container">

        <div class="logo-container">
            <img src="assets/favicon.png" alt="Livestock System Logo" class="auth-logo">
        </div>

        <h2>Welcome back</h2>
        <p class="subtitle">
            Log in to continue to the Livestock System
        </p>

        <?php if ($error !== ''): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email"
                       autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password"
                       autocomplete="current-password" required>
            </div>

            <button type="submit">Login</button>
        </form>

        <p class="auth-links">
            Don't have an account? <a href="register.php">Register</a>
        </p>
    </div>
</body>
</html>