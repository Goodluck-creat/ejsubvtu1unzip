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
    "Authorization: Token "
]);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
