<?php
// edit_product.php (New file, basic edit form)
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$id = $_GET['id'];
$admin_id = $_SESSION['admin_id'];

if (isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $image = $_FILES['image']['name'] ? "uploads/" . $_FILES['image']['name'] : $_POST['old_image'];
    if ($_FILES['image']['name']) move_uploaded_file($_FILES['image']['tmp_name'], $image);

    $sql = "UPDATE products SET name='$name', price=$price, quantity=$quantity, image='$image' WHERE id=$id AND admin_id=$admin_id";
    if ($conn->query($sql)) {
        header("Location: admin_dashboard.php");
    }
}

$sql = "SELECT * FROM products WHERE id=$id AND admin_id=$admin_id";
$product = $conn->query($sql)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Edit Product</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <main class="login-container">
    <div class="login-box">
      <h2>Edit Product</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" value="<?php echo $product['name']; ?>" required />
        <input type="number" name="price" value="<?php echo $product['price']; ?>" required />
        <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" required />
        <input type="hidden" name="old_image" value="<?php echo $product['image']; ?>">
        <input type="file" name="image" accept="image/*" />
        <button type="submit" name="update_product">Update</button>
      </form>
    </div>
  </main>
</body>
</html>