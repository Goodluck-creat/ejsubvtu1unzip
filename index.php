<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EJSUB</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    :root {
      --primary: #00A86B;
      --secondary: #1E1E2F;
      --accent: #FFD700;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: var(--secondary);
      color: #fff;
      overflow-x: hidden;
    }

    header {
      background: var(--primary);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      animation: slideDown 0.8s ease;
      z-index: 1000;
    }

    header h1 {
      color: var(--accent);
      font-size: 22px;
    }

    nav {
      display: flex;
      gap: 20px;
    }

    nav a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
    }

    nav a:hover {
      color: var(--accent);
    }

    .menu-toggle {
      width: 30px;
      height: 22px;
      position: relative;
      display: none;
      cursor: pointer;
      z-index: 1001;
    }

    .menu-toggle span {
      position: absolute;
      height: 3px;
      width: 100%;
      background: #fff;
      left: 0;
      transition: 0.3s ease;
    }

    .menu-toggle span:nth-child(1) { top: 0; }
    .menu-toggle span:nth-child(2) { top: 9px; }
    .menu-toggle span:nth-child(3) { top: 18px; }

    .menu-toggle.active span:nth-child(1) {
      transform: rotate(45deg);
      top: 9px;
    }

    .menu-toggle.active span:nth-child(2) {
      opacity: 0;
    }

    .menu-toggle.active span:nth-child(3) {
      transform: rotate(-45deg);
      top: 9px;
    }

    .hero-slider {
      position: relative;
      height: 50vh;
      overflow: hidden;
    }

    .hero-slider .slide {
      position: absolute;
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      top: 0;
      left: 0;
      opacity: 0;
      transition: opacity 1.5s ease-in-out;
    }

    .hero-slider .slide.active {
      opacity: 1;
      z-index: 1;
    }

    .hero-slider .overlay {
      background: linear-gradient(135deg, rgba(30, 30, 47, 0.85), rgba(0, 168, 107, 0.85));
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hero-content {
      text-align: center;
      color: #fff;
      animation: fadeIn 1.2s ease;
      padding: 0 20px;
    }

    .hero-content h2 {
      font-size: 38px;
      color: var(--accent);
      margin-bottom: 15px;
      animation: pulseGlow 2s infinite ease-in-out;
    }

    .hero-content p {
      font-size: 18px;
      color: #f0f0f0;
      margin-bottom: 25px;
    }

    .hero-content button {
      padding: 12px 28px;
      background-color: var(--accent);
      color: var(--secondary);
      border: none;
      font-weight: 600;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.3s ease, background-color 0.3s ease;
    }

    .hero-content button:hover {
      transform: scale(1.05);
      background-color: #e6c200;
    }

    .services {
      padding: 60px 20px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
    }

    .service-box {
      background-color: #2E2E40;
      padding: 25px 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .service-box h3 {
      color: var(--accent);
      margin-bottom: 12px;
      font-size: 20px;
    }

    .service-box p {
      color: #ccc;
      font-size: 15px;
    }

    .service-box:hover {
      transform: translateY(-8px);
      box-shadow: 0 6px 18px var(--primary);
    }

    footer {
      background: var(--primary);
      padding: 20px;
      text-align: center;
      color: #ffffff;
      font-weight: 300;
    }

    @keyframes slideDown {
      from { transform: translateY(-100px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulseGlow {
      0% {
        text-shadow: 0 0 5px var(--accent), 0 0 10px var(--accent);
      }
      50% {
        text-shadow: 0 0 15px var(--accent), 0 0 20px var(--accent);
      }
      100% {
        text-shadow: 0 0 5px var(--accent), 0 0 10px var(--accent);
      }
    }

    .reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.6s ease-out;
    }

    .reveal.show {
      opacity: 1;
      transform: translateY(0);
    }

    @media (max-width: 768px) {
      .menu-toggle {
        display: block;
      }

      nav {
        flex-direction: column;
        align-items: flex-start;
        position: fixed;
        top: 0;
        right: -100%;
        background: var(--primary);
        height: 100vh;
        width: 250px;
        padding: 80px 20px;
        gap: 20px;
        transition: right 0.3s ease;
      }

      nav.show {
        right: 0;
      }

      nav a {
        padding: 12px 0;
        width: 100%;
        font-size: 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      }
    }

    @media (max-width: 480px) {
      .hero-content h2 {
        font-size: 24px;
      }

      .hero-content button {
        font-size: 14px;
        padding: 10px 20px;
      }
    }

    .site-logo img {
      height: 40px;
      max-width: 100%;
      object-fit: contain;
      vertical-align: middle;
      background-color: #ffd700;
    }

    @media (min-width: 768px) {
      .site-logo img {
        height: 50px;
      }
    }
  </style>
</head>
<body>

  <header>
    <h1 class="site-logo">
      <img src="images/ejsub.png" alt="EJSUB Logo">
    </h1>

    <div class="menu-toggle" id="menu-toggle">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <nav id="nav-menu">
      <a href="#">Home</a>
      <a href="user/buy_airtime.php">Airtime</a>
      <a href="#">Data</a>
      <a href="#">Utilities</a>
      <a href="login">Login</a>
      <a href="register">Register</a>
    </nav>
  </header>

  <!-- HERO SLIDER -->
  <section class="hero-slider">
      <div class="slide" style="background-image: url('images/slide4.png');">
  <div class="overlay">
    <div class="hero-content">
      <h2>Fund Crypto Instantly</h2>
      <p>Buy and fund your crypto wallet with ease. Fast, secure, and stress-free funding available 24/7.</p>
      <button>Fund Now</button>
    </div>
  </div>
</div>

    
    <div class="slide" style="background-image: url('images/slide2.png');">
      <div class="overlay">
        <div class="hero-content">
          <h2>Pay Bills Seamlessly</h2>
          <p>Pay for PHCN, DSTV, GOTV, and more without stress.</p>
          <button>Start Now</button>
        </div>
      </div>
    </div>
    <div class="slide" style="background-image: url('images/slide5.png');">
  <div class="overlay">
    <div class="hero-content">
      <h2>Fast & Trusted Crypto Funding</h2>
      <p>Securely fund your crypto wallet in seconds. EJSUB delivers speed, trust, and reliability 24/7.</p>
      <button>Fund Now</button>
    </div>
  </div>
</div>
<div class="slide active" style="background-image: url('images/slide3.png');">
      <div class="overlay">
        <div class="hero-content">
          <h2>Top Up Instantly</h2>
          <p>Fast and affordable airtime, data, and bill payments for everyone.</p>
          <button>Get Started</button>
        </div>
      </div>
    </div>

  </section>

  <!-- SERVICES -->
  <section class="services">
    <div class="service-box reveal">
      <i class="fas fa-mobile-alt fa-2x" style="color: var(--accent); margin-bottom: 10px;"></i>
      <h3>Airtime Recharge</h3>
      <p>Buy airtime for all networks with instant delivery and cashback.</p>
    </div>
    <div class="service-box reveal">
      <i class="fas fa-wifi fa-2x" style="color: var(--accent); margin-bottom: 10px;"></i>
      <h3>Data Bundles</h3>
      <p>Get affordable data plans with 24/7 uptime support.</p>
    </div>
    <div class="service-box reveal">
      <i class="fas fa-bolt fa-2x" style="color: var(--accent); margin-bottom: 10px;"></i>
      <h3>Pay Bills</h3>
      <p>Settle DSTV, PHCN, and more without stress or delays.</p>
    </div>
  </section>

  <footer>
    &copy; 2025 EJSUB
  </footer>

  <script>
    const toggleBtn = document.getElementById('menu-toggle');
    const navMenu = document.getElementById('nav-menu');

    toggleBtn.addEventListener('click', () => {
      toggleBtn.classList.toggle('active');
      navMenu.classList.toggle('show');
    });

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
        }
      });
    }, { threshold: 0.2 });

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    // HERO SLIDER LOGIC
    const slides = document.querySelectorAll('.hero-slider .slide');
    let currentSlide = 0;

    setInterval(() => {
      slides[currentSlide].classList.remove('active');
      currentSlide = (currentSlide + 1) % slides.length;
      slides[currentSlide].classList.add('active');
    }, 6000);
  </script>

</body>
</html>
