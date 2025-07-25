<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cable Subscription</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --primary: #00A86B;
      --secondary: #1E1E2F;
      --accent: #FFD700;
    }

    * {
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }

    body {
      background: #f4f4f4;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 500px;
      margin: auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: var(--primary);
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
      color: var(--secondary);
    }

    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    button {
      background: var(--primary);
      color: white;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 5px;
      font-size: 16px;
      margin-top: 20px;
      cursor: pointer;
    }

    button:hover {
      background: #008556;
    }

    #iuc_name {
      font-size: 14px;
      color: green;
      margin-top: 5px;
    }

    /* Preloader spinner */
    .preloader {
      display: none;
      position: fixed;
      z-index: 9999;
      background: rgba(255,255,255,0.8);
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      justify-content: center;
      align-items: center;
    }

    .preloader .loader {
      border: 6px solid #f3f3f3;
      border-top: 6px solid var(--primary);
      border-radius: 50%;
      width: 60px;
      height: 60px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<div class="preloader" id="preloader">
  <div class="loader"></div>
</div>

<div class="container">
  <h2>Cable Subscription</h2>

  <form id="cableForm">
    <label>Cable Type</label>
    <select name="cable_id" id="cable_id" required>
      <option value="">Select Cable</option>
      <option value="1">GOTV</option>
      <option value="2">DSTV</option>
      <option value="3">STARTIME</option>
    </select>

    <label for="iuc">IUC Number</label>
    <input type="text" name="iuc" id="iuc" required>
    <div id="iuc_name"></div>

    <label>Cable Plan</label>
    <select name="plan_id" id="plan_id" required></select>

    <input type="hidden" name="amount" id="amount">

    <button type="submit">Subscribe</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const preloader = document.getElementById("preloader");

function showLoader() {
  preloader.style.display = "flex";
}
function hideLoader() {
  preloader.style.display = "none";
}

let plans = [];

document.getElementById("cable_id").addEventListener("change", function() {
  const cableId = this.value;
  if (!cableId) return;

  showLoader();
  fetch(`fetch_plans.php?cable_id=${cableId}`)
    .then(res => res.json())
    .then(data => {
      hideLoader();
      plans = data;
      let planSelect = document.getElementById("plan_id");
      planSelect.innerHTML = "";
      data.forEach(plan => {
        let option = document.createElement("option");
        option.value = plan.id;
        option.textContent = `${plan.plan_name} - ₦${plan.final_amount}`;
        option.dataset.amount = plan.final_amount;
        planSelect.appendChild(option);
      });
    })
    .catch(() => {
      hideLoader();
      Swal.fire("Error", "Failed to fetch plans", "error");
    });
});

document.getElementById("plan_id").addEventListener("change", function() {
  let selected = this.options[this.selectedIndex];
  document.getElementById("amount").value = selected.dataset.amount;
});

document.getElementById("iuc").addEventListener("blur", function() {
  const iuc = this.value;
  const cable = document.getElementById("cable_id").value;

  if (iuc && cable) {
    showLoader();
    fetch(`verify_iuc.php?iuc=${iuc}&cable=${cable}`)
      .then(res => res.json())
      .then(data => {
        hideLoader();
        const div = document.getElementById("iuc_name");
        if (data.status === "success") {
          div.textContent = "Name: " + data.name;
        } else {
          div.textContent = "Invalid IUC!";
          document.getElementById("iuc").focus();
        }
      })
      .catch(() => {
        hideLoader();
        Swal.fire("Error", "Unable to verify IUC", "error");
      });
  }
});

document.getElementById("cableForm").addEventListener("submit", function(e) {
  e.preventDefault();

  // 🛑 Ensure amount is filled even if user didn’t change plan after load
  const planSelect = document.getElementById("plan_id");
  if (planSelect.selectedIndex > -1) {
    const selectedOption = planSelect.options[planSelect.selectedIndex];
    document.getElementById("amount").value = selectedOption.dataset.amount || 0;
  }

  const formData = new FormData(this);

  showLoader();
  fetch("submit_subscription.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    hideLoader();
    if (data.status === "success") {
      Swal.fire("Success", data.message, "success");
      document.getElementById("cableForm").reset();
      document.getElementById("plan_id").innerHTML = "";
      document.getElementById("iuc_name").textContent = "";
    } else {
      Swal.fire("Error", data.message || "Something went wrong", "error");
    }
  })
  .catch(() => {
    hideLoader();
    Swal.fire("Error", "Something went wrong", "error");
  });
});

</script>

</body>
</html>
