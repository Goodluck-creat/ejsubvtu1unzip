<?php
session_start();

// Color variables
$primary   = '#00A86B';
$secondary = '#1E1E2F';
$accent    = '#FFD700';

include("connection.php");
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $msg = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, password, role FROM vtu_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (!password_verify($password, $user['password'])) {
                $msg = "Incorrect password.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit;
            }
        } else {
            $msg = "Account not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EJSUB</title>
  <style>
    :root {
      --primary: <?= $primary ?>;
      --secondary: <?= $secondary ?>;
      --accent: <?= $accent ?>;
    }

    body {
      background: var(--secondary);
      font-family: 'Poppins', sans-serif;
      color: white;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-container {
      background: #2E2E40;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
      width: 100%;
      max-width: 400px;
    }

    .login-container h2 {
      color: var(--accent);
      margin-bottom: 20px;
      text-align: center;
    }

    input[type="email"], input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: none;
      border-radius: 5px;
      outline: none;
    }

    button {
      width: 100%;
      padding: 12px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 600;
      margin-top: 10px;
    }

    button:hover {
      background: #007f57;
    }

    .message {
      color: #ff6b6b;
      text-align: center;
      margin-top: 10px;
    }

    .login-container a {
      display: block;
      text-align: center;
      margin-top: 15px;
      color: var(--accent);
      text-decoration: none;
    }

    .login-container a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Login TO EJSUB</h2>
    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <?php if ($msg): ?>
      <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>
    <a href="register.php">Don't have an account? Register</a>
  </div>
</body>
</html>


