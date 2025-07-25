<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=airtime_offers.xls");

$result = $conn->query("SELECT * FROM airtime_offers ORDER BY id DESC");

echo "ID\tNetwork\tCode\tAmount\tDiscount Price\tActive\n";
while ($row = $result->fetch_assoc()) {
    echo "{$row['id']}\t{$row['network_name']}\t{$row['network_code']}\t{$row['amount']}\t{$row['discount_price']}\t" . ($row['active'] ? "Yes" : "No") . "\n";
}
