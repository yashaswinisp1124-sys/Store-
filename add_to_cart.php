<?php

session_start();

include 'includes/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$product_id = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT
);

if (!$product_id) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, stock FROM products WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$current_quantity = $_SESSION['cart'][$product_id] ?? 0;

if ($current_quantity < $product['stock']) {

    $_SESSION['cart'][$product_id] = $current_quantity + 1;

}

header("Location: cart.php");
exit;

?>