<?php
// customer_login.php (Modified from login.html)
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            header("Location: user_index.php");
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MotoMatch | Customer Login</title>
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
      <h2>Customer Login</h2>
      <form method="POST">
        <input type="text" name="username" placeholder="Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <button class="btn-primary" type="submit">Login</button>
      </form>
      <p class="signup-text">Don’t have an account? <a href="customer_register.php">Sign up</a></p>
    </div>
  </main>

  <footer>
    <p>© 2025 MotoMatch | All Rights Reserved</p>
  </footer>
</body>
</html>