<?php
session_start();
include("../connection.php");

// ✅ Optional: restrict to admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// Fetch all transactions
$query = "SELECT * FROM cable_transactions ORDER BY id DESC";
$result = $conn->query($query);

// Total profit (if `profit` column exists)
$profitQuery = "SELECT SUM(profit) AS total_profit FROM cable_transactions";
$profitResult = $conn->query($profitQuery);
$totalProfit = $profitResult->fetch_assoc()['total_profit'] ?? 0;

// Handle Excel Export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=cable_transactions.xls");
    echo "ID\tUser ID\tCable\tIUC\tPlan ID\tAmount\tStatus\tMessage\tProfit\tRequest ID\tDate\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['id']}\t{$row['user_id']}\t{$row['cable']}\t{$row['iuc']}\t{$row['cable_plan']}\t{$row['amount']}\t{$row['status']}\t{$row['message']}\t{$row['profit']}\t{$row['request_id']}\t{$row['created_at']}\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - Cable Transactions</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-xl font-bold">Cable TV Transactions</h1>
      <a href="?export=excel" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">⬇ Export to Excel</a>
    </div>

    <p class="mb-4 font-semibold text-lg">Total Profit: <span class="text-green-600">₦<?= number_format($totalProfit, 2) ?></span></p>

    <div class="overflow-x-auto">
      <table class="min-w-full table-auto border border-gray-300 text-sm">
        <thead class="bg-gray-200 text-gray-700">
          <tr>
            <th class="border px-2 py-1">ID</th>
            <th class="border px-2 py-1">User ID</th>
            <th class="border px-2 py-1">Cable</th>
            <th class="border px-2 py-1">IUC</th>
            <th class="border px-2 py-1">Plan</th>
            <th class="border px-2 py-1">Amount</th>
            <th class="border px-2 py-1">Status</th>
            <th class="border px-2 py-1">Message</th>
            <th class="border px-2 py-1">Profit</th>
            <th class="border px-2 py-1">Request ID</th>
            <th class="border px-2 py-1">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-50">
                <td class="border px-2 py-1"><?= $row['id'] ?></td>
                <td class="border px-2 py-1"><?= $row['user_id'] ?></td>
                <td class="border px-2 py-1"><?= htmlspecialchars($row['cable']) ?></td>
                <td class="border px-2 py-1"><?= $row['iuc'] ?></td>
                <td class="border px-2 py-1"><?= $row['plan_name'] ?? $row['cable_plan'] ?></td>
                <td class="border px-2 py-1">₦<?= number_format($row['amount'], 2) ?></td>
                <td class="border px-2 py-1"><?= ucfirst($row['status']) ?></td>
                <td class="border px-2 py-1"><?= htmlspecialchars($row['message']) ?></td>
                <td class="border px-2 py-1 text-green-600">₦<?= number_format($row['profit'] ?? 0, 2) ?></td>
                <td class="border px-2 py-1"><?= $row['request_id'] ?></td>
                <td class="border px-2 py-1"><?= $row['created_at'] ?? '' ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="11" class="text-center py-4 text-red-500">No cable transactions found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
