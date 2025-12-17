<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: customer_login.php");
    exit();
}
include 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch ALL products with shop info
$products = [];
$sql = "SELECT p.*, a.shop_name, a.id AS admin_id 
        FROM products p 
        JOIN admins a ON p.admin_id = a.id 
        WHERE p.quantity > 0 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Place Order
if (isset($_POST['place_order'])) {
    $product_id = intval($_POST['product_id']);
    $admin_id   = intval($_POST['admin_id']);

    $check = $conn->prepare("SELECT name FROM products WHERE id = ? AND quantity > 0");
    $check->bind_param("i", $product_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $order = $conn->prepare("INSERT INTO orders (user_id, product_id, status) VALUES (?, ?, 'pending')");
        $order->bind_param("ii", $user_id, $product_id);
        if ($order->execute()) {
            $order_id = $conn->insert_id;
            $msg = "New order (ID #$order_id) from user";
            $notify = $conn->prepare("INSERT INTO notifications (recipient_id, recipient_type, message, order_id) VALUES (?, 'admin', ?, ?)");
            $notify->bind_param("isi", $admin_id, $msg, $order_id);
            $notify->execute();
            $notify->close();
            header("Location: user_index.php?ok=1");
            exit();
        }
    }
    $check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MotoMatch | Shop Parts</title>
  <link rel="stylesheet" href="style.css">
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
    .navbar { background: #1a1a1a; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
    .navbar a { color: white; text-decoration: none; margin: 0 1rem; font-weight: bold; }
    .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }

    /* Search Bar */
    .search-box {
      background: white;
      padding: 1.5rem;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
      text-align: center;
    }
    .search-box input {
      width: 100%;
      max-width: 600px;
      padding: 1rem;
      font-size: 1.1rem;
      border: 2px solid #ddd;
      border-radius: 50px;
      outline: none;
    }
    .search-box input:focus { border-color: #28a745; }

    /* Product Grid */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem;
    }
    .product-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform 0.2s;
    }
    .product-card:hover { transform: translateY(-5px); }
    .product-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    .info {
      padding: 1rem;
    }
    .info h3 {
      margin: 0 0 0.5rem;
      font-size: 1.2rem;
      color: #333;
    }
    .price {
      font-size: 1.4rem;
      font-weight: bold;
      color: #28a745;
    }
    .stock {
      font-size: 0.9rem;
      color: #666;
      margin: 0.3rem 0;
    }
    .shop {
      font-size: 0.95rem;
      color: #007bff;
      margin-bottom: 0.8rem;
    }
    .order-btn {
      width: 100%;
      padding: 0.8rem;
      background: #28a745;
      color: white;
      border: none;
      font-weight: bold;
      cursor: pointer;
      border-radius: 8px;
    }
    .order-btn:hover { background: #218838; }

    .alert {
      padding: 1rem;
      margin: 1rem 0;
      border-radius: 8px;
      text-align: center;
      font-weight: bold;
    }
    .success { background: #d4edda; color: #155724; }
  </style>
</head>
<body>

  <header class="navbar">
    <h1>MotoMatch</h1>
    <nav>
      <a href="home.html">Home</a>
      <a href="user_index.php" style="color:#28a745;">Shop Parts</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <div class="container">

    <?php if (isset($_GET['ok'])): ?>
      <div class="alert success">Order placed! Shop will contact you soon.</div>
    <?php endif; ?>

    <!-- SEARCH BAR -->
    <div class="search-box">
      <input type="text" id="searchInput" placeholder="🔍 Search parts: brake, chain, oil filter..." autofocus>
      <p style="margin:0.5rem 0 0; color:#666;">Showing <strong><?= count($products) ?></strong> parts from local shops</p>
    </div>

    <!-- PRODUCTS GRID -->
    <div class="products-grid" id="productsContainer">
      <?php foreach ($products as $p): ?>
        <div class="product-card">
          <img src="<?= htmlspecialchars($p['image'] ?: 'uploads/no-image.jpg') ?>" alt="<?= htmlspecialchars($p['name']) ?>">
          <div class="info">
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <div class="price">₱<?= number_format($p['price'], 2) ?></div>
            <div class="stock">Stock: <?= $p['quantity'] ?></div>
            <div class="shop">Store: <?= htmlspecialchars($p['shop_name']) ?></div>
            <form method="POST">
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="admin_id" value="<?= $p['admin_id'] ?>">
              <button type="submit" name="place_order" class="order-btn">ORDER NOW</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
      <div style="text-align:center; padding:3rem; color:#666;">
        <h2>No parts yet</h2>
        <p>Ask shop admins to add items!</p>
      </div>
    <?php endif; ?>

  </div>

  <script>
    // Live Search
    const searchInput = document.getElementById('searchInput');
    const cards = document.querySelectorAll('.product-card');

    searchInput.addEventListener('input', () => {
      const term = searchInput.value.toLowerCase();
      cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(term) ? 'block' : 'none';
      });
    });
  </script>

</body>
</html>