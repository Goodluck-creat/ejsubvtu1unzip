<?php
session_start();
include("../connection.php");

// 🔒 Allow only admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}
// Fetch successful transactions
$query = "SELECT * FROM data_transactions WHERE status = 'success' ORDER BY id DESC";
$result = $conn->query($query);

// Initialize total profit
$totalProfit = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Transactions</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
        }

        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #333;
            color: white;
        }

        .profit {
            font-weight: bold;
            margin-top: 15px;
            color: green;
        }

        .not-profitable {
            color: red;
        }
    </style>
</head>
<body>

    <h2>✅ Successful Data Transactions</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>Network</th>
            <th>Plan Name</th>
            <th>Phone</th>
            <th>Amount Paid</th>
            <th>Agent Price</th>
            <th>Profit</th>
            <th>Status</th>
            <th>Transaction ID</th>
            <th>Date</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Sum total profit
                $totalProfit += $row['profit'];

                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['user_id']}</td>
                        <td>{$row['network']}</td>
                        <td>{$row['plan_name']}</td>
                        <td>{$row['phone']}</td>
                        <td>₦" . number_format($row['amount'], 2) . "</td>
                        <td>₦0.00</td>
                        <td>₦" . number_format($row['profit'], 2) . "</td>
                        <td>{$row['status']}</td>
                        <td>{$row['transaction_id']}</td>
                        <td>{$row['created_at']}</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='11'>No successful transactions found.</td></tr>";
        }
        ?>
    </table>

    <div class="profit">
        Total Profit: ₦<?= number_format($totalProfit, 2) ?>
    </div>

</body>
</html>
