<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Green Valley Farm</title>
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
            <a href="index.php" class="active">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
        </nav>        

        <a href="login.php" class="login-btn">Staff Login</a>
    </header>

    <main>

        <section class="hero">
            <div class="container">
                <h1>Quality Livestock, Raised Right</h1>
                <p>
                Green Valley Farm is a trusted livestock farm in Uganda, supplying
                healthy animals and quality breeding stock to farmers across the region.
                </p>
                <a href="contact.php" class="hero-btn">Contact Us</a>
            </div>
        </section>

        <section class="home-about">
            <div class="container">
            <h2>Who We Are</h2>
            <p>
                Located in the heart of Uganda, Green Valley Farm has been raising
                livestock for years, built on a simple belief: healthy animals come
                from good care. We keep pigs, goats and cattle, and we sell breeding
                stock, weaners and farm products to customers who value quality.
            </p>
            <p>
                Behind every animal is a record — its birth, its weight, its health.
                That is why we built a system to keep those records accurate and
                accessible, so our customers can trust what they buy and our staff
                can work with confidence.
            </p>
            </div>
        </section>


        <section class="home-cards">
            <div class="container">
                            <h2>What We Offer</h2>

            <div class="card-grid">
                <div class="info-card">
                    <h3>🐄 Quality Breeding Stock</h3>
                    <p>Healthy, well-raised animals ready for your farm.</p>
                </div>

                <div class="info-card">
                    <h3>🥩 Farm Products</h3>
                    <p>Fresh, reliable supply for homes and businesses.</p>
                </div>

                <div class="info-card">
                    <h3>🤝 Trusted Service</h3>
                    <p>Years of experience serving farmers across the region.</p>
                </div>
            </div>
            </div>
        </section>


        <section class="home-cta">
            <div class="container">
                <h2>Farm Staff?</h2>
            <p>Log in to access the livestock records system.</p>
            <a href="login.php" class="hero-btn">Staff Login</a>
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