<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$iuc = $_GET['iuc'] ?? '';
$cable = $_GET['cable'] ?? '';

$url = "https://teetechglobal.com/api/cable/cable-validation?iuc=$iuc&cable=$cable";

$headers = [
  "Authorization: Token s472AeibdqBckxIblB6AhnCorAa5ymCB21Ct5CJ90p3AvBDw3G32x8BAxx6C1690281110",
  "Content-Type: application/json"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
