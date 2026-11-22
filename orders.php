<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/connect.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = "";
$error = "";

// Allowed statuses
$payment_statuses = [
    "Pending",
    "Paid",
    "Failed",
    "Refunded"
];

$order_statuses = [
    "Placed",
    "Confirmed",
    "Processing",
    "Shipped",
    "Out for Delivery",
    "Delivered",
    "Completed",
    "Cancelled"
];

// Update or delete order
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $action = $_POST['action'] ?? '';
        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

        if (!$order_id || $order_id <= 0) {
            $error = "Invalid order ID.";
        }

        // Update order
        elseif ($action === "update") {

            $payment_status = $_POST['payment_status'] ?? '';
            $order_status = $_POST['order_status'] ?? '';

            if (
                !in_array($payment_status, $payment_statuses, true) ||
                !in_array($order_status, $order_statuses, true)
            ) {
                $error = "Invalid status selected.";
            } else {

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE orders
                     SET payment_status = ?, order_status = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssi",
                    $payment_status,
                    $order_status,
                    $order_id
                );

                if (mysqli_stmt_execute($stmt)) {
                    $success = "Order #$order_id updated successfully!";
                } else {
                    $error = "Unable to update order.";
                }

                mysqli_stmt_close($stmt);
            }
        }

        // Delete order
        elseif ($action === "delete") {

            mysqli_begin_transaction($conn);

            try {

                // Delete order items first
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM order_items WHERE order_id = ?"
                );

                mysqli_stmt_bind_param($stmt, "i", $order_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Delete order
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM orders WHERE id = ?"
                );

                mysqli_stmt_bind_param($stmt, "i", $order_id);
                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    mysqli_commit($conn);
                    $success = "Order #$order_id deleted successfully!";
                } else {
                    throw new Exception("Order not found.");
                }

                mysqli_stmt_close($stmt);

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Unable to delete order.";
            }
        }
    }
}

// Fetch all orders
$sql = "SELECT * FROM orders ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - Hidden Store</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        .back-btn {
            display: inline-block;
            background: #333;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .order-card {
            background: white;
            padding: 22px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 12px;
        }

        .order-details {
            line-height: 1.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #eee;
        }

        select {
            padding: 9px;
            width: 100%;
            margin: 6px 0 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .update-btn {
            background: #198754;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            cursor: pointer;
        }

        .update-btn:hover {
            background: #146c43;
        }

        .delete-btn:hover {
            background: #bb2d3b;
        }

        .status-form {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        @media(max-width: 600px) {
            body {
                padding: 10px;
            }

            .order-card {
                padding: 12px;
                overflow-x: auto;
            }

            table {
                min-width: 450px;
            }
        }
    </style>

</head>

<body>

<div class="container">

    <h1>Manage Customer Orders</h1>

    <a href="dashboard.php" class="back-btn">
        ← Back to Dashboard
    </a>

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

    <?php if ($result && mysqli_num_rows($result) > 0): ?>

        <?php while ($order = mysqli_fetch_assoc($result)): ?>

            <div class="order-card">

                <div class="order-header">

                    <h2>
                        Order #<?php echo $order['id']; ?>
                    </h2>

                    <strong>
                        Total: ₹<?php echo number_format($order['total'], 2); ?>
                    </strong>

                </div>

                <div class="order-details">

                    <p>
                        <strong>Customer:</strong>
                        <?php echo htmlspecialchars($order['customer_name']); ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?php echo htmlspecialchars($order['email']); ?>
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        <?php echo htmlspecialchars($order['phone']); ?>
                    </p>

                    <p>
                        <strong>Address:</strong>
                        <?php echo htmlspecialchars($order['address']); ?>,
                        <?php echo htmlspecialchars($order['city']); ?> -
                        <?php echo htmlspecialchars($order['pincode']); ?>
                    </p>

                    <p>
                        <strong>Order Date:</strong>
                        <?php echo htmlspecialchars($order['created_at']); ?>
                    </p>

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

                </div>

                <h3>Ordered Products</h3>

                <table>

                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>

                    <?php

                    $order_id = (int)$order['id'];

                    $item_stmt = mysqli_prepare(
                        $conn,
                        "SELECT product_name, price, quantity, subtotal
                         FROM order_items
                         WHERE order_id = ?"
                    );

                    mysqli_stmt_bind_param($item_stmt, "i", $order_id);
                    mysqli_stmt_execute($item_stmt);

                    $items = mysqli_stmt_get_result($item_stmt);

                    ?>

                    <?php while ($item = mysqli_fetch_assoc($items)): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format($item['price'], 2); ?>
                            </td>

                            <td>
                                <?php echo (int)$item['quantity']; ?>
                            </td>

                            <td>
                                ₹<?php echo number_format($item['subtotal'], 2); ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    <?php mysqli_stmt_close($item_stmt); ?>

                </table>

                <div class="status-form">

                    <h3>Update Order Status</h3>

                    <form method="POST">

                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <input type="hidden" name="order_id"
                               value="<?php echo $order['id']; ?>">

                        <input type="hidden" name="action" value="update">

                        <label>
                            <strong>Payment Status</strong>
                        </label>

                        <select name="payment_status">

                            <?php foreach ($payment_statuses as $status): ?>

                                <option value="<?php echo $status; ?>"
                                    <?php echo ($order['payment_status'] === $status) ? 'selected' : ''; ?>>

                                    <?php echo $status; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <label>
                            <strong>Order Status</strong>
                        </label>

                        <select name="order_status">

                            <?php foreach ($order_statuses as $status): ?>

                                <option value="<?php echo $status; ?>"
                                    <?php echo ($order['order_status'] === $status) ? 'selected' : ''; ?>>

                                    <?php echo $status; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <button type="submit" class="update-btn">
                            Update Order
                        </button>

                    </form>

                    <br>

                    <form method="POST"
                          onsubmit="return confirm('Are you sure you want to delete Order #<?php echo $order['id']; ?>? This cannot be undone.');">

                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <input type="hidden" name="order_id"
                               value="<?php echo $order['id']; ?>">

                        <input type="hidden" name="action" value="delete">

                        <button type="submit" class="delete-btn">
                            Delete Order
                        </button>

                    </form>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <h3>No orders found.</h3>

    <?php endif; ?>

</div>

</body>
</html>