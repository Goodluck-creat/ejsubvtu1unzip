<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$apiKey = "5DQNRWD-7744EW6-PECPTKE-CYT1SXZ";

$amount = $_GET['amount']; // e.g., 1000
$user_id = $_SESSION['user_id'];
$order_id = uniqid("vtu_");

// Save order in DB
include("connection.php");
$conn->query("INSERT INTO crypto_orders (user_id, amount, order_id) VALUES ('$user_id', '$amount', '$order_id')");

// Create Invoice
$data = [
  "price_amount" => $amount,
  "price_currency" => "ngn",
  "pay_currency" => "ltc", // Bitcoin
  "order_id" => $order_id,
  "order_description" => "Wallet funding for user ID $user_id",
  "ipn_callback_url" => "https://ejsub.com/crypto_webhook.php"
];

$ch = curl_init('https://api.nowpayments.io/v1/invoice');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'x-api-key: ' . $apiKey,
  'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$res = json_decode($response, true);

if (isset($res['invoice_url'])) {
  header("Location: " . $res['invoice_url']);
  exit;
} else {
  echo "Error creating invoice.";
  print_r($res);
}
