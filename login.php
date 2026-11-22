
<?php
session_start();

require_once '../includes/connect.php';

// Already logged in?
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if (empty($_SESSION['admin_login_token'])) {
    $_SESSION['admin_login_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['admin_login_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please refresh the page.";
    } else {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === "" || $password === "") {
            $error = "Please enter your email and password.";
        } else {

            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, name, email, password FROM admins WHERE email = ? LIMIT 1"
            );

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);

            if ($admin && password_verify($password, $admin['password'])) {

                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];

                unset($_SESSION['admin_login_token']);

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "Invalid email or password.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | Hidden Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="row justify-content-center">

        <div class="col-md-5">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h2 class="text-center mb-3">
                        HIDDEN STORE
                    </h2>

                    <h4 class="text-center mb-4">
                        Admin Login
                    </h4>

                    <?php if (isset($_GET['created'])): ?>

                        <div class="alert alert-success">
                            Admin account created successfully! Please log in.
                        </div>

                    <?php endif; ?>

                    <?php if ($error !== ""): ?>

                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['admin_login_token']); ?>"
                        >

                        <div class="mb-3">

                            <label class="form-label">
                                Admin Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Enter admin email"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter password"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-dark w-100"
                        >
                            Login
                        </button>

                    </form>

                    <div class="text-center mt-3">

                        <a href="../index.php">
                            Back to Store
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
