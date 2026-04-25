<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie QR Menu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-page">
    <div class="landing-wrap">
        <header class="container landing-nav">
            <a class="landing-brand" href="#home" aria-label="Foodie QR Home">
                <span class="landing-brand__dot"></span>
                <strong>Foodie QR</strong>
            </a>
            <button class="landing-nav__toggle" type="button" aria-expanded="false" aria-controls="landingMenu" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            <nav id="landingMenu" class="landing-menu" aria-label="Main navigation">
                <a href="#home">Home</a>
                <a href="#services">Services</a>
                <a href="#about">About</a>
                <a href="#gallery">Gallery</a>
                <a href="#contact">Contact</a>
                <a class="btn btn-outline landing-nav__btn" href="admin/login.php">Admin Login</a>
            </nav>
        </header>
        <main class="container landing-main">
            <!-- Hero section -->
            <section id="home" class="landing-hero">
                <div class="landing-hero__content">
                    <p class="landing-kicker">Smart Restaurant Experience</p>
                    <h1 class="landing-title">Modern digital menu for your restaurant</h1>
                    <p class="landing-subtitle">
                        Delight guests with a fast QR menu, beautiful food photos, and a simple admin dashboard for your team.
                    </p>
                    <div class="landing-cta-row">
                        <a class="btn landing-btn" href="admin/login.php">Start Managing Menus</a>
                        <a class="btn btn-outline landing-btn-outline" href="menu.php?id=demo2026">View Demo Menu</a>
                    </div>
                </div>
                <div class="landing-hero__visual">
                    <img class="landing-hero-img" src="assets/images/food-burger.svg" alt="Modern burger presentation">
                </div>
            </section>

            <!-- Gallery section -->
            <section id="gallery" class="landing-gallery">
                <article class="landing-gallery__card">
                    <img src="assets/images/food-pasta.svg" alt="Pasta dish illustration">
                    <div>
                        <h3>Beautiful Dish Cards</h3>
                        <p>Highlight ingredients, flavors, and pricing with clean image-focused cards.</p>
                    </div>
                </article>
                <article class="landing-gallery__card">
                    <img src="assets/images/food-dessert.svg" alt="Dessert illustration">
                    <div>
                        <h3>Attractive Guest Experience</h3>
                        <p>Make every menu feel premium with elegant visuals and smooth navigation.</p>
                    </div>
                </article>
            </section>

            <!-- Services section -->
            <section id="services" class="landing-features">
                <article class="landing-feature-card">
                    <h3>Easy Admin Flow</h3>
                    <p>Create menus, add categories, and manage items from one dashboard.</p>
                </article>
                <article class="landing-feature-card">
                    <h3>Instant QR Sharing</h3>
                    <p>Generate downloadable QR codes for tables, walls, and takeaway packaging.</p>
                </article>
                <article class="landing-feature-card">
                    <h3>Fast for Customers</h3>
                    <p>Search and filter menus quickly, with clear photos and ETB pricing.</p>
                </article>
            </section>

            <!-- About section -->
            <section id="about" class="landing-about">
                <div>
                    <h2>Built for modern restaurants</h2>
                    <p>Foodie QR helps restaurants, cafes, and hotels deliver a clean digital dining experience without complex setup.</p>
                </div>
                <ul>
                    <li>Menu updates in real-time</li>
                    <li>Multi-admin role support</li>
                    <li>Fast QR access for customers</li>
                </ul>
            </section>

            <!-- Contact section -->
            <section id="contact" class="landing-contact">
                <h2>Get started today</h2>
                <form class="landing-contact__form" action="#" method="post">
                    <label>
                        Name
                        <input type="text" name="name" placeholder="Your name">
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" placeholder="you@example.com">
                    </label>
                    <label class="full">
                        Message
                        <textarea name="message" rows="4" placeholder="Tell us about your restaurant"></textarea>
                    </label>
                    <button class="btn full" type="button">Send Message</button>
                </form>
            </section>
        </main>
        <footer class="container landing-footer">
            <p>&copy; <?= date('Y') ?> Foodie QR Menu. All rights reserved.</p>
        </footer>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
