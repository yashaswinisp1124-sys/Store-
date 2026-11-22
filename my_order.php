<?php
require_once '../includes/auth.php';
require_once '../includes/connect.php';

// Only logged-in customers can view their orders
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = "";
$error = "";

// Handle customer actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";

    } else {

        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? '';

        if (!$order_id || $order_id <= 0) {
            $error = "Invalid order.";

        } else {

            // Check that the order belongs to this customer
            $check = mysqli_prepare(
                $conn,
                "SELECT * FROM orders WHERE id = ? AND user_id = ?"
            );

            mysqli_stmt_bind_param($check, "ii", $order_id, $user_id);
            mysqli_stmt_execute($check);

            $result = mysqli_stmt_get_result($check);
            $order = mysqli_fetch_assoc($result);

            mysqli_stmt_close($check);

            if (!$order) {
                $error = "Order not found.";

            } elseif ($action === "confirm") {

                // Confirm receipt
                if ($order['order_status'] === "Delivered") {

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE orders
                         SET order_status = 'Completed'
                         WHERE id = ? AND user_id = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);

                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Order confirmed successfully!";
                    } else {
                        $error = "Unable to confirm order.";
                    }

                    mysqli_stmt_close($stmt);

                } else {
                    $error = "You can confirm receipt only after delivery.";
                }

            } elseif ($action === "cancel") {

                // Cancel orders that haven't entered processing
                if (in_array($order['order_status'], ["Placed", "Confirmed"], true)) {

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE orders
                         SET order_status = 'Cancelled'
                         WHERE id = ? AND user_id = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);

                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Order cancelled successfully!";
                    } else {
                        $error = "Unable to cancel order.";
                    }

                    mysqli_stmt_close($stmt);

                } else {
                    $error = "This order can no longer be cancelled.";
                }

            } elseif ($action === "delete") {

                // Delete only cancelled orders
                if ($order['order_status'] === "Cancelled") {

                    mysqli_begin_transaction($conn);

                    try {

                        $stmt = mysqli_prepare(
                            $conn,
                            "DELETE FROM order_items WHERE order_id = ?"
                        );

                        mysqli_stmt_bind_param($stmt, "i", $order_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        $stmt = mysqli_prepare(
                            $conn,
                            "DELETE FROM orders
                             WHERE id = ? AND user_id = ?"
                        );

                        mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
                        mysqli_stmt_execute($stmt);

                        if (mysqli_stmt_affected_rows($stmt) > 0) {
                            mysqli_commit($conn);
                            $success = "Order deleted successfully!";
                        } else {
                            throw new Exception("Order not found.");
                        }

                        mysqli_stmt_close($stmt);

                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        $error = "Unable to delete order.";
                    }

                } else {
                    $error = "Only cancelled orders can be deleted.";
                }
            }
        }
    }
}

// Fetch logged-in customer's orders
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM orders
     WHERE user_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$orders = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Orders | Hidden Store</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f5f7;
    color: #222;
}

/* HEADER */

