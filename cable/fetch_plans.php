<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../connection.php");

header('Content-Type: application/json');

$cable_id = isset($_GET['cable_id']) ? intval($_GET['cable_id']) : 0;

if ($cable_id === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid cable_id"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, plan_name, final_amount FROM Tcable_plans WHERE cable_id = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Query failed"]);
    exit;
}

$stmt->bind_param("i", $cable_id);
$stmt->execute();
$result = $stmt->get_result();

$plans = [];

while ($row = $result->fetch_assoc()) {
    $plans[] = $row;
}

echo json_encode($plans);
