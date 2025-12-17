<?php
// admin_register.php (New file for admin registration)
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $shop_name = $_POST['shop_name'];
    $shop_lat = $_POST['shop_lat'];
    $shop_lng = $_POST['shop_lng'];

    $sql = "INSERT INTO admins (username, password, shop_name, shop_lat, shop_lng) VALUES ('$username', '$password', '$shop_name', '$shop_lat', '$shop_lng')";

    if ($conn->query($sql) === TRUE) {
        header("Location: admin_login.php?msg=Registration successful!");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MotoMatch | Admin Register</title>
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
      <h2>Admin (Shop Owner) Register</h2>
      <form method="POST">
        <input type="text" name="username" placeholder="Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="text" name="shop_name" placeholder="Shop Name" required />
        <input type="number" step="any" name="shop_lat" placeholder="Shop Latitude (e.g., 9.6477)" required />
        <input type="number" step="any" name="shop_lng" placeholder="Shop Longitude (e.g., 123.8552)" required />
        <button class="btn-primary" type="submit">Register</button>
      </form>
      <p class="signup-text">Already have an account? <a href="admin_login.php">Login</a></p>
    </div>
  </main>

  <footer>
    <p>© 2025 MotoMatch | All Rights Reserved</p>
  </footer>
</body>
</html>