header {
    background: #171717;
    color: white;
    padding: 18px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

header h1 {
    margin: 0;
    font-size: 25px;
}

.user-section {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.user-section p {
    margin: 0;
}

.user-section a {
    color: white;
    text-decoration: none;
}

.user-section a:hover {
    color: #ffc107;
}

/* ADMIN LOGIN BUTTON */

.admin-login-btn {
    background: #ffc107;
    color: #171717 !important;
    padding: 9px 15px;
    border-radius: 6px;
    font-weight: bold;
    transition: 0.2s;
}

.admin-login-btn:hover {
    background: #e0a800;
    color: #171717 !important;
}

/* MAIN CONTAINER */

.orders-container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
}

.page-title {
    font-size: 30px;
    margin-bottom: 8px;
}

.subtitle {
    color: #777;
    margin-bottom: 30px;
}

/* MESSAGES */

.message {
    padding: 14px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.success {
    background: #d1e7dd;
    color: #0f5132;
}

.error {
    background: #f8d7da;
    color: #842029;
}

/* ORDER CARD */

.order-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.07);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.order-title {
    margin: 0 0 7px;
    font-size: 21px;
}

.order-date {
    color: #777;
    font-size: 14px;
}

/* STATUS BADGES */

.badge {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin: 3px;
}

.placed {
    background: #cfe2ff;
    color: #084298;
}

.confirmed {
    background: #cff4fc;
    color: #055160;
}

.processing {
    background: #fff3cd;
    color: #664d03;
}

.shipped {
    background: #e2d9f3;
    color: #432874;
}

.out-for-delivery {
    background: #ffe5d0;
    color: #984c0c;
}

.delivered {
    background: #d1e7dd;
    color: #0f5132;
}

.completed {
    background: #198754;
    color: white;
}

.cancelled {
    background: #f8d7da;
    color: #842029;
}

.payment {
    background: #e2e3e5;
    color: #41464b;
}

/* PRODUCT TABLE */

.section-title {
    margin-top: 22px;
    margin-bottom: 12px;
    font-size: 17px;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 500px;
}

th, td {
    padding: 13px;
    border: 1px solid #ddd;
    text-align: left;
}

th {
    background: #f0f0f0;
    font-size: 14px;
}

td {
    font-size: 14px;
}

/* ORDER INFORMATION */

.order-info {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.order-info p {
    line-height: 1.7;
}

.total {
    font-size: 22px;
    font-weight: bold;
}

/* BUTTONS */

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    display: inline-block;
    padding: 11px 17px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
}

.btn-confirm {
    background: #198754;
    color: white;
}

.btn-cancel {
    background: #ffc107;
    color: #222;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-shop {
    background: #222;
    color: white;
}

.btn:hover {
    opacity: 0.85;
}

/* EMPTY STATE */

.empty {
    background: white;
    padding: 45px 20px;
    border-radius: 10px;
    text-align: center;
}

/* RESPONSIVE */

@media(max-width: 600px) {

    header {
        padding: 15px;
    }

    .orders-container {
        margin: 25px auto;
        padding: 0 12px;
    }

    .order-card {
        padding: 17px;
    }

    .order-header {
        align-items: flex-start;
    }

    .order-info {
        display: block;
    }

}

</style>

</head>

<body>

<header>

    <h1>HIDDEN STORE</h1>

    <div class="user-section">

        <p>
            Welcome,
            <strong>
                <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
            </strong>!
        </p>

        <a href="/hidden_store/index.php">Home</a>

        <a href="/hidden_store/my_orders/my_order.php">My Orders</a>

        <a href="/hidden_store/cart.php">🛒 Cart</a>

        <a href="/hidden_store/logout.php">Logout</a>

        <!-- ADMIN LOGIN BUTTON -->
        <a href="/hidden_store/admin/login.php" class="admin-login-btn">
            Admin Login
        </a>

    </div>

</header>


<div class="orders-container">

    <h2 class="page-title">My Orders</h2>

    <p class="subtitle">
        View your order details, payment information and order status.
    </p>

    <?php if ($success): ?>
        <div class="message success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>


    <?php if (mysqli_num_rows($orders) > 0): ?>

        <?php while ($order = mysqli_fetch_assoc($orders)): ?>

            <?php

            // Display clean order status
            $status = $order['order_status'];

            if ($status === "Demo Order") {
                $status = "Placed";
            }

            // Display clean payment status
            $payment = $order['payment_status'];

            if ($payment === "Demo Only - Not Paid") {
                $payment = "Pending";
            }

            // Remove Demo from payment method label
            $payment_method = str_replace(" Demo", "", $order['payment_method']);

            $status_class = strtolower(
                str_replace(' ', '-', $status)
            );

            ?>

            <div class="order-card">

                <div class="order-header">

                    <div>

                        <h3 class="order-title">
                            Order #<?php echo (int)$order['id']; ?>
                        </h3>

                        <div class="order-date">
                            Ordered on:
                            <?php
                            echo date(
                                'd M Y, h:i A',
                                strtotime($order['created_at'])
                            );
                            ?>
                        </div>

                    </div>

                    <div>

                        <span class="badge <?php echo htmlspecialchars($status_class); ?>">
                            <?php echo htmlspecialchars($status); ?>
                        </span>

                        <span class="badge payment">
                            Payment: <?php echo htmlspecialchars($payment); ?>
                        </span>

                    </div>

                </div>

                <hr>

                <h4 class="section-title">Ordered Products</h4>

                <div class="table-wrapper">

                    <table>

                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php

                        $item_stmt = mysqli_prepare(
                            $conn,
                            "SELECT * FROM order_items WHERE order_id = ?"
                        );

                        mysqli_stmt_bind_param(
                            $item_stmt,
                            "i",
                            $order['id']
                        );

                        mysqli_stmt_execute($item_stmt);

                        $items = mysqli_stmt_get_result($item_stmt);

                        ?>

                        <?php while ($item = mysqli_fetch_assoc($items)): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format((float)$item['price'], 2); ?>
                                </td>

                                <td>
                                    <?php echo (int)$item['quantity']; ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format((float)$item['subtotal'], 2); ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

                <?php mysqli_stmt_close($item_stmt); ?>


                <div class="order-info">

                    <div>

                        <p>
                            <strong>Payment Method:</strong>
                            <?php echo htmlspecialchars($payment_method); ?>
                        </p>

                        <p>
                            <strong>Payment Status:</strong>
                            <?php echo htmlspecialchars($payment); ?>
                        </p>

                        <p>
                            <strong>Delivery Address:</strong><br>

                            <?php echo htmlspecialchars($order['address']); ?>,

                            <?php echo htmlspecialchars($order['city']); ?> -

                            <?php echo htmlspecialchars($order['pincode']); ?>
                        </p>

                    </div>

                    <div>

                        <p class="total">
                            Total:
                            ₹<?php echo number_format((float)$order['total'], 2); ?>
                        </p>

                    </div>

                </div>

                <hr>

                <div class="actions">

                    <?php if ($status === "Delivered"): ?>

                        <form method="POST"
                              onsubmit="return confirm('Confirm that you received this order?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <input type="hidden"
                                   name="order_id"
                                   value="<?php echo (int)$order['id']; ?>">

                            <input type="hidden"
                                   name="action"
                                   value="confirm">

                            <button type="submit" class="btn btn-confirm">
                                Confirm Received
                            </button>

                        </form>

                    <?php endif; ?>


                    <?php if (in_array($status, ["Placed", "Confirmed"], true)): ?>

                        <form method="POST"
                              onsubmit="return confirm('Are you sure you want to cancel this order?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <input type="hidden"
                                   name="order_id"
                                   value="<?php echo (int)$order['id']; ?>">

                            <input type="hidden"
                                   name="action"
                                   value="cancel">

                            <button type="submit" class="btn btn-cancel">
                                Cancel Order
                            </button>

                        </form>

                    <?php endif; ?>


                    <?php if ($status === "Cancelled"): ?>

                        <form method="POST"
                              onsubmit="return confirm('Permanently delete this cancelled order?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <input type="hidden"
                                   name="order_id"
                                   value="<?php echo (int)$order['id']; ?>">

                            <input type="hidden"
                                   name="action"
                                   value="delete">

                            <button type="submit" class="btn btn-delete">
                                Delete Order
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">

            <h3>No Orders Yet!</h3>

            <p>You haven't placed any orders with this account.</p>

            <a href="/hidden_store/index.php" class="btn btn-shop">
                Start Shopping
            </a>

        </div>

    <?php endif; ?>

    <a href="/hidden_store/index.php" class="btn btn-shop">
        ← Continue Shopping
    </a>

</div>

</body>
</html>