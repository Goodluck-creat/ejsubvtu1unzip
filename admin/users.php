<?php
session_start();
include("../connection.php");

// 🔒 Allow only admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// 📌 Uncomment this to enable CSRF protection
// if (empty($_SESSION['csrf_token'])) {
//     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// }

// ✅ Handle User Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    // if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //     die("Invalid CSRF token.");
    // }

    $id = intval($_POST['id']);
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $wallet = floatval($_POST['wallet_balance']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

    if ($email && $fullname && $phone) {
        $stmt = $conn->prepare("UPDATE vtu_users SET fullname=?, email=?, phone=?, wallet_balance=?, role=? WHERE id=?");
        $stmt->bind_param("sssisi", $fullname, $email, $phone, $wallet, $role, $id);
        $stmt->execute();
        $msg = "User ID $id updated successfully!";
    } else {
        $msg = "Invalid input data!";
    }
}

// 🔍 Search
$search = "";
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $query = "SELECT * FROM vtu_users WHERE fullname LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%'";
} else {
    $query = "SELECT * FROM vtu_users";
}

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - User Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 10px; margin: 0; }
        h2 { text-align: center; }

        .msg {
            background-color: #e0ffe0;
            padding: 10px;
            color: green;
            font-weight: bold;
            margin-bottom: 15px;
            border: 1px solid green;
            border-radius: 5px;
        }

        .search-box {
            text-align: center;
            margin: 10px 0;
        }

        .search-box input[type="text"] {
            padding: 8px;
            width: 90%;
            max-width: 400px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            overflow-x: auto;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }

        input[type='text'], input[type='email'], input[type='number'], select {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
        }

        .submit-btn {
            padding: 6px 10px;
            background: green;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: darkgreen;
        }

        @media screen and (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                display: none;
            }

            tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                padding: 10px;
                background-color: #fff;
            }

            td {
                text-align: left;
                position: relative;
                padding-left: 50%;
            }

            td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                font-weight: bold;
                white-space: nowrap;
            }

            input, select {
                width: 90%;
            }
        }
    </style>
</head>
<body>

<h2>Admin Panel – Manage Users</h2>

<?php if (isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

<div class="search-box">
    <form method="GET">
        <input type="text" name="search" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($search) ?>">
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Wallet (₦)</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($user = $result->fetch_assoc()): ?>
        <tr>
            <form method="POST">
                <!-- <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"> -->
                <input type="hidden" name="id" value="<?= $user['id'] ?>">

                <td data-label="ID"><?= $user['id'] ?></td>
                <td data-label="Full Name">
                    <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                </td>
                <td data-label="Email">
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </td>
                <td data-label="Phone">
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                </td>
                <td data-label="Role">
                    <select name="role">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </td>
                <td data-label="Wallet">
                    <input type="number" step="0.01" name="wallet_balance" value="<?= $user['wallet_balance'] ?>">
                </td>
                <td data-label="Created At"><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                <td data-label="Action">
                    <button class="submit-btn" name="update_user">Update</button>
                </td>
            </form>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
