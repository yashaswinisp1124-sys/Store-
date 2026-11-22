<?php
require_once 'includes/auth.php';
include 'includes/connect.php';

$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart | Hidden Store</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include 'includes/header.php'; ?>

<main class="cart-container">

    <h2>Your Shopping Cart</h2>

    <?php if (empty($cart)): ?>

        <p>Your cart is empty.</p>

        <a href="index.php" class="continue-btn">
            Continue Shopping
        </a>

    <?php else: ?>

        <?php foreach ($cart as $id => $quantity): ?>

            <?php

            $stmt = mysqli_prepare(
                $conn,
                "SELECT * FROM products WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);

            if (!$product) {
                unset($_SESSION['cart'][$id]);
                mysqli_stmt_close($stmt);
                continue;
            }

            $subtotal = $product['price'] * $quantity;
            $total += $subtotal;

            ?>

            <div class="cart-item">

                <div class="cart-item-details">

                    <h3>
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>

                    <p>
                        Price: ₹<?php echo number_format($product['price'], 2); ?>
                    </p>

                    <p>
                        Quantity: <?php echo (int)$quantity; ?>
                    </p>

                    <p>
                        Subtotal: ₹<?php echo number_format($subtotal, 2); ?>
                    </p>

                </div>

                <form action="remove_from_cart.php" method="POST">

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php echo $id; ?>"
                    >

                    <button type="submit" class="remove-btn">
                        Remove
                    </button>

                </form>

            </div>

            <?php mysqli_stmt_close($stmt); ?>

        <?php endforeach; ?>

        <div class="cart-total">

            <h2>
                Total: ₹<?php echo number_format($total, 2); ?>
            </h2>

        </div>

        <a href="checkout.php" class="continue-btn">
            Proceed to Checkout
        </a>

        <br><br>

        <a href="index.php">
            Continue Shopping
        </a>

    <?php endif; ?>

</main>

</body>
</html>