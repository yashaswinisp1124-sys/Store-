
<?php

require_once 'includes/auth.php';
include 'includes/connect.php';


// Redirect errors back to checkout
function checkoutError($message) {

    $_SESSION['checkout_error'] = $message;

    header("Location: checkout.php");
    exit;

}


// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;

}


// Validate CSRF token
if (
    empty($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    checkoutError("Invalid request. Please try again.");

}


// Check cart
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {

    header("Location: cart.php");
    exit;

}


// Get customer details
$name = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$pincode = trim($_POST['pincode'] ?? '');
$payment_method = $_POST['payment_method'] ?? '';


// Validate customer details
if (
    $name === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match('/^[0-9]{10}$/', $phone) ||
    $address === '' ||
    $city === '' ||
    !preg_match('/^[0-9]{6}$/', $pincode)
) {

    checkoutError("Please enter valid customer and delivery details.");

}


// Validate lengths
if (
    strlen($name) > 255 ||
    strlen($email) > 255 ||
    strlen($address) > 2000 ||
    strlen($city) > 100
) {

    checkoutError("One or more fields are too long.");

}


// Validate payment method
$allowedPayments = [
    'COD',
    'UPI Demo',
    'Card Demo'
];

if (!in_array($payment_method, $allowedPayments, true)) {

    checkoutError("Please select a valid payment method.");

}


// Get logged-in user ID or NULL for guest
$user_id = $_SESSION['user']['id'] ?? null;


// Start database transaction
try {

    mysqli_begin_transaction($conn);

    $total = 0;

    $order_products = [];


    // CHECK PRODUCTS AND STOCK

    foreach ($cart as $id => $quantity) {

        $id = filter_var($id, FILTER_VALIDATE_INT);

        $quantity = filter_var(
            $quantity,
            FILTER_VALIDATE_INT
        );

        if (!$id || !$quantity || $quantity < 1) {

            throw new Exception("Invalid cart item.");

        }


        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, price, stock
             FROM products
             WHERE id = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $product = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (!$product) {

            throw new Exception(
                "A product in your cart is unavailable."
            );

        }


        if ($quantity > $product['stock']) {

            throw new Exception(
                $product['name'] . " does not have enough stock."
            );

        }


        $subtotal = $product['price'] * $quantity;

        $total += $subtotal;


        $order_products[] = [

            'id' => $id,

            'name' => $product['name'],

            'price' => $product['price'],

            'quantity' => $quantity,

            'subtotal' => $subtotal

        ];

    }


    // PAYMENT STATUS

    if ($payment_method === 'COD') {

        $payment_status = 'Pending';

        $order_status = 'Placed';

    } else {

        $payment_status = 'Demo Only - Not Paid';

        $order_status = 'Demo Order';

    }


    // INSERT CUSTOMER ORDER

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO orders
        (
            user_id,
            customer_name,
            email,
            phone,
            address,
            city,
            pincode,
            payment_method,
            payment_status,
            order_status,
            total
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "isssssssssd",
        $user_id,
        $name,
        $email,
        $phone,
        $address,
        $city,
        $pincode,
        $payment_method,
        $payment_status,
        $order_status,
        $total
    );


    mysqli_stmt_execute($stmt);

    $order_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);


    // INSERT ORDER ITEMS AND UPDATE STOCK

    foreach ($order_products as $item) {


        // Save order item

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO order_items
            (
                order_id,
                product_id,
                product_name,
                price,
                quantity,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?, ?)"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "iisdid",
            $order_id,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity'],
            $item['subtotal']
        );


        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);


        // Reduce stock

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE products
             SET stock = stock - ?
             WHERE id = ? AND stock >= ?"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $item['quantity'],
            $item['id'],
            $item['quantity']
        );


        mysqli_stmt_execute($stmt);


        if (mysqli_stmt_affected_rows($stmt) !== 1) {

            mysqli_stmt_close($stmt);

            throw new Exception(
                "Unable to update product stock."
            );

        }


        mysqli_stmt_close($stmt);

    }


    // COMMIT TRANSACTION

    mysqli_commit($conn);


    // CLEAR CART

    $_SESSION['cart'] = [];

    $_SESSION['last_order_id'] = $order_id;

    unset($_SESSION['csrf_token']);


    // Redirect to success page

    header("Location: order_success.php");

    exit;


} catch (Throwable $e) {


    mysqli_rollback($conn);

    error_log(
        "Hidden Store Order Error: " . $e->getMessage()
    );


    if (
        str_contains(
            $e->getMessage(),
            "does not have enough stock"
        )
    ) {

        checkoutError($e->getMessage());

    }


    checkoutError(
        "Unable to place your order. Please try again."
    );

}

?>
