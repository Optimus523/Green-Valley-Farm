<?php
require 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Read inputs safely (?? prevents PHP 8 warnings if key is missing)
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $password   = $_POST['password'] ?? '';

    // 2. Role is forced server-side — never trust the client with admin
    $role = 'staff';

    // 3. Validation
    if ($first_name === '' || $last_name === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            'INSERT INTO users (first_name, last_name, email, password_hash, role)
            VALUES (?, ?, ?, ?, ?)'
        );

        if ($stmt) {
            $stmt->bind_param('sssss', $first_name, $last_name, $email, $hash, $role);

            try {
                if ($stmt->execute()) {
                    // $success = 'Registration successful! You can now log in.';
                    // // Clear the form values after success
                    // $first_name = $last_name = $email = '';
                    header('Location: login.php?registered=1');
                    exit;
                }
            } catch (mysqli_sql_exception $e) {
                // 1062 = duplicate entry (email already exists)
                if ($e->getCode() === 1062) {
                    $error = 'That email is already registered.';
                } else {
                    error_log('Registration error: ' . $e->getMessage());
                    $error = 'Registration failed. Please try again.';
                }
            }
            $stmt->close();
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Livestock System</title>
    <link rel="stylesheet" href="CSS/register.css">
    <link rel="icon" type="image/png" href="assets/favicon.png">
</head>
<body>
    <div class="register-container">

        <div class="logo-container">
            <img src="assets/favicon.png" alt="Livestock System Logo" class="auth-logo">
        </div>

        <h2>Get Started</h2>
        <p class="subtitle">Create your account to manage your livestock</p>

        <?php if ($error !== ''): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form action="register.php" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name"
                        value="<?= htmlspecialchars($first_name ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name"
                        value="<?= htmlspecialchars($last_name ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email"
                    value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required minlength="6">
            </div>

            <button type="submit">Register</button>
        </form>

        <p class="auth-links">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>
</body>
</html>