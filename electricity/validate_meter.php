<?php
$meter = $_GET['meter_number'] ?? '';
$disco = $_GET['disco'] ?? '';
$type = $_GET['meter_type'] ?? '';

if (!$meter || !$disco || !$type) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$url = "https://teetechglobal.com/api/bill/bill-validation?meter_number={$meter}&disco={$disco}&meter_type={$type}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Token s472AeibdqBckxIblB6AhnCorAa5ymCB21Ct5CJ90p3AvBDw3G32x8BAxx6C1690281110"
]);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
