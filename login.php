<?php

require_once 'includes/auth.php';
include 'includes/connect.php';

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, email, password
         FROM users
         WHERE email = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];

        header("Location: index.php");
        exit;

    } else {

        $error = "Incorrect email or password.";

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Hidden Store</title>

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

        .success {
            color: green;
        }
    </style>

</head>

<body>

<?php include 'includes/header.php'; ?>

<div class="auth-container">

    <h2>Welcome Back!</h2>

    <?php if (isset($_GET['registered'])): ?>
        <p class="success">
            Registration successful! Please log in.
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Email Address</label>

        <input
            type="email"
            name="email"
            required
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

    <p>
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</div>

</body>
</html>