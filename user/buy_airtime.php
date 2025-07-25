<?php include("connection.php"); ?>
<!DOCTYPE html>
<html>
<head>
  <title>Buy Airtime - Smart VTU</title>
  <script src="https://js.paystack.co/v1/inline.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }
    .card {
      display: inline-block;
      background: #38a169;
      color: white;
      padding: 15px 25px;
      margin: 10px;
      border-radius: 10px;
      cursor: pointer;
      transition: 0.2s;
    }
    .card:hover {
      background: #2f855a;
    }
    #cards {
      margin-top: 20px;
    }
    input, button {
      padding: 10px;
      margin-top: 10px;
      width: 100%;
      max-width: 400px;
    }
  </style>
</head>
<body>

  <h2>📲 Guest Airtime Purchase</h2>

  <input type="text" id="name" placeholder="Your Name" required><br>
  <input type="email" id="email" placeholder="Your Email" required><br>
  <input type="tel" id="phone" placeholder="Enter phone number" onkeyup="detectNetwork()" required><br>
  <input type="hidden" id="network" name="network">
  <div id="networkDisplay"></div>

  <div id="cards"></div>

  <script>
    const prefixes = {
      MTN: ["0803", "0806", "0703", "0706", "0813", "0810", "0814", "0816", "0903", "0906", "0913", "0916"],
      AIRTEL: ["0802", "0808", "0708", "0812", "0902", "0907", "0901", "0912"],
      GLO: ["0805", "0807", "0705", "0815", "0811", "0905", "0915"],
      "9MOBILE": ["0809", "0817", "0818", "0909", "0908"]
    };

    const networkCodes = {
      MTN: 1,
      AIRTEL: 2,
      GLO: 3,
      "9MOBILE": 4
    };

    function detectNetwork() {
      const phone = document.getElementById("phone").value.trim();
      const prefix = phone.substring(0, 4);
      let detected = "";

      for (let net in prefixes) {
        if (prefixes[net].includes(prefix)) {
          detected = net;
          break;
        }
      }

      if (detected) {
        document.getElementById("network").value = networkCodes[detected];
        document.getElementById("networkDisplay").innerHTML = `<strong>Detected Network:</strong> ${detected}`;
        showCards(detected);
      } else {
        document.getElementById("networkDisplay").innerHTML = `<span style="color:red">❌ Invalid network</span>`;
        document.getElementById("cards").innerHTML = '';
      }
    }

    function showCards(network) {
      const amounts = [100, 200, 500, 1000];
      let html = `<p><strong>Select Airtime Amount for ${network}:</strong></p>`;

      amounts.forEach(amount => {
        html += `<div class="card" onclick="payWithPaystack(${amount})">₦${amount}</div>`;
      });

      document.getElementById("cards").innerHTML = html;
    }

    function payWithPaystack(amount) {
      const name = document.getElementById("name").value;
      const email = document.getElementById("email").value;
      const phone = document.getElementById("phone").value;
      const network = document.getElementById("network").value;

      if (!name || !email || !phone || !network) {
        alert("Please fill in all fields");
        return;
      }

      const ref = 'VTU_' + Math.floor(Math.random() * 1000000000 + 1);

      let handler = PaystackPop.setup({
        key: 'pk_live_fcaab659b3403194294d38587ef367798ab23b39',
        email: email,
        amount: amount * 100,
        currency: "NGN",
        ref: ref,
        callback: function(response) {
          window.location.href = `verify_payment.php?ref=${response.reference}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&phone=${encodeURIComponent(phone)}&amount=${amount}&network=${network}`;
        },
        onClose: function() {
          alert("Transaction was cancelled.");
        }
      });

      handler.openIframe();
    }
  </script>

</body>
</html>
