<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

include("../header.php");
include("connection.php");
$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT fullname, email, flutterwave_bank_name, flutterwave_account_number, wallet_balance FROM vtu_users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$hasWallet = !empty($user['flutterwave_account_number']) && !empty($user['flutterwave_bank_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title> EJSUB User Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
  :root {
    --primary: #00A86B;
    --secondary: #1E1E2F;
    --accent: #FFD700;
    --bg-light: #f5f5f5;
    --text-light: #333;
    --bg-dark: #1E1E2F;
    --text-dark: #fff;
  }

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
  }

  body {
    background: var(--bg-dark);
    color: var(--text-dark);
    transition: background 0.3s, color 0.3s;
  }

  .container {
    max-width: 100%;
    margin: 30px auto;
    padding: 15px;
    background: #2e2e40;
    border-radius: 14px;
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.25);
    animation: fadeIn 1s ease-in-out;
  }

  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
  }

  .header h2 {
    color: var(--accent);
    font-weight: 600;
    font-size: 20px;
  }

  .theme-icon {
    font-size: 18px;
    cursor: pointer;
    color: var(--accent);
  }

  .scroll-wrapper {
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 10px;
    margin-bottom: 20px;
  }

  .card {
    display: inline-block;
    min-width: 180px;
    max-width: 220px;
    background: #3a3a50;
    border-radius: 10px;
    padding: 12px;
    margin: 10px 10px 10px 0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
    vertical-align: top;
    font-size: 14px;
  }

  .card:hover {
    transform: translateY(-5px);
  }

  .label {
    color: var(--accent);
    font-size: 12px;
  }

  .value {
    font-size: 16px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 5px;
  }

  .copy-btn {
    background: none;
    border: none;
    color: var(--accent);
    font-size: 14px;
    cursor: pointer;
  }

  .services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-top: 20px;
  }

  .service-card {
    background: #3a3a50;
    padding: 12px;
    border-radius: 10px;
    text-align: center;
    font-size: 13px;
    color: white;
    transition: 0.3s ease;
    cursor: pointer;
  }

  .service-card:hover {
    background: var(--primary);
    color: #fff;
  }

  .activate-box {
    background: #553939;
    color: #fff;
    text-align: center;
    padding: 18px;
    border-radius: 10px;
    font-size: 14px;
  }

  .activate-btn {
    background: var(--accent);
    color: var(--secondary);
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    margin-top: 12px;
    font-weight: bold;
    font-size: 14px;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @media screen and (max-width: 768px) {
    .card, .service-card {
      min-width: 100% !important;
      max-width: 100%;
      font-size: 13px;
      padding: 10px;
    }

    .container {
      padding: 10px;
    }

    .scroll-wrapper {
      padding-bottom: 10px;
    }
  }

  @media screen and (max-width: 600px) {
    table {
      font-size: 13px;
    }

    th, td {
      padding: 6px;
      white-space: nowrap;
    }

    .value {
      font-size: 14px;
    }

    .activate-btn {
      font-size: 13px;
      padding: 8px 12px;
    }
  }
  
  @keyframes bounceRight {
  0%, 100% {
    transform: translateX(0);
  }
  50% {
    transform: translateX(8px);
  }
}

.swipe-hint {
  display: none;
  text-align: center;
  margin-top: -15px;
  margin-bottom: 20px;
  color: var(--accent);
  font-size: 14px;
  animation: bounceRight 1.5s infinite;
}

@media screen and (max-width: 768px) {
  .swipe-hint {
    display: block;
  }
}

</style>

</head>
<body>
    
  <div class="container">
    <div class="header">
      <h2><i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['fullname']) ?></h2>
      <i class="fa-solid fa-circle-half-stroke theme-icon" onclick="toggleTheme()" title="Toggle Theme"></i>
    </div>

    <div class="scroll-wrapper">
      <div class="card">
        <div class="label">Wallet Balance</div>
        <div class="value"><i class="fa-solid fa-wallet"></i> ₦<?= number_format($user['wallet_balance'], 2) ?>
        </div>
      </div><br><br>
<!--      <div class="swipe-hint">-->
<!--  👉 Swipe to see more-->
<!--</div>-->

      <?php if ($hasWallet): ?>
        <div class="card">
          <div class="label">Bank</div>
          <div class="value"><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($user['flutterwave_bank_name']) ?></div>
        </div><br><br><br>
        <div class="card">
          <div class="label">Account Number</div>
          <div class="value">
            <i class="fa-solid fa-hashtag"></i>
            <span id="acct"><?= htmlspecialchars($user['flutterwave_account_number']) ?></span>
            <button class="copy-btn" onclick="copyAccountNumber()" title="Copy"><i class="fa-solid fa-copy"></i></button>
          </div>
          <small style="color:#ccc;">Copy account number and fund your wallet</small>
        </div>
    <?php else: ?>
    

  <div class="card activate-box">
    <div class="label">Activate Wallet</div>
    
    <!-- Toggle Button -->
    <button onclick="toggleNINBox()" class="activate-btn" type="button">Activate Wallet</button>

    <!-- Hidden NIN Form -->
    <form method="POST" action="generate_account.php" id="nin-box" style="display: none; margin-top: 15px;">
      <!--<label for="nin">NIN (11 digits)</label>-->
     <input type="text" name="nin" id="nin" pattern="\d{11}" required 
  style="margin: 10px 0; padding: 8px; width: 100%; max-width: 400px; border-radius: 6px; " placeholder="NIN (11 digits)"><br><br>

      <!--<label for="phone">Phone Number</label>-->
     <input type="tel" name="phone" id="phone" required 
  value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
  style="margin: 10px 0; padding: 8px; width: 100%; max-width: 400px; border-radius: 6px;" placeholder="Phone Number"><br><br>


      <button type="submit" class="activate-btn" style="margin-top: 10px;">Generate Account</button>
    </form>
  </div>
