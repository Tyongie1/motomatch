<?php
// admin_login.php (New file for admin login)
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admins WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['shop_name'] = $row['shop_name'];
            header("Location: admin_dashboard.php");
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No admin found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MotoMatch | Admin Login</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <header class="navbar">
    <h1>MotoMatch 🏍️</h1>
    <nav>
      <a href="home.html">Home</a>
    </nav>
  </header>

  <main class="login-container">
    <div class="login-box">
      <h2>Admin Login</h2>
      <form method="POST">
        <input type="text" name="username" placeholder="Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <button class="btn-primary" type="submit">Login</button>
      </form>
      <p class="signup-text">Don’t have an account? <a href="admin_register.php">Sign up</a></p>
    </div>
  </main>

  <footer>
    <p>© 2025 MotoMatch | All Rights Reserved</p>
  </footer>
</body>
</html>