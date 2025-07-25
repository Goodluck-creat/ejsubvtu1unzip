<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

include("../connection.php");
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cable = $_POST['cable_id'] ?? '';
    $iuc = $_POST['iuc'] ?? '';
    $plan_id = $_POST['plan_id'] ?? '';
    $amount = $_POST['amount'] ?? 0;

    if (!$user_id || !$cable || !$iuc || !$plan_id || !$amount) {
        echo json_encode(["status" => "failed", "message" => "Missing required fields"]);
        exit;
    }

    // Fetch plan name
    $stmt = $conn->prepare("SELECT plan_name FROM Tcable_plans WHERE id = ?");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $plan = $stmt->get_result()->fetch_assoc();
    $plan_name = $plan['plan_name'] ?? "Unknown";

    $request_id = "Cable_" . uniqid();
    $headers = [
        "Authorization: Token ",
        "Content-Type: application/json"
    ];

    // Step 1: Check user balance
    $check = $conn->prepare("SELECT wallet_balance FROM vtu_users WHERE id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $wallet = $check->get_result()->fetch_assoc();

    if (!$wallet || $wallet['wallet_balance'] < $amount) {
        echo json_encode(["status" => "failed", "message" => "Insufficient wallet balance"]);
        exit;
    }

    // Step 2: IUC Verification
    $verify_url = "https://teetechglobal.com/api/cable/cable-validation?iuc=$iuc&cable=$cable";
    $ch = curl_init($verify_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $verify_response = curl_exec($ch);
    curl_close($ch);

    $verify = json_decode($verify_response, true);

    if (!$verify || $verify['status'] !== "success") {
        echo json_encode(["status" => "failed", "message" => "IUC Verification failed"]);
        exit;
    }

    // Step 3: Deduct wallet
    $new_balance = $wallet['wallet_balance'] - $amount;
    $deduct = $conn->prepare("UPDATE vtu_users SET wallet_balance = ? WHERE id = ?");
    $deduct->bind_param("di", $new_balance, $user_id);
    $deduct->execute();

    // Step 4: Make API Payment
    $payload = [
        "cable" => (int)$cable,
        "iuc" => $iuc,
        "cable_plan" => (int)$plan_id,
        "bypass" => false,
        "request-id" => $request_id
    ];

    $ch = curl_init("https://teetechglobal.com/api/cable");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($res, true);

    // Step 5: Handle API Failure
    if (!$result || $result['status'] !== "success") {
        // Refund
        $refund = $conn->prepare("UPDATE vtu_users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $refund->bind_param("di", $amount, $user_id);
        $refund->execute();

        $api_message = $result['message'] ?? 'Unknown API error';
        $display_message = "Cable subscription failed. Please try again shortly.";

        // Alert admin if Teetech wallet is low
        if (str_contains(strtolower($api_message), "insufficient account")) {
            $to = "nwimochioma15@gmail.com"; // Your real email
            $subject = "Teetech Wallet Low Alert";
            $body = "⚠️ Your Teetech VTU wallet is too low to process this cable request.\n\n".
                    "User ID: $user_id\nIUC: $iuc\nPlan: $plan_name\nAmount: ₦$amount\n".
                    "Request ID: $request_id\n\nTeetech Response: $api_message";
            $headers = "From: noreply@EJSUB.com";
            @mail($to, $subject, $body, $headers);
        }

        echo json_encode([
            "status" => "failed",
            "message" => $display_message
        ]);
        exit;
    }

    // Step 6: Log transaction
    $stmt = $conn->prepare("INSERT INTO cable_transactions 
        (user_id, cable, iuc, cable_plan, amount, status, message, request_id, cabl_name, plan_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisiisssss", 
        $user_id, $cable, $iuc, $plan_id, $amount,
        $result['status'], $result['message'], $request_id,
        $result['cabl_name'], $result['plan_name']);
    $stmt->execute();

    // Step 7: Return success with redirect
    echo json_encode([
        "status" => "success",
        "message" => $result['message'],
        "redirect" => "receipt.php?ref=$request_id"
    ]);
}
