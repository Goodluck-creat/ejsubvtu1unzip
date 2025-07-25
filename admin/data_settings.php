<?php
session_start();
include("../connection.php");

// 🔒 Allow only admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

header("Content-Type: text/html; charset=UTF-8");

// CSRF Token Setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch all data plans
$plans = $conn->query("SELECT * FROM data_plans ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Handle AJAX requests (Add, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $action = $_POST['action'];

    // Input cleaning function
    function clean($val) {
        return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
    }

    if ($action === 'add') {
        $network = clean($_POST['network']);
        $plan_name = clean($_POST['plan_name']);
        $price = floatval($_POST['price']);
        $agent_price = floatval($_POST['agent_price']);
        $plan_id = clean($_POST['plan_id']);
        $network_code = clean($_POST['network_code']);

        if (!$network || !$plan_name || !$plan_id || !$network_code || $price <= 0) {
            echo json_encode(["status" => "error", "message" => "Missing required fields"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO data_plans (network, plan_name, price, agent_price, plan_id, network_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddss", $network, $plan_name, $price, $agent_price, $plan_id, $network_code);
        echo $stmt->execute()
            ? json_encode(["status" => "success", "message" => "Plan added successfully"])
            : json_encode(["status" => "error", "message" => "Failed to add plan"]);
        exit;
    }

    if ($action === 'update') {
        $id = intval($_POST['id']);
        $network = clean($_POST['network']);
        $plan_name = clean($_POST['plan_name']);
        $price = floatval($_POST['price']);
        $agent_price = floatval($_POST['agent_price']);
        $plan_id = clean($_POST['plan_id']);
        $network_code = clean($_POST['network_code']);

        $stmt = $conn->prepare("UPDATE data_plans SET network=?, plan_name=?, price=?, agent_price=?, plan_id=?, network_code=? WHERE id=?");
        $stmt->bind_param("ssddssi", $network, $plan_name, $price, $agent_price, $plan_id, $network_code, $id);
        echo $stmt->execute()
            ? json_encode(["status" => "success", "message" => "Plan updated"])
            : json_encode(["status" => "error", "message" => "Update failed"]);
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM data_plans WHERE id=?");
        $stmt->bind_param("i", $id);
        echo $stmt->execute()
            ? json_encode(["status" => "success", "message" => "Plan deleted"])
            : json_encode(["status" => "error", "message" => "Delete failed"]);
        exit;
    }

    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure Data Plan Settings</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 20px; overflow-x: auto; display: block; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #007BFF; color: #fff; }
        input[type="text"], input[type="number"] { width: 100%; padding: 6px; }
        button { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .saveBtn { background: green; color: white; }
        .deleteBtn { background: red; color: white; }
        .addBtn { background: #007BFF; color: white; }
        .msg { padding: 10px; margin-bottom: 15px; display: none; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .pagination { margin-top: 10px; text-align: center; }
        .pagination button { padding: 6px 10px; margin: 2px; border: 1px solid #007BFF; background: white; color: #007BFF; border-radius: 4px; }
        .pagination button.active { background: #007BFF; color: white; }
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            tr { margin-bottom: 15px; }
            td { text-align: left; padding-left: 40%; position: relative; }
            td::before {
                position: absolute;
                top: 10px;
                left: 10px;
                width: 35%;
                white-space: nowrap;
                font-weight: bold;
            }
        }
    </style>
</head>
<body>

<h2>Admin: Data Plan Settings</h2>
<a href="export_excel.php" target="_blank" style="display:inline-block;margin-bottom:15px;text-decoration:none;padding:10px 14px;background:#28a745;color:white;border-radius:4px;">⬇️ Export to Excel</a>



<div class="msg" id="msgBox"></div>

<!-- Add New Plan -->
<h3>Add New Plan</h3>
<table>
<tr>
    <td><input type="text" id="new_network" placeholder="Network"></td>
    <td><input type="text" id="new_plan_name" placeholder="Plan Name"></td>
    <td><input type="number" id="new_price" placeholder="Price"></td>
    <td><input type="number" id="new_agent_price" placeholder="Agent Price"></td>
    <td><input type="text" id="new_plan_id" placeholder="API Plan ID"></td>
    <td><input type="text" id="new_network_code" placeholder="Network Code"></td>
    <td><button class="addBtn" onclick="addPlan()">Add</button></td>
</tr>
</table>

<!-- Existing Plans -->
<h3>Existing Plans</h3>
<table>
<thead>
<tr>
    <th>ID</th><th>Network</th><th>Plan Name</th><th>Price</th><th>Agent Price</th><th>Plan ID</th><th>Net Code</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($plans as $plan): ?>
<tr data-id="<?= $plan['id'] ?>">
    <td><?= $plan['id'] ?></td>
    <td><input type="text" class="network" value="<?= htmlspecialchars($plan['network']) ?>"></td>
    <td><input type="text" class="plan_name" value="<?= htmlspecialchars($plan['plan_name']) ?>"></td>
    <td><input type="number" class="price" value="<?= $plan['price'] ?>"></td>
    <td><input type="number" class="agent_price" value="<?= $plan['agent_price'] ?>"></td>
    <td><input type="text" class="plan_id" value="<?= htmlspecialchars($plan['plan_id']) ?>"></td>
    <td><input type="text" class="network_code" value="<?= htmlspecialchars($plan['network_code']) ?>"></td>
    <td>
        <button class="saveBtn" onclick="updatePlan(this)">Save</button>
        <button class="deleteBtn" onclick="deletePlan(this)">Delete</button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<script>
const csrf = "<?= $csrf_token ?>";
const msgBox = document.getElementById("msgBox");

function showMessage(msg, status) {
    msgBox.innerHTML = msg;
    msgBox.className = `msg ${status}`;
    msgBox.style.display = "block";
    setTimeout(() => msgBox.style.display = "none", 4000);
}

function addPlan() {
    const data = {
        action: "add",
        csrf_token: csrf,
        network: document.getElementById("new_network").value,
        plan_name: document.getElementById("new_plan_name").value,
        price: document.getElementById("new_price").value,
        agent_price: document.getElementById("new_agent_price").value,
        plan_id: document.getElementById("new_plan_id").value,
        network_code: document.getElementById("new_network_code").value
    };
    sendData(data);
}

function updatePlan(btn) {
    const row = btn.closest("tr");
    const data = {
        action: "update",
        csrf_token: csrf,
        id: row.dataset.id,
        network: row.querySelector(".network").value,
        plan_name: row.querySelector(".plan_name").value,
        price: row.querySelector(".price").value,
        agent_price: row.querySelector(".agent_price").value,
        plan_id: row.querySelector(".plan_id").value,
        network_code: row.querySelector(".network_code").value
    };
    sendData(data);
}

function deletePlan(btn) {
    if (!confirm("Are you sure you want to delete this plan?")) return;
    const id = btn.closest("tr").dataset.id;
    sendData({ action: "delete", csrf_token: csrf, id });
}

function sendData(data) {
    const formData = new URLSearchParams(data);
    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(res => {
        showMessage(res.message, res.status);
        if (res.status === "success") setTimeout(() => location.reload(), 1000);
    });
}

// 📄 Pagination
const rowsPerPage = 5;
const rows = [...document.querySelectorAll("tbody tr")];
const totalPages = Math.ceil(rows.length / rowsPerPage);
let currentPage = 1;

function showPage(page) {
    rows.forEach((row, index) => {
        row.style.display = (index >= (page - 1) * rowsPerPage && index < page * rowsPerPage) ? "" : "none";
    });
    document.querySelectorAll(".pagination button").forEach((btn, i) => {
        btn.classList.toggle("active", i + 1 === page);
    });
}

function createPagination() {
    const container = document.createElement("div");
    container.className = "pagination";
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement("button");
        btn.textContent = i;
        btn.onclick = () => { currentPage = i; showPage(i); };
        if (i === 1) btn.classList.add("active");
        container.appendChild(btn);
    }
    document.body.appendChild(container);
}

if (totalPages > 1) {
    createPagination();
    showPage(1);
}
</script>

</body>
</html>
