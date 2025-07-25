<?php
session_start();
include("../connection.php");

// 🔐 Allow only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

header("Content-Type: text/html; charset=UTF-8");

// CSRF Token Setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle AJAX actions (Add, Update, Delete, Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $action = $_POST['action'] ?? '';
    function clean($val) {
        return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
    }

    if ($action === 'add') {
        $network_name = clean($_POST['network_name']);
        $network_code = intval($_POST['network_code']);
        $amount = floatval($_POST['amount']);
        $discount_price = floatval($_POST['discount_price']);

        if ($network_name && $network_code && $amount > 0 && $discount_price > 0) {
            $stmt = $conn->prepare("INSERT INTO airtime_offers (network_name, network_code, amount, discount_price, active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("sidd", $network_name, $network_code, $amount, $discount_price);
            echo $stmt->execute()
                ? json_encode(["status" => "success", "message" => "Offer added"])
                : json_encode(["status" => "error", "message" => "Failed to add offer"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid input"]);
        }
        exit;
    }

    if ($action === 'update') {
        $id = intval($_POST['id']);
        $network_name = clean($_POST['network_name']);
        $network_code = intval($_POST['network_code']);
        $amount = floatval($_POST['amount']);
        $discount_price = floatval($_POST['discount_price']);

        $stmt = $conn->prepare("UPDATE airtime_offers SET network_name=?, network_code=?, amount=?, discount_price=? WHERE id=?");
        $stmt->bind_param("siddi", $network_name, $network_code, $amount, $discount_price, $id);
        echo $stmt->execute()
            ? json_encode(["status" => "success", "message" => "Offer updated"])
            : json_encode(["status" => "error", "message" => "Update failed"]);
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM airtime_offers WHERE id=?");
        $stmt->bind_param("i", $id);
        echo $stmt->execute()
            ? json_encode(["status" => "success", "message" => "Offer deleted"])
            : json_encode(["status" => "error", "message" => "Delete failed"]);
        exit;
    }

    if ($action === 'toggle') {
        $id = intval($_POST['id']);
        $current = intval($_POST['current']);
        $new = $current ? 0 : 1;
        $stmt = $conn->prepare("UPDATE airtime_offers SET active=? WHERE id=?");
        $stmt->bind_param("ii", $new, $id);
        echo $stmt->execute()
            ? json_encode(["status" => "success", "message" => "Visibility updated"])
            : json_encode(["status" => "error", "message" => "Toggle failed"]);
        exit;
    }

    exit;
}

// Pagination logic
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Fetch paginated data
$total = $conn->query("SELECT COUNT(*) FROM airtime_offers")->fetch_row()[0];
$offers = $conn->query("SELECT * FROM airtime_offers ORDER BY id DESC LIMIT $offset, $perPage")->fetch_all(MYSQLI_ASSOC);
$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Airtime Settings</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; }
        h2 { text-align: center; }
        table { width: 100%; background: white; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #007BFF; color: white; }
        input, select { width: 100%; padding: 5px; }
        button { padding: 5px 10px; border: none; cursor: pointer; border-radius: 4px; }
        .btn-add { background: green; color: white; }
        .btn-update { background: #007BFF; color: white; }
        .btn-delete { background: red; color: white; }
        .btn-toggle { background: orange; color: white; }
        .msg { padding: 10px; margin: 10px 0; display: none; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .pagination { margin-top: 15px; text-align: center; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 6px 12px; background: #007BFF; color: white; border-radius: 4px; }
        .pagination .current { background: #333; }
        @media (max-width: 768px) {
            table, tr, td, th { font-size: 12px; }
        }
    </style>
</head>
<body>

<h2>Admin: Airtime Offer Settings</h2>

<div id="msgBox" class="msg"></div>

<!-- Add Offer Form -->
<h3>Add New Offer</h3>
<table>
<tr>
    <td>
        <select id="network_name">
            <option value="">Select Network</option>
            <option value="MTN">MTN</option>
            <option value="Airtel">Airtel</option>
            <option value="Glo">Glo</option>
            <option value="9mobile">9mobile</option>
        </select>
    </td>
    <td>
        <select id="network_code">
            <option value="">Code</option>
            <option value="1">1 - MTN</option>
            <option value="2">2 - Airtel</option>
            <option value="3">3 - Glo</option>
            <option value="6">6 - 9mobile</option>
        </select>
    </td>
    <td><input type="number" id="amount" placeholder="Amount" min="50" step="0.01"></td>
    <td><input type="number" id="discount_price" placeholder="Discount Price" min="50" step="0.01"></td>
    <td><button class="btn-add" onclick="addOffer()">Add</button></td>
</tr>
</table>

<!-- Export to Excel -->
<div style="text-align:right; margin: 10px 0;">
    <a href="export_airtime_excel.php" target="_blank"><button>⬇ Export to Excel</button></a>
</div>

<!-- Offer List -->
<table>
<thead>
<tr>
    <th>ID</th><th>Network</th><th>Code</th><th>Amount</th><th>Discount</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($offers as $offer): ?>
<tr data-id="<?= $offer['id'] ?>"">
    <td><?= $offer['id'] ?></td>
    <td><input type="text" class="network_name" value="<?= $offer['network_name'] ?>"></td>
    <td><input type="number" class="network_code" value="<?= $offer['network_code'] ?>"></td>
    <td><input type="number" class="amount" value="<?= $offer['amount'] ?>"></td>
    <td><input type="number" class="discount_price" value="<?= $offer['discount_price'] ?>"></td>
    
    <td>
        <button class="btn-update" onclick="updateOffer(this)">Save</button>
        <button class="btn-delete" onclick="deleteOffer(this)">Delete</button>
       
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- Pagination -->
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="<?= ($i === $page ? 'current' : '') ?>" href="?page=<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>

<script>
const csrf = "<?= $csrf_token ?>";
const msgBox = document.getElementById("msgBox");

function showMessage(msg, type) {
    msgBox.innerHTML = msg;
    msgBox.className = `msg ${type}`;
    msgBox.style.display = "block";
    setTimeout(() => msgBox.style.display = "none", 4000);
}

function send(data) {
    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(data)
    })
    .then(res => res.json())
    .then(res => {
        showMessage(res.message, res.status);
        if (res.status === 'success') setTimeout(() => location.reload(), 1000);
    });
}

function addOffer() {
    send({
        action: "add", csrf_token: csrf,
        network_name: document.getElementById("network_name").value,
        network_code: document.getElementById("network_code").value,
        amount: document.getElementById("amount").value,
        discount_price: document.getElementById("discount_price").value
    });
}

function updateOffer(btn) {
    const row = btn.closest("tr");
    send({
        action: "update", csrf_token: csrf,
        id: row.dataset.id,
        network_name: row.querySelector(".network_name").value,
        network_code: row.querySelector(".network_code").value,
        amount: row.querySelector(".amount").value,
        discount_price: row.querySelector(".discount_price").value
    });
}

function deleteOffer(btn) {
    if (!confirm("Delete this offer?")) return;
    const id = btn.closest("tr").dataset.id;
    send({ action: "delete", csrf_token: csrf, id });
}

function toggleOffer(btn) {
    const row = btn.closest("tr");
    send({ action: "toggle", csrf_token: csrf, id: row.dataset.id, current: row.dataset.active });
}
</script>

</body>
</html>
