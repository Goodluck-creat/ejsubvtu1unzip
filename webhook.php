<?php
include("connection.php");

// ✅ Add your secret hash from Flutterwave dashboard
$secretHash = ''; // 🔒 Replace with your actual secret hash

// ✅ Check for correct method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("❌ Invalid request method.");
}

// ✅ Validate secret hash from headers
$signature = $_SERVER['HTTP_VERIF_HASH'] ?? '';
if (!$signature || $signature !== $secretHash) {
    http_response_code(403);
    exit("❌ Invalid secret hash.");
}

// ✅ Read input
$input = file_get_contents("php://input");
file_put_contents("webhook_log.txt", "RAW INPUT: $input" . PHP_EOL, FILE_APPEND);

// ✅ Decode input
$data = json_decode($input, true);
file_put_contents("webhook_log.txt", "DECODED: " . print_r($data, true), FILE_APPEND);

// ✅ Validate event
if (!$data || !isset($data['event']) || $data['event'] !== 'charge.completed') {
    http_response_code(400);
    exit("❌ Not a charge.completed event.");
}

// ✅ Extract data
$event      = $data['event'];
$tx_id      = $data['data']['id'];
$email      = $data['data']['customer']['email'];
$amount     = $data['data']['amount'];
$flw_ref    = $data['data']['flw_ref'];
$acct_no    = $data['data']['account_number'];
$bankname   = $data['data']['bank_name'] ?? 'Flutterwave';

// ✅ Check if already credited
$stmt = $conn->prepare("SELECT id FROM vtu_transactions WHERE reference = ?");
$stmt->bind_param("s", $flw_ref);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(200);
    exit("✅ Already processed.");
}

// ✅ Find user
$stmt = $conn->prepare("SELECT id, wallet_balance FROM vtu_users WHERE email = ? OR flutterwave_account_number = ?");
$stmt->bind_param("ss", $email, $acct_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit("❌ User not found.");
}

$user = $result->fetch_assoc();
$new_balance = $user['wallet_balance'] + $amount;

// ✅ Update wallet
$update = $conn->prepare("UPDATE vtu_users SET wallet_balance = ? WHERE id = ?");
$update->bind_param("di", $new_balance, $user['id']);
$update->execute();

// ✅ Save transaction
$insert = $conn->prepare("INSERT INTO vtu_transactions (user_id, reference, amount, status, date) VALUES (?, ?, ?, 'success', NOW())");
$insert->bind_param("isd", $user['id'], $flw_ref, $amount);
$insert->execute();

http_response_code(200);
exit("✅ Wallet funded successfully for user ID: {$user['id']}");
