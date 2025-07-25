<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");
include("../connection.php");

// Get input
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;
$phone = $data['phone'] ?? null;
$network = $data['network'] ?? null;
$local_plan_id = $data['data_plan'] ?? null;
$ref = uniqid("ds_");

// Validate input
if (!$user_id || !$phone || !$network || !$local_plan_id) {
    echo json_encode(["status" => "error", "message" => "Missing required data"]);
    exit;
}

// Get plan details
$plan_stmt = $conn->prepare("SELECT * FROM data_plans WHERE id = ?");
$plan_stmt->bind_param("i", $local_plan_id);
$plan_stmt->execute();
$plan_result = $plan_stmt->get_result();

if ($plan_result->num_rows < 1) {
    echo json_encode(["status" => "error", "message" => "Plan not found"]);
    exit;
}

$plan = $plan_result->fetch_assoc();
$price = $plan['price'];
$agent_price = $plan['agent_price'] ?? 0;
$profit = $price - $agent_price;
$api_plan_id = $plan['plan_id'];
$plan_name = $plan['plan_name'];
$api_network = $plan['network'];

// Check user wallet
$user_stmt = $conn->prepare("SELECT wallet_balance FROM vtu_users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows < 1) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

$user = $user_result->fetch_assoc();
if ($user['wallet_balance'] < $price) {
    echo json_encode(["status" => "error", "message" => "Insufficient wallet balance"]);
    exit;
}

// Deduct balance
$deduct_stmt = $conn->prepare("UPDATE vtu_users SET wallet_balance = wallet_balance - ? WHERE id = ?");
$deduct_stmt->bind_param("di", $price, $user_id);
$deduct_stmt->execute();

// Call API
$payload = json_encode([
    "network" => $plan['network_code'],
    "phone" => $phone,
    "ref" => $ref,
    "data_plan" => $api_plan_id,
    "ported_number" => false
]);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://datasource.com.ng/api/data/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization:",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => $payload
]);

$response = curl_exec($curl);
curl_close($curl);

// Decode response
file_put_contents("log.txt", "[" . date("Y-m-d H:i:s") . "] API Response: $response" . PHP_EOL, FILE_APPEND);
$result = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $jsonError = json_last_error_msg();
    echo json_encode(["status" => "error", "message" => "Invalid API response: $jsonError"]);
    exit;
}

// Check success
$status = strtolower($result['status'] ?? '');
$altStatus = strtolower($result['Status'] ?? '');
$finalStatus = (in_array($status, ['success', 'successful']) || in_array($altStatus, ['success', 'successful'])) ? 'success' : 'failed';
$message = $result['message'] ?? 'put valid number';

// Save to `data_transactions`
$trans_stmt = $conn->prepare("INSERT INTO data_transactions (user_id, network, plan_id, plan_name, phone, amount, profit, status, transaction_id, api_response) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$api_response_json = json_encode($result);
$trans_stmt->bind_param("isissddsss", $user_id, $api_network, $local_plan_id, $plan_name, $phone, $price, $profit, $finalStatus, $ref, $api_response_json);

if ($trans_stmt->execute()) {
    if ($finalStatus === "success") {
        echo json_encode(["status" => "success", "message" => "Data purchase successful", "ref" => $ref]);
    } else {
        // Refund
        $refund_stmt = $conn->prepare("UPDATE vtu_users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $refund_stmt->bind_param("di", $price, $user_id);
        $refund_stmt->execute();

        echo json_encode(["status" => "error", "message" => "Purchase failed: $message"]);
    }
} else {
    file_put_contents("log.txt", "[" . date("Y-m-d H:i:s") . "] Insert Error: " . $trans_stmt->error . PHP_EOL, FILE_APPEND);
    echo json_encode(["status" => "error", "message" => "Database error: " . $trans_stmt->error]);
}
?>
