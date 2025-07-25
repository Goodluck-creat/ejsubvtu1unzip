<?php
include("connection.php");

// Airtime networks Subvas supports
$airtime_networks = [
    ['network' => 'MTN', 'code' => 1],
    ['network' => 'GLO', 'code' => 2],
    ['network' => 'AIRTEL', 'code' => 3],
    ['network' => '9MOBILE', 'code' => 4],
];

foreach ($airtime_networks as $network) {
    $name = $network['network'];
    $code = $network['code'];
    $price = 0.00; // You can update this later if needed

    $stmt = $conn->prepare("INSERT INTO airtime_services (network, code, base_price, status)
    VALUES (?, ?, ?, 'active')
    ON DUPLICATE KEY UPDATE status='active'");
    $stmt->bind_param("sid", $name, $code, $price);
    $stmt->execute();
}

echo "Airtime services set successfully.";
?>
