<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/connect.php';

$message = '';
$message_type = '';

// Add new brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $message = "Please enter a brand name.";
        $message_type = "danger";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO brands (name, description) VALUES (?, ?)"
        );

        mysqli_stmt_bind_param($stmt, "ss", $name, $description);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Brand added successfully!";
            $message_type = "success";
        } else {
            $message = "Brand already exists or could not be added.";
            $message_type = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

// Delete brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_brand'])) {

    $brand_id = (int)($_POST['brand_id'] ?? 0);

    // Check whether products are assigned to this brand
    $check = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM products WHERE brand_id = ?"
    );

    mysqli_stmt_bind_param($check, "i", $brand_id);
    mysqli_stmt_execute($check);

    $check_result = mysqli_stmt_get_result($check);
    $row = mysqli_fetch_assoc($check_result);

    mysqli_stmt_close($check);

    if ($row['total'] > 0) {
        $message = "This brand cannot be deleted because products are assigned to it.";
        $message_type = "danger";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM brands WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $brand_id);

        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            $message = "Brand deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Brand could not be deleted.";
            $message_type = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

// Fetch brands and product counts
$sql = "
    SELECT
        b.*,
        COUNT(p.id) AS product_count
    FROM brands b
    LEFT JOIN products p ON b.id = p.brand_id
    GROUP BY b.id
    ORDER BY b.id ASC
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Brands | Hidden Store Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
        }

        .page-container {
            margin-top: 35px;
        }

        .card {
            border: none;
            border-radius: 12px;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container">

        <a class="navbar-brand" href="dashboard.php">
            HIDDEN STORE ADMIN
        </a>

        <div class="d-flex align-items-center gap-3">

            <span class="text-white">
                Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
            </span>

            <a href="logout.php" class="btn btn-outline-light btn-sm">
                Logout
            </a>

        </div>
    </div>
</nav>

<div class="container page-container">

    <div class="mb-4">
        <h2>Manage Brands</h2>
        <p class="text-muted">
            Add, view and manage your store brands.
        </p>
    </div>

    <?php if ($message !== ''): ?>

        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <!-- Add Brand Form -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="mb-3">Add New Brand</h5>

            <form method="POST">

                <div class="mb-3">

                    <label class="form-label">Brand Name</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="Enter brand name"
                        maxlength="150"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">Description</label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="3"
                        placeholder="Enter brand description"
                    ></textarea>

                </div>

                <button
                    type="submit"
                    name="add_brand"
                    class="btn btn-primary"
                >
                    + Add Brand
                </button>

            </form>

        </div>

    </div>

    <!-- Brands List -->

    <div class="card shadow-sm">

        <div class="card-body">

            <h5 class="mb-3">All Brands</h5>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>ID</th>
                            <th>Brand Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (mysqli_num_rows($result) > 0): ?>

                            <?php while ($brand = mysqli_fetch_assoc($result)): ?>

                                <tr>

                                    <td>
                                        <?php echo (int)$brand['id']; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($brand['description'] ?? ''); ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo (int)$brand['product_count']; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($brand['created_at']); ?>
                                    </td>

                                    <td>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this brand?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="brand_id"
                                                value="<?php echo (int)$brand['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_brand"
                                                class="btn btn-sm btn-danger"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    No brands found.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <a href="dashboard.php" class="btn btn-secondary mt-3">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>