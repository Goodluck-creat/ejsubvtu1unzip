<?php
include("connection.php");

// ✅ Safely get values from the URL
$ref = $_GET['ref'] ?? '';
$name = $_GET['name'] ?? '';
$email = $_GET['email'] ?? '';
$phone = $_GET['phone'] ?? '';
$amount = $_GET['amount'] ?? '';
$network_code = $_GET['network'] ?? '';

// ✅ Step 1: Verify payment with Paystack
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/$ref",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer sk_live_", // ⚠️ Replace with your real key
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

// ✅ Step 2: Proceed if payment was successful
if (isset($result['data']['status']) && $result['data']['status'] == 'success') {
    
    // ✅ Get network name & base price
    $stmt = $conn->prepare("SELECT network, base_price FROM airtime_services WHERE code = ?");
    $stmt->bind_param("i", $network_code);
    $stmt->execute();
    $stmt->bind_result($network_name, $base_price);
    $stmt->fetch();
    $stmt->close();

    // ✅ Calculate profit
    $amount_paid = (float)$amount;
    $cost_price = (float)$base_price > 0 ? $base_price : ($amount_paid - 3.00);
    $profit = $amount_paid - $cost_price;

    // ✅ Step 3: Deliver Airtime via Subvas
    $payload = [
        'network' => (int)$network_code,
        'phone' => $phone,
        'plan_type' => 'VTU',
        'bypass' => false,
        'amount' => (int)$amount,
        'request-id' => 'Airtime_' . uniqid()
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://subvas.com/api/topup');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token ",
        "Content-Type: application/json"
    ]);

    $subvas_response = curl_exec($ch);
    curl_close($ch);

    $subvas_data = json_decode($subvas_response, true);

    // ✅ Step 4: Set delivery status
    $delivery_status = (isset($subvas_data['status']) && $subvas_data['status'] == 'success') ? 'success' : 'failed';

    // ✅ Step 5: Record transaction in DB
    $stmt = $conn->prepare("INSERT INTO transactions 
        (name, email, phone, service, reference, network, amount, profit, status)
        VALUES (?, ?, ?, 'airtime', ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdss", $name, $email, $phone, $ref, $network_name, $amount_paid, $profit, $delivery_status);
    $stmt->execute();

    // ✅ Step 6: Display receipt-style result
    if ($delivery_status === 'success') {
        echo "
        <div style='
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #f0fff4;
            border: 2px solid #38a169;
            color: #22543d;
            font-family: Arial, sans-serif;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            line-height: 1.6;
        '>
            <h2 style='text-align:center;'>✅ Airtime Receipt</h2>
            <hr>
            <p><strong>Customer Name:</strong> {$name}</p>
            <p><strong>Phone Number:</strong> {$phone}</p>
            <p><strong>Network:</strong> {$network_name}</p>
            <p><strong>Amount Recharged:</strong> ₦{$amount_paid}</p>
            <p><strong>Reference:</strong> {$ref}</p>
            <p><strong>Status:</strong> <span style='color: green;'>Delivered</span></p>
            <hr>
            <p style='text-align:center;'>Thanks for using <strong>RechargeHub</strong> 💚</p>
        </div>
        ";
    } else {
        echo "
        <div style='
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff5f5;
            border: 2px solid #e53e3e;
            color: #c53030;
            font-family: Arial, sans-serif;
            border-radius: 12px;
            text-align: center;
        '>
            <h2>⚠️ Airtime Delivery Failed</h2>
            <p>Payment was successful but airtime delivery failed.</p>
            <p>Transaction Reference: <strong>{$ref}</strong></p>
            <p>Please contact support if this persists.</p>
        </div>
        ";
    }
} else {
    echo "
    <div style='
        max-width: 500px;
        margin: 50px auto;
        padding: 30px;
        background: #fff5f5;
        border: 2px solid #e53e3e;
        color: #c53030;
        font-family: Arial, sans-serif;
        border-radius: 12px;
        text-align: center;
    '>
        <h2>❌ Payment Verification Failed</h2>
        <p>Transaction could not be verified via Paystack.</p>
        <p>Please do not refresh this page manually.</p>
    </div>
    ";
}
?>
