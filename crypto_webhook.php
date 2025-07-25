<?php
$secret = "5DQNRWD-7744EW6-PECPTKE-CYT1SXZ"; // Your IPN secret
$hmac_header = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];
$json = file_get_contents("php://input");
$calculated_hmac = hash_hmac("sha512", $json, $secret);

if ($hmac_header !== $calculated_hmac) {
  http_response_code(401);
  exit("HMAC verification failed");
}

$data = json_decode($json, true);
if ($data['payment_status'] === 'finished') {
  $order_id = $data['order_id'];

  include("connection.php");

  $check = $conn->query("SELECT * FROM crypto_orders WHERE order_id = '$order_id' AND status = 'pending'");
  if ($check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $user_id = $row['user_id'];
    $amount = $row['amount'];

    // Credit wallet
    $conn->query("UPDATE vtu_users SET wallet_balance = wallet_balance + $amount WHERE id = $user_id");
    $conn->query("UPDATE crypto_orders SET status = 'paid' WHERE order_id = '$order_id'");

    // Add transaction log
    $ref = $data['payment_id'];
    $status = 'success';
    $type = 'crypto';
    $desc = 'Wallet funding via crypto';

    $conn->query("INSERT INTO vtu_transactions (user_id, amount, reference, status, payment_type, description, date) 
                  VALUES ($user_id, $amount, '$ref', '$status', '$type', '$desc', NOW())");
  }
}
