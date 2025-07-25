<?php
session_start();
include('../connection.php');

// 🔒 Allow only admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// Fetch all electricity transactions
$query = "SELECT * FROM vtu_electricity_payments ORDER BY created_at DESC";
$result = $conn->query($query);

// Calculate total profit
$profitQuery = "SELECT SUM(profit) AS total_profit FROM vtu_electricity_payments";
$profitResult = $conn->query($profitQuery);
$totalProfit = $profitResult->fetch_assoc()['total_profit'] ?? 0;

// Handle Excel Export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=electricity_transactions.xls");
    echo "ID\tUser ID\tDisco\tMeter Type\tMeter No\tAmount\tCharges\tProfit\tStatus\tCreated At\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['id']}\t{$row['user_id']}\t{$row['disco']}\t{$row['meter_type']}\t{$row['meter_number']}\t₦{$row['amount']}\t₦{$row['charges']}\t₦{$row['profit']}\t{$row['status']}\t{$row['created_at']}\n";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Electricity Transactions - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Electricity Transactions</h1>
            <a href="?export=excel" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">⬇ Export to Excel</a>
        </div>

        <p class="text-lg mb-4 font-semibold">Total Profit: <span class="text-green-600">₦<?php echo number_format($totalProfit, 2); ?></span></p>

        <div class="overflow-x-auto">
            <table class="min-w-full table-auto text-sm border border-gray-300">
                <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="border px-2 py-2">ID</th>
                        <th class="border px-2 py-2">User ID</th>
                        <th class="border px-2 py-2">Disco</th>
                        <th class="border px-2 py-2">Meter Type</th>
                        <th class="border px-2 py-2">Meter Number</th>
                        <th class="border px-2 py-2">Amount (₦)</th>
                        <th class="border px-2 py-2">Charges</th>
                        <th class="border px-2 py-2">Profit (₦)</th>
                        <th class="border px-2 py-2">Status</th>
                        <th class="border px-2 py-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border px-2 py-1"><?php echo $row['id']; ?></td>
                                <td class="border px-2 py-1"><?php echo $row['user_id']; ?></td>
                                <td class="border px-2 py-1"><?php echo $row['disco']; ?></td>
                                <td class="border px-2 py-1"><?php echo $row['meter_type']; ?></td>
                                <td class="border px-2 py-1"><?php echo $row['meter_number']; ?></td>
                                <td class="border px-2 py-1">₦<?php echo $row['amount']; ?></td>
                                <td class="border px-2 py-1">₦<?php echo $row['charges']; ?></td>
                                <td class="border px-2 py-1 text-green-600">₦<?php echo $row['profit']; ?></td>
                                <td class="border px-2 py-1"><?php echo ucfirst($row['status']); ?></td>
                                <td class="border px-2 py-1"><?php echo $row['created_at']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-red-500">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
