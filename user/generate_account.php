<?php
session_start();
include("connection.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nin     = trim($_POST['nin'] ?? '');
$phone   = trim($_POST['phone'] ?? '');

// Validation
if (strlen($nin) !== 11 || !ctype_digit($nin)) {
    die("❌ Invalid NIN format.");
}
if (!preg_match('/^\d{10,14}$/', $phone)) {
    die("❌ Invalid phone number.");
}

// 🚫 Check if NIN is already used
$checkNin = $conn->prepare("SELECT id FROM vtu_users WHERE nin = ?");
$checkNin->bind_param("s", $nin);
$checkNin->execute();
$checkNin->store_result();

if ($checkNin->num_rows > 0) {
    die("❌ This NIN has already been used. Only one account is allowed per NIN.");
}

// ✅ Get user info
$stmt = $conn->prepare("SELECT fullname, email FROM vtu_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    die("❌ User not found.");
}

$user = $result->fetch_assoc();
$fullname = $user['fullname'];
$email    = $user['email'];

// Split fullname
$nameParts = explode(" ", $fullname, 2);
$first_name = $nameParts[0];
$last_name  = $nameParts[1] ?? $first_name;

// Flutterwave API setup
$secretKey = "FLWSECK-233b3ffd10f76922bc56b1522f8f243a-19791dbf855vt-X"; // ✅ Replace with your real secret key

$data = [
    "email"         => $email,
    "is_permanent"  => true,
    "bvn"           => $nin,
    "tx_ref"        => uniqid("vtu-"),
    "phonenumber"   => $phone,
    "firstname"     => $first_name,
    "lastname"      => $last_name
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.flutterwave.com/v3/virtual-account-numbers",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => "POST",
    CURLOPT_POSTFIELDS     => json_encode($data),
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($curl);
if (curl_errno($curl)) {
    echo "❌ cURL Error: " . curl_error($curl);
    curl_close($curl);
    exit;
}

curl_close($curl);
$result = json_decode($response, true);

// Log API response
file_put_contents("account_log.txt", print_r($result, true) . PHP_EOL, FILE_APPEND);

// ✅ Handle successful account creation
if (isset($result['status']) && $result['status'] === 'success') {
    $account = $result['data'];
    $acct_no = $account['account_number'] ?? '';
    $bank    = $account['bank_name'] ?? '';

    if ($acct_no && $bank) {
        // ✅ Save account + nin to DB
        $update = $conn->prepare("UPDATE vtu_users SET flutterwave_account_number = ?, flutterwave_bank_name = ?, nin = ? WHERE id = ?");
        $update->bind_param("sssi", $acct_no, $bank, $nin, $user_id);
        $update->execute();

        $_SESSION['success'] = "✅ Account generated successfully.";
        header("Location: dashboard.php");
        exit;
    } else {
        die("❌ Account created but missing info.");
    }

} else {
    $error = $result['message'] ?? 'Unknown error from Flutterwave.';
    die("❌ Failed: " . htmlspecialchars($error));
}
