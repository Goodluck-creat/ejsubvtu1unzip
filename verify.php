<?php
include("connection.php");

$token = $_GET['token'] ?? '';
if (!$token) {
    die("Invalid or missing verification token.");
}

// ✅ Find user with verification token
$stmt = $conn->prepare("SELECT id, fullname, email, is_verified, flutterwave_account_number, flutterwave_bank_name FROM vtu_users WHERE verify_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Invalid verification link.");
}

$user = $res->fetch_assoc();

if ((int)$user['is_verified'] === 1) {
    echo "<p>Your account is already verified.</p>";
    echo "<p>Virtual Account: <strong>" . htmlspecialchars($user['flutterwave_bank_name']) . " — " . htmlspecialchars($user['flutterwave_account_number']) . "</strong></p>";
    exit;
}

$uid   = $user['id'];
$name  = $user['fullname'];
$email = $user['email'];

// ✅ Generate tx_ref and store it in payment_refs
$tx_ref = uniqid(time() . '-RND_');
$expected_amount = 100;

$stmt = $conn->prepare("INSERT INTO payment_refs (user_id, tx_ref, expected_amount) VALUES (?, ?, ?)");
$stmt->bind_param("isd", $uid, $tx_ref, $expected_amount);
$stmt->execute();

// 🔐 Your Flutterwave secret key
$secretKey = '';

// ✅ Prepare virtual account creation payload
$data = [
    'email'        => $email,
    'firstname'    => $name,
    'is_permanent' => false,
    'amount'       => $expected_amount,
    'tx_ref'       => $tx_ref
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.flutterwave.com/v3/virtual-account-numbers",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo "<p>cURL Error: " . curl_error($curl) . "</p>";
    curl_close($curl);
    exit;
}

curl_close($curl);
$result = json_decode($response, true);
file_put_contents("va_create_log.txt", print_r($result, true) . PHP_EOL, FILE_APPEND);

// ✅ Check result and update user
if (isset($result['status']) && $result['status'] === 'success') {
    $va = $result['data'];
    $acct = $va['account_number'] ?? '';
    $bank = $va['bank_name'] ?? '';

    if (!$acct || !$bank) {
        echo "<p>⚠️ Missing required fields. Please contact support.</p>";
        exit;
    }

    $upd = $conn->prepare("UPDATE vtu_users SET is_verified = 1, flutterwave_account_number = ?, flutterwave_bank_name = ?, verify_token = NULL WHERE id = ?");
    $upd->bind_param("ssi", $acct, $bank, $uid);
    $upd->execute();

    echo "<p>✅ Thank you, " . htmlspecialchars($name) . "!</p>";
    echo "<p>Your account has been verified and a virtual account has been created:</p>";
    echo "<p><strong>" . htmlspecialchars($bank) . " — " . htmlspecialchars($acct) . "</strong></p>";
} else {
    echo "<p>✅ Verification succeeded, but virtual account creation failed.</p>";
    echo "<p>❌ Reason: " . htmlspecialchars($result['message'] ?? 'Unknown error') . "</p>";
}
?>
