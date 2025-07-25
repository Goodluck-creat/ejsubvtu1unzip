<?php
session_start();
// add_airtime_offer.php
include("../connection.php");

// 🔒 Allow only admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $network_name   = isset($_POST['network_name']) ? trim($_POST['network_name']) : '';
  $network_code   = isset($_POST['network_code']) ? intval($_POST['network_code']) : 0;
  $amount         = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
  $discount_price = isset($_POST['discount_price']) ? floatval($_POST['discount_price']) : 0;

  if ($network_name && $network_code && $amount > 0 && $discount_price > 0) {
    $stmt = $conn->prepare("INSERT INTO airtime_offers (network_name, network_code, amount, discount_price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sidd", $network_name, $network_code, $amount, $discount_price);
    if ($stmt->execute()) {
      echo "✅ Airtime offer added successfully.";
    } else {
      echo "❌ Failed to insert offer.";
    }
  } else {
    echo "❌ Invalid input.";
  }
}
?>


<!DOCTYPE html>
<html>
<head>
  <title>Add Airtime Offer</title>
  <style>
    body { font-family: Arial; background: #f5f5f5; padding: 40px; }
    form {
      background: white;
      max-width: 400px;
      margin: auto;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px #ccc;
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      background: green;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 5px;
      font-weight: bold;
    }
  </style>
</head>
<body>

<h2 style="text-align:center;">Add Airtime Offer</h2>

<form method="POST">
  <label for="network_name">Network Name</label>
  <select name="network_name" required>
    <option value="">-- Select Network --</option>
    <option value="MTN">MTN</option>
    <option value="Airtel">Airtel</option>
    <option value="Glo">Glo</option>
    <option value="9mobile">9mobile</option>
  </select>

  <label for="network_code">Network Code</label>
  <select name="network_code" required>
    <option value="">-- Select Code --</option>
    <option value="1">1 - MTN</option>
    <option value="2">2 - Airtel</option>
    <option value="3">3 - Glo</option>
    <option value="6">6 - 9mobile</option>
  </select>

  <label for="amount">Airtime Amount</label>
  <input type="number" name="amount" required min="50" step="0.01">

  <label for="discount_price">Discounted Price</label>
  <input type="number" name="discount_price" required min="50" step="0.01">

  <button type="submit">Add Offer</button>
</form>

</body>
</html>
