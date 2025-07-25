<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");
include("../connection.php");

// Capture raw request
$raw = file_get_contents("php://input");
file_put_contents("elec_debug.json", "[" . date("Y-m-d H:i:s") . "] RAW: $raw\n", FILE_APPEND);

$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

// Extract input
$user_id = $data['user_id'] ?? null;
$provider_code = $data['provider'] ?? null;
$meter = trim($data['meter_number'] ?? '');
$amount = $data['amount'] ?? null;
$type = strtolower($data['type'] ?? '');
$ref = uniqid("elec_");

if (!$user_id || !$provider_code || !$meter || !$amount || !$type) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// Confirm provider exists
$stmt = $conn->prepare("SELECT provider_code, provider_name FROM electricity_providers WHERE provider_code = ?");
$stmt->bind_param("s", $provider_code);
$stmt->execute();
$providerResult = $stmt->get_result();
if ($providerResult->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid provider"]);
    exit;
}
$providerData = $providerResult->fetch_assoc();
$provider_name = $providerData['provider_name'];

// Fetch user wallet & email
$stmt = $conn->prepare("SELECT wallet_balance, email FROM vtu_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}
if ($user['wallet_balance'] < $amount) {
    echo json_encode(["status" => "error", "message" => "Insufficient wallet balance"]);
    exit;
}

// Deduct wallet
$conn->query("UPDATE vtu_users SET wallet_balance = wallet_balance - $amount WHERE id = $user_id");

// === TEETECH FUNCTION ===
function useTeetech($conn, $provider_name, $meter, $type, $amount, $ref) {
    // ✅ Get Teetech plan_id using provider_name
    $stmt = $conn->prepare("SELECT plan_id FROM teetechdisco WHERE disco_name = ?");
    $stmt->bind_param("s", $provider_name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        return ["success" => false, "message" => "Teetech DISCO ID not found for '$provider_name'"];
    }
    $row = $res->fetch_assoc();
    $teetechDiscoId = $row['plan_id'];

    // 🔍 Meter verification
    $verifyUrl = "https://teetechglobal.com/api/bill/bill-validation?meter_number={$meter}&disco={$teetechDiscoId}&meter_type={$type}";
    $headers = [
        "Authorization: Token "
    ];

    $verifyCurl = curl_init();
    curl_setopt_array($verifyCurl, [
        CURLOPT_URL => $verifyUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $verifyResponse = curl_exec($verifyCurl);
    curl_close($verifyCurl);
    $verifyResult = json_decode($verifyResponse, true);

    if (strtolower($verifyResult['status']) !== 'success') {
        return ["success" => false, "message" => "Meter verification failed: " . ($verifyResult['message'] ?? 'Unknown error')];
    }

    // Proceed to purchase
    $headers[] = "Content-Type: application/json";

    $payload = [
        "disco" => $teetechDiscoId,
        "meter_type" => $type,
        "meter_number" => "$meter",
        "amount" => $amount,
        "bypass" => false,
        "request-id" => $ref
    ];

    $ch = curl_init("https://teetechglobal.com/api/bill");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($response, true);

    if (strtolower($res['status']) === 'success') {
        return [
            "success" => true,
            "token" => $res['token'] ?? 'N/A',
            "units" => $res['amount'] ?? 'N/A',
            "provider" => 'TeetechGlobal'
        ];
    } else {
        return ["success" => false, "message" => $res['message'] ?? 'Teetech failed'];
    }
}

// === ONLY TEETECH ===
$result = useTeetech($conn, $provider_name, $meter, $type, $amount, $ref);

if (!$result['success']) {
    // Refund wallet
    $conn->query("UPDATE vtu_users SET wallet_balance = wallet_balance + $amount WHERE id = $user_id");
    echo json_encode(["status" => "error", "message" => $result['message']]);
    exit;
}

// === SUCCESS, SAVE TRANSACTION ===
$token = $result['token'];
$units = $result['units'];
$providerUsed = $result['provider'];

$stmt = $conn->prepare("INSERT INTO electricity_transactions (user_id, provider, meter_number, amount, token, ref, units, status, date) VALUES (?, ?, ?, ?, ?, ?, ?, 'success', NOW())");
$stmt->bind_param("issdsss", $user_id, $provider_code, $meter, $amount, $token, $ref, $units);
$stmt->execute();

// Email user
$email = $user['email'];
$subject = "Your Electricity Token [$ref]";
$message = "Token: $token\nUnits: $units\nRef: $ref\nAmount: ₦$amount\nProvider: $providerUsed";
mail($email, $subject, $message);

// Respond
echo json_encode([
    "status" => "success",
    "token" => $token,
    "units" => $units,
    "ref" => $ref,
    "provider" => $providerUsed
]);
?>
