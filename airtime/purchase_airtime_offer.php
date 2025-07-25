<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
include("../connection.php");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Airtime Purchase</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>
        Swal.fire({ icon: 'error', title: 'Unauthorized', text: 'You must be logged in.' });
        setTimeout(() => window.location.href='../login.php', 3000);
    </script>";
    exit;
}

$user_id  = $_SESSION['user_id'];
$offer_id = intval($_POST['offer_id'] ?? 0);
$phone    = trim($_POST['phone'] ?? '');

if (!preg_match("/^\d{10,14}$/", $phone)) {
    echo "<script>
        Swal.fire({ icon: 'error', title: 'Invalid Phone', text: 'Enter a valid phone number.' });
        setTimeout(() => window.location.href='buy_airtime.php', 3000);
    </script>";
    exit;
}

// Fetch offer
$stmt = $conn->prepare("SELECT * FROM airtime_offers WHERE id = ?");
$stmt->bind_param("i", $offer_id);
$stmt->execute();
$offer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$offer) {
    echo "<script>
        Swal.fire({ icon: 'error', title: 'Offer Not Found', text: 'Selected airtime offer does not exist.' });
        setTimeout(() => window.location.href='buy_airtime.php', 3000);
    </script>";
    exit;
}

$amount        = intval($offer['amount']);
$price         = floatval($offer['discount_price']);
$network_code  = intval($offer['network_code']);
$network_name  = $offer['network_name'];
$ref           = "airtime_" . uniqid();

// Check wallet balance
$stmt = $conn->prepare("SELECT wallet_balance FROM vtu_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || floatval($user['wallet_balance']) < $price) {
    echo "<script>
        Swal.fire({ icon: 'error', title: 'Insufficient Balance', text: 'Your wallet does not have enough funds.' });
        setTimeout(() => window.location.href='buy_airtime.php', 3000);
    </script>";
    exit;
}

// Subvas API
$api_token = "";
$payload = [
    "network"    => $network_code,
    "phone"      => $phone,
    "plan_type"  => "VTU",
    "bypass"     => false,
    "amount"     => $amount,
    "request-id" => $ref
];

$ch = curl_init("https://subvas.com/api/topup");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Token $api_token",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

// Log raw response
file_put_contents("subvas_log.txt", date("Y-m-d H:i:s") . " | {$ref} | {$phone} | {$response}\n", FILE_APPEND);

$result = json_decode($response, true);
$status = strtolower($result['status'] ?? '');
$message = $result['message'] ?? 'Unknown error';
$subvas_balance = floatval($result['newbal'] ?? 0);

// 🔔 Low wallet balance alert
if ($subvas_balance < 1000) {
    $admin_email = "nwimochioma15@gmail.com"; // ✅ Replace with your real email
    $subject = "⚠️ Low Subvas Wallet Alert";
    $body = "Hello Admin,\n\nYour Subvas wallet balance is low: ₦" . number_format($subvas_balance, 2) . "\n\nPlease top up to avoid failed airtime transactions.\n\n- Your VTU Platform";
    $headers = "From: noreply@yourdomain.com";
    mail($admin_email, $subject, $body, $headers);
}

// Log parsed status
file_put_contents("subvas_log.txt", "Parsed Status: $status\n", FILE_APPEND);

// ✅ Accept both "success" and "successful"
if (in_array($status, ['success', 'successful'])) {
    try {
        $conn->begin_transaction();

        // Deduct wallet
        $stmt = $conn->prepare("UPDATE vtu_users SET wallet_balance = wallet_balance - ? WHERE id = ?");
        $stmt->bind_param("di", $price, $user_id);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            throw new Exception("Wallet deduction failed.");
        }
        $stmt->close();

        // Save purchase
        $stmt = $conn->prepare("INSERT INTO airtime_purchases 
            (user_id, network_name, network_code, phone, amount, discount_price, reference, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'successful')");
        $stmt->bind_param("isisdds", $user_id, $network_name, $network_code, $phone, $amount, $price, $ref);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Airtime Sent!',
                html: '₦{$amount} sent to <b>{$phone}</b> via <b>{$network_name}</b><br>Ref: <code>{$ref}</code>',
                timer: 5000,
                showConfirmButton: false
            });
            setTimeout(() => window.location.href='buy_airtime.php', 5000);
        </script>";

    } catch (Exception $e) {
        $conn->rollback();

        file_put_contents("subvas_log.txt", date("Y-m-d H:i:s") . " | DB ERROR | Ref: $ref | ERROR: " . $e->getMessage() . "\n", FILE_APPEND);

        echo "<script>
            Swal.fire({ icon: 'error', title: 'Transaction Failed', text: 'Something went wrong. Please try again.' });
            setTimeout(() => window.location.href='buy_airtime.php', 5000);
        </script>";
    }

} else {
    // ❌ Save failed transaction in DB
    $stmt = $conn->prepare("INSERT INTO failed_airtime_logs 
      (user_id, network_name, network_code, phone, amount, discount_price, reference, status, reason) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $status_text = 'api_failed';
    $stmt->bind_param("isissdsss", $user_id, $network_name, $network_code, $phone, $amount, $price, $ref, $status_text, $message);
    $stmt->execute();
    $stmt->close();

    // Log to file
    file_put_contents("subvas_log.txt", date("Y-m-d H:i:s") . " | API FAIL | Ref: $ref | Phone: $phone | Message: $message\n", FILE_APPEND);

    echo "<script>
        Swal.fire({ 
            icon: 'error', 
            title: 'Request Failed', 
            text: 'Something went wrong while processing your airtime. Please try again later.' 
        });
        setTimeout(() => window.location.href='buy_airtime.php', 5000);
    </script>";
}
?>

</body>
</html>
