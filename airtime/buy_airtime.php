<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include("../connection.php");

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$userRes = $conn->query("SELECT wallet_balance FROM vtu_users WHERE id = $user_id");
$user = $userRes->fetch_assoc();
$wallet = $user['wallet_balance'];
$offers = $conn->query("SELECT * FROM airtime_offers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Buy Airtime EJSUB</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
  <style>
    :root {
      --bg-light: #f5f5f5;
      --text-light: #333;
      --bg-dark: #1e1e2f;
      --text-dark: #fff;
      --card-light: #fff;
      --card-dark: #2e2e40;
      --accent: #00A86B;
    }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      transition: background 0.3s, color 0.3s;
    }

    .light-mode {
      background: var(--bg-light);
      color: var(--text-light);
    }

    .dark-mode {
      background: var(--bg-dark);
      color: var(--text-dark);
    }

    header {
      padding: 20px;
      text-align: center;
      background: var(--accent);
      color: white;
      position: relative;
    }

    header h1 {
      margin: 0;
      font-size: 24px;
    }

    .theme-toggle {
      position: absolute;
      right: 20px;
      top: 20px;
      background: white;
      color: var(--accent);
      border: none;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      font-size: 18px;
      cursor: pointer;
    }

    .animation-box {
      width: 100px;
      margin: auto;
    }

    .wallet {
      max-width: 800px;
      margin: 20px auto;
      padding: 15px;
      border-radius: 10px;
      font-weight: bold;
      background: var(--card-dark);
      color: white;
      text-align: center;
    }

    .light-mode .wallet {
      background: var(--card-light);
      color: var(--text-light);
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      max-width: 1000px;
      margin: auto;
      padding: 20px;
    }

    .card {
      background: var(--card-dark);
      color: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
      text-align: center;
      transition: transform 0.2s;
    }

    .light-mode .card {
      background: var(--card-light);
      color: var(--text-light);
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .card button {
      margin-top: 10px;
      padding: 10px 20px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    /* MODAL STYLING */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.6);
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      color: black;
      padding: 30px;
      border-radius: 12px;
      width: 90%;
      max-width: 400px;
      position: relative;
      text-align: center;
    }

    .dark-mode .modal-content {
      background: var(--card-dark);
      color: white;
    }

    .modal-content h3 {
      margin-top: 0;
    }

    .modal-content input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    .modal-content button[type="submit"] {
      background: var(--accent);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
    }

    .close-btn {
      position: absolute;
      right: 15px;
      top: 15px;
      font-size: 20px;
      cursor: pointer;
      color: #999;
    }

    @media (max-width: 600px) {
      header h1 {
        font-size: 18px;
      }
    }
   .page-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.footer {
  margin-top: auto;
  background: rgba(0, 0, 0, 0.08);
  color: inherit;
  text-align: center;
  padding: 10px 20px;
  font-size: 14px;
}

.light-mode .footer {
  background: rgba(0, 0, 0, 0.05);
  color: #555;
}

.dark-mode .footer {
  background: rgba(255, 255, 255, 0.05);
  color: #ccc;
}

.back-btn {
  position: absolute;
  left: 20px;
  top: 20px;
  color: white;
  background: rgba(0,0,0,0.2);
  padding: 8px 10px;
  border-radius: 50%;
  text-decoration: none;
  font-size: 16px;
  transition: background 0.3s;
}

.back-btn:hover {
  background: rgba(255,255,255,0.2);
}

.light-mode .back-btn {
  color: var(--accent);
  background: rgba(0,0,0,0.05);
}

.light-mode .back-btn:hover {
  background: rgba(0,0,0,0.1);
}


  </style>
</head>
<body class="dark-mode" id="theme-body">

<header>
    <a href="../user/dashboard.php" class="back-btn" title="Go Back">
  <i class="fa-solid fa-arrow-left"></i>
</a>

  <button class="theme-toggle" onclick="toggleTheme()">
    <i class="fa-solid fa-moon" id="theme-icon"></i>
  </button>
  <h1>Buy Airtime Instantly On EJSUB</h1>
  
</header>

<div class="wallet">
  <i class="fa-solid fa-wallet"></i> Wallet Balance: ₦<?= number_format($wallet, 2) ?>
</div>

<div class="grid">
  <?php while ($offer = $offers->fetch_assoc()): ?>
    <div class="card">
      <h4><?= strtoupper($offer['network_name']) ?> - ₦<?= $offer['amount'] ?></h4>
      <p><i>Pay ₦<?= $offer['discount_price'] ?> from your wallet</i></p>
      <button onclick="openModal(<?= $offer['id'] ?>, '<?= strtoupper($offer['network_name']) ?>', <?= $offer['amount'] ?>, <?= $offer['discount_price'] ?>)">Buy Now</button>
    </div>
  <?php endwhile; ?>
</div>

<!-- MODAL -->
<div class="modal" id="airtimeModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">×</span>
    <h3 id="modal-title">Buy Airtime</h3>
    <form method="POST" action="purchase_airtime_offer.php">
      <input type="hidden" name="offer_id" id="modal-offer-id">
      <input type="tel" name="phone" placeholder="Enter Phone Number" required pattern="\d{10,14}">
      <button type="submit">Confirm Purchase</button>
    </form>
  </div>
</div>

<script>
  function toggleTheme() {
    const body = document.getElementById('theme-body');
    const icon = document.getElementById('theme-icon');
    if (body.classList.contains('dark-mode')) {
      body.classList.replace('dark-mode', 'light-mode');
      icon.classList.replace('fa-moon', 'fa-sun');
    } else {
      body.classList.replace('light-mode', 'dark-mode');
      icon.classList.replace('fa-sun', 'fa-moon');
    }
  }

  function openModal(id, network, amount, discount) {
    document.getElementById('airtimeModal').style.display = 'flex';
    document.getElementById('modal-title').innerHTML = `${network} - ₦${amount}<br><small>Pay ₦${discount} from wallet</small>`;
    document.getElementById('modal-offer-id').value = id;
  }

  function closeModal() {
    document.getElementById('airtimeModal').style.display = 'none';
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    if (event.target == document.getElementById('airtimeModal')) {
      closeModal();
    }
  };

  // Auto theme on load
  window.onload = () => {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const body = document.getElementById('theme-body');
    const icon = document.getElementById('theme-icon');
    if (!prefersDark) {
      body.classList.replace('dark-mode', 'light-mode');
      icon.classList.replace('fa-moon', 'fa-sun');
    }
  };
</script>

<body class="dark-mode" id="theme-body">
  <div class="page-wrapper">
    <!-- your entire content here (header, wallet, grid, modal, etc.) -->

    <footer class="footer">
      <p>&copy; <?= date('Y') ?> ChiomaPay • Powered by AnambraTechies</p>
    </footer>
  </div>
</body>


</body>
</html>
