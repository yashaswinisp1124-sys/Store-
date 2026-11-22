<?php
require_once 'includes/auth.php';
require_once 'includes/connect.php';

$order_id = $_SESSION['last_order_id'] ?? 0;

if (!$order_id) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM orders WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM order_items WHERE order_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);

$items = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Successful | Hidden Store</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        .success-container {
            max-width: 650px;
            margin: 50px auto;
            padding: 35px;
            background: white;
            text-align: center;
            border-radius: 12px;
        }

        .success-icon {
            font-size: 65px;
            color: green;
        }

        .success-container h2 {
            color: green;
        }

        .order-details {
            text-align: left;
            background: #f5f5f5;
            padding: 20px;
            margin-top: 25px;
            border-radius: 8px;
        }

        .home-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background: #222;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

    </style>

</head>

<body>

<header>
    <h1>HIDDEN STORE</h1>
</header>

<div class="success-container">

    <div class="success-icon">✓</div>

    <h2>Order Placed Successfully!</h2>

    <p>Thank you for shopping with Hidden Store.</p>

    <div class="order-details">

        <h3>Order Details</h3>

        <p>
            <strong>Order ID:</strong>
            #<?php echo (int)$order['id']; ?>
        </p>

        <p>
            <strong>Customer:</strong>
            <?php echo htmlspecialchars($order['customer_name']); ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?php echo htmlspecialchars($order['phone']); ?>
        </p>

        <p>
            <strong>Delivery Address:</strong>
            <?php
            echo htmlspecialchars(
                $order['address'] . ', ' .
                $order['city'] . ' - ' .
                $order['pincode']
            );
            ?>
        </p>

        <hr>

        <h3>Products Ordered</h3>

        <?php while ($item = mysqli_fetch_assoc($items)): ?>

            <p>
                <?php echo htmlspecialchars($item['product_name']); ?>

                × <?php echo (int)$item['quantity']; ?>

                — ₹<?php echo number_format($item['subtotal'], 2); ?>
            </p>

        <?php endwhile; ?>

        <hr>

        <h3>
            Total: ₹<?php echo number_format($order['total'], 2); ?>
        </h3>

        <p>
            <strong>Payment Method:</strong>
            <?php echo htmlspecialchars($order['payment_method']); ?>
        </p>

        <p>
            <strong>Payment Status:</strong>
            <?php echo htmlspecialchars($order['payment_status']); ?>
        </p>

        <p>
            <strong>Order Status:</strong>
            <?php echo htmlspecialchars($order['order_status']); ?>
        </p>

        <?php if ($order['payment_method'] !== 'COD'): ?>

            <p style="color:#b45309;">
                This was a demonstration order. No actual payment was collected.
            </p>

        <?php else: ?>

            <p>
                Please pay when your order is delivered.
            </p>

        <?php endif; ?>

    </div>

    <a href="index.php" class="home-btn">
        Continue Shopping
    </a>

</div>

</body>
</html>