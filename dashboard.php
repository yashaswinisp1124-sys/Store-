
<?php
session_start();

require_once '../includes/connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$products = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM products")
);

$orders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders")
);

$users = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM users")
);

$revenue = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(total), 0) AS total FROM orders WHERE payment_status = 'Paid'")
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Hidden Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">

    <span class="navbar-brand">
        HIDDEN STORE - ADMIN
    </span>

    <a href="logout.php" class="btn btn-danger">
        Logout
    </a>

</nav>

<div class="container mt-5">

    <h2>
        Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!
    </h2>

    <p class="text-muted">
        Administrator Dashboard
    </p>

    <div class="row mt-4">

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3">
                <h6>Total Products</h6>
                <h2><?php echo $products['total']; ?></h2>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3">
                <h6>Total Orders</h6>
                <h2><?php echo $orders['total']; ?></h2>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3">
                <h6>Total Users</h6>
                <h2><?php echo $users['total']; ?></h2>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3">
                <h6>Total Revenue</h6>
                <h2>₹<?php echo number_format($revenue['total'], 2); ?></h2>
            </div>
        </div>

    </div>

    <hr>

    <h4 class="mt-4">Management</h4>

    <div class="row mt-3">

        <div class="col-md-4 mb-3">
            <a href="products.php" class="btn btn-dark w-100 p-3">
                Manage Products
            </a>
        </div>

        <div class="col-md-4 mb-3">
            <a href="brands.php" class="btn btn-dark w-100 p-3">
                Manage Brands
            </a>
        </div>

        <div class="col-md-4 mb-3">
            <a href="categories.php" class="btn btn-dark w-100 p-3">
                Manage Categories
            </a>
        </div>

    </div>

</div>

</body>
</html>

