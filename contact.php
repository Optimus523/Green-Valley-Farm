<?php

require 'includes/db.php';

$success = '';
$error = '';
$values = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // save for repopulation (so form does not go blank on error)

$values = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'message' => $message,
    'subject' => $message
    
];

// validate

if ($first_name === '' || $last_name === '' || $email === '' || $message === ''){
    $error = 'Please fill in the required fields.';
}else if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
    $error = 'Please enter a valid email address';
}else if (strlen($message) < 10){
    $error = "Message must be at least 10 characters.";
}else{
    // insert into db
    try {
        $stmt = $conn->prepare(
            'INSERT INTO messages (first_name, last_name, email, subject, message)
            VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $first_name, $last_name, $email, $subject, $message);
        $stmt->execute();
        $stmt->close();

        $success = 'Thank you! Your message has been sent.';
        $values = ['first_name'=>'', 'last_name'=>'', 'email'=>'', 'subject'=>'', 'message'=>''];
    } catch (mysqli_sql_exception $e) {
        error_log('Contact form insert error: ' . $e->getMessage());
        $error = 'Something went wrong. Please try again.';
    }
}
}






?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Green Valley Farm</title>
    <link rel="stylesheet" href="CSS/public.css">
    <link rel="icon" type="image/png" href="assets/favicon.png">
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="site-logo">
            <img src="assets/favicon.png" alt="Green Valley Farm logo">
            <span>Green Valley Farm</span>
        </a>

        <nav class="site-nav">
            <a href="index.php" >Home</a>
            <a href="about.php">About</a>
            <a href="contact.php" class="active">Contact</a>
        </nav>

        <a href="login.php" class="login-btn">Staff Login</a>
    </header>

<main>

    <!-- PAGE BANNER -->
    <section class="page-banner">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We would love to hear from you.</p>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section class="home-about">
        <div class="container">

            <h2>Send Us a Message</h2>
            <?php if ($success !== ''): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <p>
                Fill in the form below and we will get back to you as soon
                as we can. Fields marked with <span class="required">*</span>
                are required.
            </p>

            <form action="contact.php" method="POST" id="contact-form" novalidate>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input 
                            type="text" 
                            name="first_name" 
                            id="first_name" 
                            maxlength="50"
                            value="<?= htmlspecialchars($values['first_name']) ?>"
                        >
                        <small class="field-error" id="error-first_name"></small>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input 
                            type="text" 
                            name="last_name" 
                            id="last_name" 
                            maxlength="50"
                            value="<?= htmlspecialchars($values['last_name']) ?>"
                        >
                        <small class="field-error" id="error-last_name"></small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        maxlength="100"
                        value="<?= htmlspecialchars($values['email']) ?>"
                    >
                    <small class="field-error" id="error-email"></small>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input 
                        type="text" 
                        name="subject" 
                        id="subject" 
                        maxlength="150"
                        value="<?= htmlspecialchars($values['subject']) ?>"
                    >
                    <small class="field-error" id="error-subject"></small>
                </div>

                <div class="form-group">
                    <label for="message">Message <span class="required">*</span></label>
                    <textarea 
                        name="message" 
                        id="message" 
                        rows="6"
                        maxlength="2000"
                    ><?= htmlspecialchars($values['message']) ?></textarea>
                    <small class="field-error" id="error-message"></small>
                </div>

                <button type="submit" class="hero-btn">Send Message</button>

            </form>

        </div>
    </section>

</main>

    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> Green Valley Farm. All rights reserved.</p>
        <p>
            <a href="index.php">Home</a> ·
            <a href="about.php">About</a> ·
            <a href="contact.php">Contact</a>
        </p>
    </footer>
    <script src="JS/contact.js "></script>
</body>
</html>