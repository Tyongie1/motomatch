<?php
// admin_dashboard.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$admin_id = $_SESSION['admin_id'];
$message = '';

// === FETCH ADMIN INFO ===
$stmt = $conn->prepare("SELECT username, shop_name FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$admin_username = $admin['username'] ?? 'Admin';
$admin_shop_name = $admin['shop_name'] ?? 'My Moto Shop';
$stmt->close();

// === ADD PRODUCT (SECURE) ===
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);

    if (empty($name) || $price <= 0 || $quantity < 0 || empty($_FILES['image']['name'])) {
        $message = "Please fill all fields correctly.";
    } else {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $image_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid('prod_') . '.' . $image_ext;
        $target_path = $upload_dir . $new_filename;

        // Validate file
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($image_ext, $allowed)) {
            $message = "Only JPG, PNG, GIF allowed.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $message = "Image must be < 5MB.";
        } elseif (in_array($mime, ['image/jpeg', 'image/png', 'image/gif']) && move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            // SECURE INSERT USING PREPARED STATEMENT
            $stmt = $conn->prepare("INSERT INTO products (admin_id, name, price, quantity, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdis", $admin_id, $name, $price, $quantity, $target_path);

            if ($stmt->execute()) {
                header("Location: admin_dashboard.php?success=1");
                exit();
            } else {
                $message = "Database error: " . $stmt->error;
                @unlink($target_path);
            }
            $stmt->close();
        } else {
            $message = "Failed to upload image.";
        }
    }
}

// === DELETE PRODUCT ===
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $image_path = $row['image'];
        $del_stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND admin_id = ?");
        $del_stmt->bind_param("ii", $id, $admin_id);
        if ($del_stmt->execute()) {
            if (file_exists($image_path)) unlink($image_path);
            header("Location: admin_dashboard.php?deleted=1");
            exit();
        }
        $del_stmt->close();
    }
    $stmt->close();
}

// === ORDER ACTIONS ===
if (isset($_POST['accept_order'])) {
    $order_id = intval($_POST['order_id']);
    $conn->autocommit(false);
    try {
        $conn->query("UPDATE orders SET status = 'accepted' WHERE id = $order_id");
        $res = $conn->query("SELECT user_id, product_id FROM orders WHERE id = $order_id");
        $order = $res->fetch_assoc();
        $msg = "Your order for product ID {$order['product_id']} has been accepted. Pickup at shop.";
        $stmt = $conn->prepare("INSERT INTO notifications (recipient_id, recipient_type, message, order_id) VALUES (?, 'user', ?, ?)");
        $stmt->bind_param("isi", $order['user_id'], $msg, $order_id);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
    }
    $conn->autocommit(true);
    header("Location: admin_dashboard.php");
    exit();
}

if (isset($_POST['decline_order'])) {
    $order_id = intval($_POST['order_id']);
    $conn->autocommit(false);
    try {
        $conn->query("UPDATE orders SET status = 'declined' WHERE id = $order_id");
        $res = $conn->query("SELECT user_id, product_id FROM orders WHERE id = $order_id");
        $order = $res->fetch_assoc();
        $msg = "Your order for product ID {$order['product_id']} has been declined.";
        $stmt = $conn->prepare("INSERT INTO notifications (recipient_id, recipient_type, message, order_id) VALUES (?, 'user', ?, ?)");
        $stmt->bind_param("isi", $order['user_id'], $msg, $order_id);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
    }
    $conn->autocommit(true);
    header("Location: admin_dashboard.php");
    exit();
}

// === FETCH DATA ===
$products = $conn->query("SELECT * FROM products WHERE admin_id = $admin_id ORDER BY id DESC");

$orders_sql = "SELECT o.*, p.name AS product_name, u.username AS user_name 
               FROM orders o 
               JOIN products p ON o.product_id = p.id 
               JOIN users u ON o.user_id = u.id 
               WHERE p.admin_id = $admin_id AND o.status = 'pending'";
$orders = $conn->query($orders_sql);

