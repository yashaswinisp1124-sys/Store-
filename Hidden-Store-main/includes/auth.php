<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loggedInUser = $_SESSION['user'] ?? null;

?>