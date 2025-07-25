<?php
include("../connection.php");
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Electricity Payment</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      font-family: Arial;
      background: #111;
      color: #fff;
      padding: 20px;
    }
    .container {
      max-width: 500px;
      background: #222;
      margin: auto;
      padding: 20px;
      border-radius: 10px;
    }
    label {
      display: block;
      margin-bottom: 5px;
    }
    input, select, button {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      border: none;
    }
    button {
      background: gold;
      color: #000;
      font-weight: bold;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Buy Electricity Token</h2>
    <form onsubmit="submitForm(event)">
     <label for="provider">Select Provider:</label>
<select name="provider" required>
  <option value="">-- Choose Provider --</option>
  <?php
  $sql = $conn->query("SELECT provider_code, provider_name FROM electricity_providers ORDER BY provider_name ASC");
  while ($row = $sql->fetch_assoc()):
  ?>
    <option value="<?= htmlspecialchars($row['provider_code']) ?>">
      <?= htmlspecialchars($row['provider_name']) ?>
    </option>
  <?php endwhile; ?>
</select>


      <label>Meter Number</label>
      <input type="text" name="meter_number" required maxlength="13">

      <label>Amount (₦)</label>
      <input type="number" name="amount" required min="100">

      <label>Meter Type</label>
      <select name="type" required>
        <option value="prepaid">Prepaid</option>
        <option value="postpaid">Postpaid</option>
      </select>

      <button type="submit">Pay</button>
    </form>
  </div>

  <script>
    function submitForm(e) {
      e.preventDefault();
      const form = e.target;
      fetch("electricity_verify.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          user_id: <?= $user_id ?>,
          provider: form.provider.value,
          meter_number: form.meter_number.value,
          amount: form.amount.value,
          type: form.type.value
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === "success") {
          Swal.fire("Success!", `Token: ${data.token}<br>Units: ${data.units}<br>Ref: ${data.ref}`, "success");
          form.reset();
        } else {
          Swal.fire("Error", data.message, "error");
        }
      })
      .catch(() => Swal.fire("Error", "Something went wrong", "error"));
    }
  </script>
</body>
</html>
