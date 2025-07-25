<?php
include("../connection.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $disco = $_POST['disco'];
    $meter_type = $_POST['meter_type'];
    $meter_number = $_POST['meter_number'];
    $amount = (float)$_POST['amount'];
    $request_id = 'Bill_' . time();

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

    // ✅ Step 1: Fetch wallet balance
    $stmt = $conn->prepare("SELECT wallet_balance FROM vtu_users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($wallet_balance);
    $stmt->fetch();
    $stmt->close();

    if ($wallet_balance < $amount) {
        echo "<script>
            window.onload = function() {
                Swal.fire('❌ Insufficient Balance', 'You have ₦$wallet_balance but need ₦$amount', 'warning')
                .then(() => window.location = 'electricity_form.php');
            };
        </script>";
        exit;
    }

    // ✅ Step 2: Validate meter
    $validate_url = "https://teetechglobal.com/api/bill/bill-validation?meter_number=$meter_number&disco=$disco&meter_type=$meter_type";
    $validate_ch = curl_init();
    curl_setopt($validate_ch, CURLOPT_URL, $validate_url);
    curl_setopt($validate_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($validate_ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token s472AeibdqBckxIblB6AhnCorAa5ymCB21Ct5CJ90p3AvBDw3G32x8BAxx6C1690281110"
    ]);
    $validate_response = curl_exec($validate_ch);
    curl_close($validate_ch);
    $validate_result = json_decode($validate_response, true);

    if (!$validate_result || $validate_result['status'] !== 'success') {
        $msg = $validate_result['message'] ?? 'Invalid meter or disco';
        echo "<script>
            window.onload = function() {
                Swal.fire('❌ Validation Failed', '$msg', 'error')
                .then(() => window.location = 'electricity_form.php');
            };
        </script>";
        exit;
    }

    $verified_name = $validate_result['name'] ?? 'N/A';

    // ✅ Step 3: Call Teetech API to pay
    $payload = array(
        'disco' => (int)$disco,
        'meter_type' => $meter_type,
        'meter_number' => strval($meter_number),
        'amount' => $amount,
        'bypass' => false,
        'request-id' => $request_id
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://teetechglobal.com/api/bill');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token s472AeibdqBckxIblB6AhnCorAa5ymCB21Ct5CJ90p3AvBDw3G32x8BAxx6C1690281110",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    if ($result && $result['status'] === 'success') {
        // ✅ Step 4: Deduct user wallet after success
        $charges = $result['charges'] ?? 0;
        $total = $amount + $charges;
        $wallet_before = $wallet_balance;
        $wallet_after = $wallet_before - $total;

        $update_wallet = $conn->prepare("UPDATE vtu_users SET wallet_balance = ? WHERE id = ?");
        $update_wallet->bind_param("di", $wallet_after, $user_id);
        $update_wallet->execute();
        $update_wallet->close();

$profit = $amount_result - $wallet_vending; // wallet_vending is actual cost from API

        // ✅ Step 5: Prepare bind variables first
        $meter_type_result = $result['meter_type'];
        $meter_number_result = $result['meter_number'];
        $amount_result = $result['amount'];
        $charges_result = $charges;
        $request_id_result = $result['request-id'];
        $oldbal = $wallet_before;
        $newbal = $wallet_after;
        $status_result = $result['status'];
        $message_result = $result['message'];
        $token = isset($result['token']) ? $result['token'] : '';
        $wallet_vending = isset($result['wallet_vending']) ? $result['wallet_vending'] : '';

        // ✅ Step 6: Save transaction
        $stmt = $conn->prepare("INSERT INTO vtu_electricity_payments 
(user_id, disco, meter_type, meter_number, amount, charges, request_id, old_balance, new_balance, status, message, token, wallet_vending, verified_name, profit) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("iissddssssssssd",
    $user_id,
    $disco,
    $meter_type_result,
    $meter_number_result,
    $amount_result,
    $charges_result,
    $request_id_result,
    $oldbal,
    $newbal,
    $status_result,
    $message_result,
    $token,
    $wallet_vending,
    $verified_name,
    $profit
);


        if ($stmt->execute()) {
            echo "<script>
                window.onload = function() {
                    Swal.fire('✅ Payment Successful', 'Token: $token', 'success')
                    .then(() => window.location = 'receipt.php?ref=$request_id_result');
                };
            </script>";
        } else {
            echo "<script>
                window.onload = function() {
                    Swal.fire('❌ Database Error', '" . $stmt->error . "', 'error');
                };
            </script>";
        }

    } else {
        $msg = $result['message'] ?? "Unknown API error";
        echo "<script>
            window.onload = function() {
                Swal.fire('❌ Payment Failed', '$msg', 'error')
                .then(() => window.location = 'electricity_form.php');
            };
        </script>";
    }
}
?>
