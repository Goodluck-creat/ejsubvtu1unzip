<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=data_plans_export.xls");

echo "ID\tNetwork\tPlan Name\tPrice\tAgent Price\tPlan ID\tNetwork Code\n";

$result = $conn->query("SELECT * FROM data_plans");
while ($row = $result->fetch_assoc()) {
    echo "{$row['id']}\t{$row['network']}\t{$row['plan_name']}\t{$row['price']}\t{$row['agent_price']}\t{$row['plan_id']}\t{$row['network_code']}\n";
}
exit;
