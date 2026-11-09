<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Green Valley Farm</title>
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
            <a href="about.php" class="active">About</a>
            <a href="contact.php">Contact</a>
        </nav>

        <a href="login.php" class="login-btn">Staff Login</a>
    </header>

    <main>
        <section class="page-banner">
            <div class="container">
                <h1>About Us</h1>
                <p>Get to know Green Valley Farm.</p>
            </div>
        </section>
        <section class="home-about">
        <div class="container">
        <h2>Our Story</h2>
        <p>
            Green Valley Farm is a family-run livestock farm located in central
            Uganda. For years, we have raised pigs, goats and cattle with one
            simple conviction: healthy animals come from good care. What started
            as a small family operation has grown into a trusted supplier of
            breeding stock and farm products to customers across the region.
        </p>
        <p>
            We keep meticulous records of every animal — its birth, weight,
            health, and history. That discipline is what lets our customers buy
            with confidence, and it is why we invested in a digital records
            system that keeps our data accurate, accessible and secure.
        </p>
        <p>
            Whether you are a farmer looking for breeding stock, a household
            looking for quality produce, or a partner looking to work with us,
            we are here to help.
        </p>
        </div>
    </section>

    <section class="home-cards">
    <div class="container">
        <h2>What Drives Us</h2>
        <div class="card-grid">
            <div class="info-card">
                <h3>🎯 Our Mission</h3>
                <p>
                    To provide healthy, high-quality livestock and farm products
                    to our customers while maintaining the highest standards of
                    animal welfare and record keeping.
                </p>
            </div>
            <div class="info-card">
                <h3>🌱 Our Vision</h3>
                <p>
                    To be the most trusted livestock supplier in the region —
                    known for quality animals, honest service and reliable
                    records that customers can depend on.
                </p>
            </div>
            <div class="info-card">
                <h3>🤝 Our Values</h3>
                <p>
                    Integrity in every transaction. Care for every animal.
                    Respect for every customer. Consistency in every record.
                </p>
            </div>
        </div>
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

</body>
</html>