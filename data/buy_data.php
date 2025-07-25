<?php
include("../connection.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all unique networks
$networksResult = $conn->query("SELECT DISTINCT network FROM data_plans");
$networks = [];
while ($row = $networksResult->fetch_assoc()) {
    $networks[] = $row['network'];
}

// Get all plans
$plansResult = $conn->query("SELECT id, network, plan_name, price FROM data_plans ORDER BY price ASC");
$dataPlans = [];
while ($row = $plansResult->fetch_assoc()) {
    $dataPlans[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ejsub - Buy Data Plans</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0; padding: 0;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background: #0b0f1a;
      color: #ffffff;
      padding: 20px;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 2rem;
      color: #FFD700;
      letter-spacing: 1px;
    }

    /* Branding */
    .ejsub-brand {
      text-align: center;
      font-weight: bold;
      font-size: 1.3rem;
      margin-bottom: 10px;
      color: #00ffbb;
    }

    /* Network tabs */
    .network-tab {
      background: #222d3d;
      color: #ffffff;
      border: 2px solid #FFD700;
      padding: 10px 18px;
      margin: 5px;
      border-radius: 20px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .network-tab:hover {
      background: #FFD700;
      color: #1E1E2F;
    }
    .active-tab {
      background: #00a86b !important;
      color: #ffffff !important;
      border-color: #00a86b !important;
    }

    .tab-container {
      text-align: center;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }

    .cards-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .data-card {
      background: #161e2a;
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 1px solid #00a86b;
    }

    .data-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 25px rgba(0, 168, 107, 0.2);
    }

    .network {
      font-size: 1.1em;
      font-weight: bold;
      color: #FFD700;
    }

    .plan-name {
      margin: 10px 0;
      font-size: 1em;
      color: #ccc;
    }

    .price {
      font-size: 1.3em;
      color: #00ffbb;
      font-weight: bold;
      margin-bottom: 15px;
    }

    .buy-btn {
      background: #FFD700;
      color: #1E1E2F;
      border: none;
      padding: 10px 15px;
      border-radius: 10px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .buy-btn:hover {
      background: #e6c200;
    }

    .buy-form {
      margin-top: 10px;
      display: none;
      transition: all 0.3s ease;
    }

    input[type="tel"] {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #FFD700;
      margin-bottom: 10px;
      background: #111722;
      color: #fff;
    }

    @media (max-width: 600px) {
      .plan-name { font-size: 0.95em; }
      .price { font-size: 1.2em; }
      .network-tab {
        padding: 8px 12px;
        margin: 4px;
        font-size: 0.9rem;
      }
    }
    
    /* Preloader Styles */
#preloader {
  position: fixed;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  background-color: #0b0f1a;
  z-index: 9999;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  transition: opacity 0.3s ease;
}

.loader p {
  margin-top: 15px;
  color: #FFD700;
  font-size: 1.2rem;
  font-weight: bold;
}

.spinner {
  border: 6px solid #1c1c2e;
  border-top: 6px solid #FFD700;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

body.loaded {
  overflow: auto;
}

  </style>
</head>
<body>
<!-- Preloader -->
<div id="preloader">
  <div class="loader">
    <div class="spinner"></div>
    <p>Loading Ejsub...</p>
  </div>
</div>


<h1>Select Network & Choose a Plan</h1>

<!-- Network Tabs -->
<div class="tab-container">
  <?php foreach ($networks as $i => $net): ?>
    <button class="network-tab" onclick="filterPlans('<?= $net ?>', event)" <?= $i === 0 ? 'id="defaultNetwork"' : '' ?>>
      <?= htmlspecialchars($net) ?>
    </button>
  <?php endforeach; ?>
</div>

<!-- Plans Container -->
<div id="plans" class="cards-container">
  <?php foreach ($dataPlans as $plan): ?>
    <div class="data-card" data-network="<?= htmlspecialchars($plan['network']) ?>">
      <div class="network"><?= htmlspecialchars($plan['network']) ?></div>
      <div class="plan-name"><?= htmlspecialchars($plan['plan_name']) ?></div>
      <div class="price">₦<?= number_format($plan['price']) ?></div>

      <button class="buy-btn" onclick="toggleForm(this)">Buy Now</button>
      <form class="buy-form" onsubmit="buyData(event, this, <?= $user_id ?>, '<?= $plan['network'] ?>', <?= $plan['id'] ?>)">
        <input type="tel" name="phone" placeholder="Enter phone number" required>
        <button type="submit" class="buy-btn">Confirm Purchase</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<script>
function toggleForm(btn) {
  const form = btn.nextElementSibling;
  form.style.display = (form.style.display === 'block') ? 'none' : 'block';
}

// Show preloader on page load
window.addEventListener("load", function () {
  const preloader = document.getElementById("preloader");
  preloader.style.opacity = '0';
  setTimeout(() => {
    preloader.style.display = 'none';
    document.body.classList.add('loaded');
  }, 500);
});

// Show preloader manually before AJAX
function showPreloader() {
  const preloader = document.getElementById("preloader");
  preloader.style.display = 'flex';
  preloader.style.opacity = '1';
}

// Hide preloader manually
function hidePreloader() {
  const preloader = document.getElementById("preloader");
  preloader.style.opacity = '0';
  setTimeout(() => {
    preloader.style.display = 'none';
  }, 400);
}

// Handle data purchase
function buyData(event, form, userId, network, dataPlanId) {
  event.preventDefault();
  const phone = form.querySelector('input[name="phone"]').value;

  showPreloader(); // show loading screen on form submit

  fetch('data_verify.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: userId,
      phone: phone,
      network: network,
      data_plan: dataPlanId
    })
  })
  .then(res => res.json())
  .then(data => {
    hidePreloader(); // hide loading when response is received

    Swal.fire({
      title: data.status === 'success' ? 'Success ✅' : 'Error ❌',
      text: data.message,
      icon: data.status === 'success' ? 'success' : 'error',
      confirmButtonColor: '#FFD700'
    });

    if (data.status === 'success') {
      form.reset();
      form.style.display = 'none';
    }
  })
  .catch(err => {
    hidePreloader();
    Swal.fire("Error", "Something went wrong. Try again.", "error");
    console.error(err);
  });
}

// Filter plans by network
function filterPlans(network, event) {
  const cards = document.querySelectorAll('.data-card');
  cards.forEach(card => {
    card.style.display = (card.dataset.network === network) ? 'block' : 'none';
  });

  document.querySelectorAll('.network-tab').forEach(btn => btn.classList.remove('active-tab'));
  event.target.classList.add('active-tab');
}

// Auto-select first network
window.onload = function() {
  document.getElementById("defaultNetwork")?.click();
}
</script>


</body>
</html>

<?php $conn->close(); ?>
