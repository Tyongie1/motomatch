<?php
session_start();
require_once 'db.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $msg = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $msg = 'Password must be at least 6 characters.';
    } else {
        // Check if username/email exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param('ss', $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $msg = 'Username or email already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $username, $email, $hash);
            if ($stmt->execute()) {
                $msg = 'Registration successful! <a href="customer_login.php">Login now</a>';
            } else {
                $msg = 'Registration failed. Try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MotoMatch | Register</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <header class="navbar">
    <h1>MotoMatch</h1>
    <nav><a href="home.html">Home</a></nav>
  </header>

  <main class="login-container">
    <div class="login-box">
      <h2>Customer Register</h2>
      <?php if ($msg): ?><p class="login-message" style="color:#ff3333;"><?php echo $msg; ?></p><?php endif; ?>
      <form method="POST">
        <input type="text" name="username" placeholder="Username" required />
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password (6+ chars)" required />
        <button class="btn-primary" type="submit">Register</button>
      </form>
      <p class="signup-text">Already have an account? <a href="customer_login.php">Login</a></p>
    </div>
  </main>

  <footer><p>© 2025 MotoMatch</p></footer>
</body>
</html>