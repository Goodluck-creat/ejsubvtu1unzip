<?php
include("../connection.php");
session_start();

if (!isset($_GET['ref'])) {
    die("No receipt reference provided.");
}

$request_id = $_GET['ref'];

$stmt = $conn->prepare("SELECT c.*, u.name, u.email 
                        FROM cable_transactions c 
                        JOIN vtu_users u ON u.id = c.user_id 
                        WHERE c.request_id = ?");
$stmt->bind_param("s", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Transaction not found.");
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Receipt - Cable Subscription</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      padding: 30px;
    }

    .receipt {
      max-width: 500px;
      margin: auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }

    .receipt h2 {
      text-align: center;
      color: #00A86B;
    }

    .receipt p {
      margin: 8px 0;
    }

    .receipt .label {
      font-weight: bold;
    }

    .btn {
      margin-top: 20px;
      background: #00A86B;
      color: white;
      padding: 10px 20px;
      border: none;
      cursor: pointer;
      border-radius: 5px;
    }

    .btn:hover {
      background: #007a56;
    }
  </style>
</head>
<body>

<div class="receipt">
  <h2>Transaction Receipt</h2>

  <p><span class="label">Name:</span> <?= htmlspecialchars($data['name']) ?></p>
  <p><span class="label">Email:</span> <?= htmlspecialchars($data['email']) ?></p>
  <p><span class="label">Cable:</span> <?= $data['cabl_name'] ?></p>
  <p><span class="label">Plan:</span> <?= $data['plan_name'] ?></p>
  <p><span class="label">IUC Number:</span> <?= $data['iuc'] ?></p>
  <p><span class="label">Amount Paid:</span> ₦<?= number_format($data['amount']) ?></p>
  <p><span class="label">Status:</span> <?= ucfirst($data['status']) ?></p>
  <p><span class="label">Message:</span> <?= $data['message'] ?></p>
  <p><span class="label">Date:</span> <?= date("M d, Y h:i A", strtotime($data['timestamp'])) ?></p>
  <p><span class="label">Request ID:</span> <?= $data['request_id'] ?></p>

  <button class="btn" onclick="window.print()">Print Receipt</button>
</div>

</body>
</html>