<?php endif; ?>
<div style="margin-top: 30px; background: #3a3a50; padding: 20px; border-radius: 12px;">
  <h3 style="color: var(--accent); margin-bottom: 10px;">💰 Fund Wallet with Crypto</h3>
  
  <form method="GET" action="pay_with_crypto.php" style="display: flex; flex-wrap: wrap; gap: 10px;">
    <input 
      type="number" 
      name="amount" 
      placeholder="Enter amount in ₦" 
      min="1000" 
      required
      style="flex: 1; min-width: 150px; padding: 10px; border-radius: 8px; border: none;"
    >
    <button type="submit" class="activate-btn" style="padding: 10px 16px; white-space: nowrap;">Proceed</button>
  </form>

  <p style="font-size: 14px; color: #ccc; margin-top: 10px;">will be redirected to a crypto payment page.</p>
</div>




    <!--  <div class="card">-->
    <!--    <div class="label">Email</div>-->
    <!--    <div class="value"><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></div>-->
    <!--  </div>-->
    <!--  <div class="card">-->
    <!--    <div class="label">Full Name</div>-->
    <!--    <div class="value"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($user['fullname']) ?></div>-->
    <!--  </div>-->
    <!--</div>-->

    <div class="services">
      <a href="../electricity/electricity_form.php" class="service-card"><i class="fa-solid fa-bolt"></i><br>Pay Utility</a>
      <a href="../airtime/buy_airtime.php" class="service-card"><i class="fa-solid fa-phone"></i><br>Airtime</a>
      <a href="../data/buy_data.php" class="service-card"><i class="fa-solid fa-wifi"></i><br>Data</a>
      <a href="../cable/cable_form.php" class="service-card"><i class="fa-solid fa-tv"></i><br>Cable TV</a>
    </div>
  </div>
  
  
  
  <style>
  .dropdown-toggle {
    background: var(--primary);
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 5px;
    margin-bottom: 10px;
  }

  .history-wrapper {
    display: none;
    margin-top: 20px;
  }

  .pagination {
    text-align: center;
    margin-top: 15px;
  }

  .pagination a {
    color: var(--accent);
    padding: 8px 14px;
    text-decoration: none;
    margin: 0 3px;
    border-radius: 4px;
    background: #333;
  }

  .pagination a.active {
    background: var(--primary);
    color: white;
  }
  @media screen and (max-width: 600px) {
  .card, .service-card {
    min-width: 100%;
  }

  table {
    font-size: 13px;
  }

  th, td {
    padding: 8px;
    white-space: nowrap;
  }
}

</style>

<button class="dropdown-toggle" onclick="toggleHistory()">🧾 View Transaction History</button>

<div class="history-wrapper" id="history-section" style="overflow-x:auto;">
  <table style="width: 100%; min-width: 500px; background: #3a3a50; color: white; border-radius: 10px; overflow: hidden;">

    <thead style="background: var(--primary);">
      <tr>
        <th style="padding: 10px;">#</th>
        <th>Amount (₦)</th>
        <th>Reference</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $limit = 10;
      $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
      $offset = ($page - 1) * $limit;

      $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM vtu_transactions WHERE user_id = ?");
      $stmt->bind_param("i", $uid);
      $stmt->execute();
      $result = $stmt->get_result();
      $total = $result->fetch_assoc()['total'];
      $pages = ceil($total / $limit);

      $stmt = $conn->prepare("SELECT amount, reference, status, date FROM vtu_transactions WHERE user_id = ? ORDER BY date DESC LIMIT ?, ?");
      $stmt->bind_param("iii", $uid, $offset, $limit);
      $stmt->execute();
      $res = $stmt->get_result();
      $count = $offset + 1;
      while ($row = $res->fetch_assoc()):
      ?>
     <tr style="text-align: center;">
  <td style="padding: 10px;"><?= $count++ ?></td>
  <td>₦<?= number_format($row['amount'], 2) ?></td>
  <td><?= htmlspecialchars($row['reference']) ?></td>
  <td><?= ucfirst($row['status']) ?></td>
  <td>
    <?php
      $date = new DateTime($row['date'], new DateTimeZone("UTC"));
      $date->setTimezone(new DateTimeZone("Africa/Lagos"));
      echo $date->format("j M Y h:i A");
    ?>
  </td>
</tr>

      <?php endwhile; ?>
    </tbody>
  </table>

  <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<script>
  function toggleHistory() {
    const section = document.getElementById('history-section');
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
  }
</script>



  <script>
  function toggleNINBox() {
    const box = document.getElementById("nin-box");
    box.style.display = (box.style.display === "none") ? "block" : "none";
  }
</script>


  <script>
    function toggleTheme() {
      const body = document.body;
      const isDark = body.style.background === 'var(--bg-light)';
      body.style.background = isDark ? 'var(--bg-dark)' : 'var(--bg-light)';
      body.style.color = isDark ? 'var(--text-dark)' : 'var(--text-light)';
      document.querySelectorAll('.card, .service-card').forEach(el => {
        el.style.background = isDark ? '#3a3a50' : '#fff';
        el.style.color = isDark ? '#fff' : '#333';
      });
    }

    function copyAccountNumber() {
      const acct = document.getElementById("acct").innerText;
      navigator.clipboard.writeText(acct).then(() => {
        alert("Account number copied!");
      }, () => {
        alert("Failed to copy account number.");
      });
    }
  </script>
  
    
</body>
</html>
