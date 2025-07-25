<?php
include("connection.php");
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn = trim($_POST['fullname']);
    $em = trim($_POST['email']);
    $ph = trim($_POST['phone']);
    $pw = $_POST['password'];
    $cp = $_POST['confirm_password'];

    if ($pw !== $cp) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM vtu_users WHERE email = ?");
        $stmt->bind_param("s", $em);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $token = bin2hex(random_bytes(16));
            $hash  = password_hash($pw, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
              "INSERT INTO vtu_users 
               (fullname,email,phone,password,verify_token) 
               VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param("sssss", $fn, $em, $ph, $hash, $token);

            if ($stmt->execute()) {
                // Send welcome email with login link
                $subject = "🎉 Welcome to Ejsub!";
                $loginLink = "https://vtu.chinovate.site/login.php";
                $body = "Hi $fn,\n\n"
                      . "Welcome to AnambraMart VTU! Your account was created successfully. 🎉\n\n"
                      . "👉 You can now log in to your account using the link below:\n"
                      . "$loginLink\n\n"
                      . "Thanks for joining our platform. We’re excited to have you!\n\n"
                      . "– AnambraMart VTU Team";

                $headers = "From: support@yourdomain.com\r\n"
                         . "Reply-To: support@yourdomain.com\r\n"
                         . "X-Mailer: PHP/" . phpversion();

                mail($em, $subject, $body, $headers); // Optional success handling
                $success = "Registered successfully! A login link has been sent to your email.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Register EJSUB</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary:#00A86B; --secondary:#1E1E2F; --accent:#FFD700;
    }
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
    body{background:var(--secondary);color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
    .container{background:#2E2E40;padding:40px;max-width:450px;width:100%;border-radius:12px;box-shadow:0 0 15px rgba(0,168,107,0.3);}
    h2{color:var(--accent);text-align:center;margin-bottom:20px;}
    .message{font-size:14px;text-align:center;margin-bottom:15px;}
    .success{color:#00ffb3;}
    .error{color:#ff5e5e;}
    .form-group{margin-bottom:20px;}
    label{display:block;color:#ddd;margin-bottom:6px;font-size:15px;}
    input{width:100%;padding:10px;border:1px solid #444;border-radius:6px;background:var(--secondary);color:#fff;font-size:15px;}
    input:focus{border-color:var(--primary);box-shadow:0 0 0 1.5px var(--primary);}
    .btn{width:100%;padding:12px;background:var(--accent);color:var(--secondary);font-weight:600;border:none;border-radius:8px;font-size:16px;cursor:pointer;transition:0.3s;}
    .btn:hover{background:#e6c200;transform:scale(1.03);}
    .login-link{text-align:center;margin-top:20px;font-size:14px;color:#ccc;}
    .login-link a{color:var(--primary);text-decoration:none;}
  </style>
</head>
<body>
  <div class="container">
    <h2>Create Your Account</h2>
    <?php if($success): ?>
      <p class="message success"><?= $success ?></p>
    <?php elseif($error): ?>
      <p class="message error"><?= $error ?></p>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label for="fullname">Full Name</label>
        <input id="fullname" name="fullname" required value="<?= htmlspecialchars($_POST['fullname']??'') ?>">
      </div>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input id="email" type="email" name="email" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
      </div>
      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input id="phone" type="tel" name="phone" required value="<?= htmlspecialchars($_POST['phone']??'') ?>">
      </div>
      <div class="form-group">
        <label for="password">Create Password</label>
        <input id="password" type="password" name="password" required>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input id="confirm_password" type="password" name="confirm_password" required>
      </div>
      <button class="btn" type="submit">Register</button>
      <p class="login-link">Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>
</body>
</html>


