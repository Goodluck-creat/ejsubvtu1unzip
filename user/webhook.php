<?php
include("connection.php");

// ✅ Add your Flutterwave secret hash
$secretHash = 'rechargehub_202507085857994daddychioma';

// ✅ Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("❌ Invalid request method.");
}

// ✅ Validate secret hash header
$signature = $_SERVER['HTTP_VERIF_HASH'] ?? '';
if (!$signature || $signature !== $secretHash) {
    http_response_code(403);
    exit("❌ Invalid secret hash.");
}

// ✅ Get and log raw input
$input = file_get_contents("php://input");
file_put_contents("webhook_log.txt", "RAW INPUT: $input" . PHP_EOL, FILE_APPEND);

// ✅ Decode input
$data = json_decode($input, true);
file_put_contents("webhook_log.txt", "DECODED: " . print_r($data, true), FILE_APPEND);

// ✅ Validate event and status
if (
    !$data ||
    !isset($data['event']) ||
    $data['event'] !== 'charge.completed' ||
    $data['data']['status'] !== 'successful'
) {
    http_response_code(400);
    exit("❌ Invalid or unsuccessful transaction.");
}

// ✅ Extract data
$email    = $data['data']['customer']['email'];
$fullname = $data['data']['customer']['name'];
$amount   = $data['data']['amount'];
$flw_ref  = $data['data']['flw_ref'];
$acct_no  = $data['data']['account_number'];

// ✅ Prevent duplicate transactions
$stmt = $conn->prepare("SELECT id FROM vtu_transactions WHERE reference = ?");
$stmt->bind_param("s", $flw_ref);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(200);
    exit("✅ Already processed.");
}

// ✅ Find the user by email or account number
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

// ✅ Update wallet balance
$update = $conn->prepare("UPDATE vtu_users SET wallet_balance = ? WHERE id = ?");
$update->bind_param("di", $new_balance, $user['id']);
$update->execute();

// ✅ Record the transaction
$insert = $conn->prepare("INSERT INTO vtu_transactions (user_id, reference, amount, status, date) VALUES (?, ?, ?, 'success', NOW())");
$insert->bind_param("isd", $user['id'], $flw_ref, $amount);
$insert->execute();

// ✅ Send Email Notification
$to = $email;
$subject = "✅ Wallet Credit Alert - ₦" . number_format($amount, 2);
$message = "Hello $fullname,\n\nYour wallet has been successfully funded with ₦" . number_format($amount, 2) . ".\n\nReference: $flw_ref\n\nThank you for using our VTU platform.\n\nEjsub Team";
$headers = "From: Ejsub.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mail($to, $subject, $message, $headers);

http_response_code(200);
exit("✅ Wallet funded and email sent.");
