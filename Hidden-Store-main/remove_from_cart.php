<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    if ($id && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }

}

header("Location: cart.php");
exit;

?>