$notifications = $conn->query("SELECT * FROM notifications 
                               WHERE recipient_id = $admin_id 
                               AND recipient_type = 'admin' 
                               ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MotoMatch | Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
    .navbar { background: #222; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
    .navbar h1 { margin: 0; font-size: 1.5rem; }
    .navbar a { color: white; text-decoration: none; }
    .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
    .card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .admin-profile { background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-align: center; padding: 2rem; border-radius: 16px; }
    .admin-profile h2 { margin: 0 0 0.5rem; font-size: 1.8rem; }
    .alert { padding: 1rem; margin: 1rem 0; border-radius: 8px; font-weight: bold; }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    input, button { padding: 0.7rem; margin: 0.5rem 0; border-radius: 6px; border: 1px solid #ddd; }
    input[type="file"] { border: none; }
    button { background: #007bff; color: white; border: none; cursor: pointer; font-weight: bold; }
    button:hover { background: #0056b3; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #f8f9fa; }
    .btn-small { padding: 0.4rem 0.8rem; font-size: 0.9rem; }
    .btn-accept { background: #28a745; }
    .btn-decline { background: #dc3545; }
    .btn-accept:hover { background: #218838; }
    .btn-decline:hover { background: #c82333; }
  </style>
</head>
<body>

  <header class="navbar">
    <h1>MotoMatch - Admin Dashboard</h1>
    <nav><a href="logout.php">Logout</a></nav>
  </header>

  <div class="container">

    <!-- Messages -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert success">Product added successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert success">Product deleted!</div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
      <div class="alert error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Admin Profile -->
    <div class="card admin-profile">
      <h2>Welcome, <?= htmlspecialchars($admin_username) ?>!</h2>
      <p><strong>Shop:</strong> <?= htmlspecialchars($admin_shop_name) ?></p>
    </div>

    <!-- Add Product -->
    <div class="card">
      <h2>Add New Product</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Product Name" required>
        <input type="number" step="0.01" name="price" placeholder="Price (₱)" required>
        <input type="number" name="quantity" placeholder="Quantity" required>
        <input type="file" name="image" accept="image/*" required>
        <button type="submit" name="add_product">Add Product</button>
      </form>
    </div>

    <!-- Products -->
    <div class="card">
      <h2>Your Products</h2>
      <?php if ($products->num_rows > 0): ?>
        <table>
          <tr><th>Name</th><th>Price</th><th>Qty</th><th>Image</th><th>Actions</th></tr>
          <?php while ($p = $products->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td>₱<?= number_format($p['price'], 2) ?></td>
              <td><?= $p['quantity'] ?></td>
              <td><img src="<?= htmlspecialchars($p['image']) ?>" width="60" style="border-radius:6px;"></td>
              <td>
                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn-small" style="background:#ffc107;color:black;">Edit</a>
                <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete?')" class="btn-small" style="background:#dc3545;color:white;">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </table>
      <?php else: ?>
        <p>No products yet.</p>
      <?php endif; ?>
    </div>

    <!-- Orders -->
    <div class="card">
      <h2>Pending Orders</h2>
      <?php if ($orders->num_rows > 0): ?>
        <table>
          <tr><th>User</th><th>Product</th><th>Actions</th></tr>
          <?php while ($o = $orders->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($o['user_name']) ?></td>
              <td><?= htmlspecialchars($o['product_name']) ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <button type="submit" name="accept_order" class="btn-small btn-accept">Accept</button>
                  <button type="submit" name="decline_order" class="btn-small btn-decline">Decline</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </table>
      <?php else: ?>
        <p>No pending orders.</p>
      <?php endif; ?>
    </div>

    <!-- Notifications -->
    <div class="card">
      <h2>Notifications</h2>
      <?php if ($notifications->num_rows > 0): ?>
        <ul style="list-style:none;padding:0;">
          <?php while ($n = $notifications->fetch_assoc()): ?>
            <li style="background:#f8f9fa;padding:1rem;margin:0.5rem 0;border-radius:8px;">
              <?= htmlspecialchars($n['message']) ?>
              <br><small style="color:#666;"><?= $n['created_at'] ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <p>No notifications.</p>
      <?php endif; ?>
    </div>

  </div>

</body>
</html>