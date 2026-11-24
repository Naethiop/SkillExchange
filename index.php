
<?php
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillExchange - Share Skills, Grow Together</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">SkillExchange</div>
    <div>
        <a href="login.php">Login</a>
        <a href="register.php">Get Started</a>
    </div>
</nav>

<section class="hero">
    <div class="hero-content">
        <h1>Share Your Skills. Learn From Others.</h1>

        <p>
            Connect with fellow university students,
            exchange digital skills and grow together.
        </p>

        <a href="register.php" class="hero-button">
            Join SkillExchange
        </a>
    </div>
</section>

<section class="features container">
    <h2>What You Can Do</h2>

    <div class="feature-grid">
        <div class="feature-card">
            <h3>Share Skills</h3>
            <p>Teach other students the digital skills you know.</p>
        </div>

        <div class="feature-card">
            <h3>Learn Skills</h3>
            <p>Discover students who can teach you new skills.</p>
        </div>

        <div class="feature-card">
            <h3>Find Partners</h3>
            <p>Get matched with students based on skill compatibility.</p>
        </div>
    </div>
</section>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> SkillExchange. All rights reserved.</p>
</footer>

</body>
</html>
