<?php
session_start();
include("../connection.php");

// 🔒 Allow only admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// Fetch dynamic stats
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM vtu_users")->fetch_assoc()['count'] ?? 0;
$walletBalance = $conn->query("SELECT SUM(wallet_balance) AS total FROM vtu_users")->fetch_assoc()['total'] ?? 0;
$todayRevenue = $conn->query("SELECT SUM(amount) AS revenue FROM data_transactions WHERE DATE(created_at) = CURDATE() AND status = 'success'")->fetch_assoc()['revenue'] ?? 0;
$failedTxns = $conn->query("SELECT COUNT(*) AS count FROM data_transactions WHERE DATE(created_at) = CURDATE() AND status = 'failed'")->fetch_assoc()['count'] ?? 0;
$monthlyProfit = $conn->query("SELECT SUM(profit) AS total FROM data_transactions WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = 'success'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VTU Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('hidden');
    }
  </script>
</head>
<body class="bg-gray-100 dark:bg-black text-gray-900 dark:text-white">
  <div class="md:flex">
    <!-- Sidebar -->
    <div id="sidebar" class="w-full md:w-1/4 bg-white dark:bg-gray-800 p-4 hidden md:block">
      <h2 class="text-xl font-bold mb-4">VTU Admin</h2>
      <ul class="space-y-2">
        <li><a href="dashboard.php" class="block p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Dashboard</a></li>
        <li><a href="users.php" class="block p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Users</a></li>
        <li><a href="data.php" class="block p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Data Transactions</a></li>
        <li><a href="" class="block p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Transactions</a></li>
        <li><a href="" class="block p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Add Services</a></li>
        <li><a href="" class="block p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Profits</a></li>
      <li class="relative">
  <button onclick="toggleDropdown()" class="w-full text-left p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 flex justify-between items-center">
    Settings
    <svg class="w-4 h-4 ml-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </button>
  <ul id="dropdownMenu" class="hidden absolute left-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-md z-10">
    <li><a href="" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">All Settings</a></li>
    <li><a href="airtime_settings.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Airtime Settings</a></li>
     <li><a href="add_airtime_offer.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"> Add Airtime</a></li>
    <li><a href="data_settings.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Data Settings</a></li>
    <li><a href="cable_settings.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Cable TV Settings</a></li>
    <li><a href="electricity_settings.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Electricity Settings</a></li>
  </ul>
</li>

<script>
  function toggleDropdown() {
    const menu = document.getElementById("dropdownMenu");
    menu.classList.toggle("hidden");
  }

  // Optional: close when clicking outside
  document.addEventListener("click", function (e) {
    const btn = document.querySelector("button[onclick='toggleDropdown()']");
    const dropdown = document.getElementById("dropdownMenu");
    if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.add("hidden");
    }
  });
</script>

      </ul>
    </div>

    <!-- Main Content -->
    <div class="w-full md:w-3/4">
      <!-- Topbar -->
      <div class="flex justify-between items-center px-4 py-6 md:px-10">
        <button class="md:hidden bg-gray-200 dark:bg-gray-700 px-3 py-2 rounded" onclick="toggleSidebar()">☰</button>
        <h1 class="text-2xl md:text-3xl font-bold">Welcome, Admin Chioma</h1>
        <div class="flex items-center gap-4">
          <button class="flex items-center gap-2 bg-gray-200 dark:bg-gray-700 px-3 py-2 rounded shadow">🔔 Alerts</button>
          <a href="logout.php" class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded shadow">🚪 Logout</a>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 px-4 md:px-10">
        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl shadow-md">
          <div class="text-3xl mb-2">👥</div>
          <p class="text-sm text-gray-500">Total Users</p>
          <h2 class="text-xl font-bold"><?php echo $totalUsers; ?></h2>
        </div>
        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl shadow-md">
          <div class="text-3xl mb-2">💼</div>
          <p class="text-sm text-gray-500">Total Wallet Balance</p>
          <h2 class="text-xl font-bold">₦<?php echo number_format($walletBalance, 2); ?></h2>
        </div>
        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl shadow-md">
          <div class="text-3xl mb-2">💰</div>
          <p class="text-sm text-gray-500">Today's Revenue</p>
          <h2 class="text-xl font-bold">₦<?php echo number_format($todayRevenue, 2); ?></h2>
        </div>
        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl shadow-md">
          <div class="text-3xl mb-2"></div>
          <p class="text-sm text-gray-500">Failed Transactions Today</p>
          <h2 class="text-xl font-bold"><?php echo $failedTxns; ?></h2>
        </div>
        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl shadow-md">
          <div class="text-3xl mb-2">📈</div>
          <p class="text-sm text-gray-500">Monthly Profit</p>
          <h2 class="text-xl font-bold">₦<?php echo number_format($monthlyProfit, 2); ?></h2>
        </div>
      </div>

      <!-- Animation -->
      <div class="flex justify-center mt-10">
        <lottie-player
          src="https://assets4.lottiefiles.com/packages/lf20_5ngs2ksb.json"
          background="transparent"
          speed="1"
          style="width: 200px; height: 200px"
          loop
          autoplay>
        </lottie-player>
      </div>
    </div>
  </div>
</body>
</html>
