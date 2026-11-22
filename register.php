<?php

require_once 'includes/auth.php';
include 'includes/connect.php';

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid name and email.";
    } elseif (strlen($name) > 100 || strlen($email) > 255) {
        $error = "Name or email is too long.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {

            $error = "This email is already registered.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt2 = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt2,
                "sss",
                $name,
                $email,
                $hashed_password
            );

            if (mysqli_stmt_execute($stmt2)) {

                mysqli_stmt_close($stmt2);

                header("Location: login.php?registered=1");
                exit;

            } else {
                $error = "Registration failed. Please try again.";
                mysqli_stmt_close($stmt2);
            }
        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | Hidden Store</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .auth-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
        }

        .auth-container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            box-sizing: border-box;
        }

        .auth-container button {
            width: 100%;
            padding: 12px;
            background: #222;
            color: white;
            border: none;
            cursor: pointer;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>

<?php include 'includes/header.php'; ?>

<div class="auth-container">

    <h2>Create Your Account</h2>

    <?php if ($error): ?>
        <p class="error">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Full Name</label>

        <input
            type="text"
            name="name"
            maxlength="100"
            required
        >

        <label>Email Address</label>

        <input
            type="email"
            name="email"
            maxlength="255"
            required
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            minlength="8"
            required
        >

        <label>Confirm Password</label>

        <input
            type="password"
            name="confirm_password"
            minlength="8"
            required
        >

        <button type="submit">
            Register
        </button>

    </form>

    <p>
        Already have an account?
        <a href="login.php">Login here</a>
    </p>

</div>

</body>
</html>