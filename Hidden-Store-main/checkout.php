
<?php
require_once 'includes/auth.php';
include 'includes/connect.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

// Generate security token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$total = 0;
$cartProducts = [];

// Fetch products and calculate total
foreach ($cart as $id => $quantity) {

    $id = filter_var($id, FILTER_VALIDATE_INT);
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if (!$id || !$quantity || $quantity < 1) {
        continue;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price, stock
         FROM products
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$product) {
        continue;
    }

    $subtotal = $product['price'] * $quantity;
    $total += $subtotal;

    $cartProducts[] = [
        'id' => $id,
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $quantity,
        'subtotal' => $subtotal,
        'stock' => $product['stock']
    ];
}

if (empty($cartProducts)) {
    header("Location: cart.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | Hidden Store</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        .checkout-container {
            max-width: 750px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .checkout-container h2 {
            margin-bottom: 20px;
        }

        .checkout-container input:not([type="radio"]),
        .checkout-container textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .checkout-container label {
            font-weight: bold;
        }

        .order-summary {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
        }

        .payment-option {
            display: block;
            padding: 12px;
            margin: 10px 0;
            background: #f7f7f7;
            border-radius: 6px;
            cursor: pointer;
        }

        .payment-option input {
            margin-right: 10px;
        }

        .place-order-btn {
            width: 100%;
            padding: 14px;
            background: #222;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .place-order-btn:hover {
            background: #444;
        }

        .checkout-error {
            color: red;
            background: #ffecec;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .demo-note {
            color: #777;
            font-size: 14px;
        }

    </style>

</head>

<body>

<?php include 'includes/header.php'; ?>

<div class="checkout-container">

    <h2>Checkout</h2>

    <?php if (!empty($_SESSION['checkout_error'])): ?>

        <div class="checkout-error">

            <?php
            echo htmlspecialchars($_SESSION['checkout_error']);
            unset($_SESSION['checkout_error']);
            ?>

        </div>

    <?php endif; ?>


    <!-- ORDER SUMMARY -->

    <div class="order-summary">

        <h3>Order Summary</h3>

        <?php foreach ($cartProducts as $product): ?>

            <p>

                <?php echo htmlspecialchars($product['name']); ?>

                × <?php echo $product['quantity']; ?>

                — ₹<?php echo number_format($product['subtotal'], 2); ?>

            </p>

            <?php if ($product['quantity'] > $product['stock']): ?>

                <p style="color:red;">
                    Insufficient stock for this product.
                </p>

            <?php endif; ?>

        <?php endforeach; ?>

        <hr>

        <h3>
            Total Amount: ₹<?php echo number_format($total, 2); ?>
        </h3>

    </div>


    <!-- CUSTOMER DETAILS -->

    <h3>Customer Details</h3>

    <?php if (isset($_SESSION['user'])): ?>

        <p style="color:green;">
            Logged in as
            <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
        </p>

    <?php else: ?>

        <p>
            Checking out as Guest.
            <a href="login.php">Login</a>
            or
            <a href="register.php">Register</a>
            if you have an account.
        </p>

    <?php endif; ?>


    <form action="process_order.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
        >


        <label>Full Name</label>

        <input
            type="text"
            name="customer_name"
            placeholder="Enter your full name"
            maxlength="255"
            value="<?php echo isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['name']) : ''; ?>"
            required
        >


        <label>Email Address</label>

        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            maxlength="255"
            value="<?php echo isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['email']) : ''; ?>"
            required
        >


        <label>Phone Number</label>

        <input
            type="tel"
            name="phone"
            placeholder="Enter your 10-digit phone number"
            pattern="[0-9]{10}"
            maxlength="10"
            required
        >


        <label>Delivery Address</label>

        <textarea
            name="address"
            placeholder="Enter your complete delivery address"
            maxlength="2000"
            required
        ></textarea>


        <label>City</label>

        <input
            type="text"
            name="city"
            placeholder="Enter your city"
            maxlength="100"
            required
        >


        <label>PIN Code</label>

        <input
            type="text"
            name="pincode"
            placeholder="Enter your 6-digit PIN code"
            pattern="[0-9]{6}"
            maxlength="6"
            required
        >


        <!-- PAYMENT METHODS -->

        <h3>Select Payment Method</h3>


        <label class="payment-option">

            <input
                type="radio"
                name="payment_method"
                value="COD"
                required
            >

            Cash on Delivery

        </label>


        <label class="payment-option">

            <input
                type="radio"
                name="payment_method"
                value="UPI Demo"
            >

            UPI (Demo)

        </label>


        <label class="payment-option">

            <input
                type="radio"
                name="payment_method"
                value="Card Demo"
            >

            Debit / Credit Card (Demo)

        </label>


        <p class="demo-note">

            UPI and Card are demonstration options.
            No actual payment will be processed.

        </p>


        <br>

        <button type="submit" class="place-order-btn">

            Place Order

        </button>

    </form>

</div>

</body>
</html>
