<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/connect.php';

$message = '';
$message_type = '';

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $message = "Please enter a category name.";
        $message_type = "danger";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO categories (name, description) VALUES (?, ?)"
        );

        mysqli_stmt_bind_param($stmt, "ss", $name, $description);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Category added successfully!";
            $message_type = "success";
        } else {
            $message = "Category already exists or could not be added.";
            $message_type = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

// Delete category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {

    $category_id = (int)($_POST['category_id'] ?? 0);

    $check = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM products WHERE category_id = ?"
    );

    mysqli_stmt_bind_param($check, "i", $category_id);
    mysqli_stmt_execute($check);

    $check_result = mysqli_stmt_get_result($check);
    $row = mysqli_fetch_assoc($check_result);

    mysqli_stmt_close($check);

    if ($row['total'] > 0) {
        $message = "This category cannot be deleted because products are assigned to it.";
        $message_type = "danger";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM categories WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $category_id);

        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            $message = "Category deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Category could not be deleted.";
            $message_type = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

// Fetch categories
$sql = "
    SELECT
        c.*,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.id ASC
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Categories | Hidden Store Admin</title>

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
        <h2>Manage Categories</h2>
        <p class="text-muted">
            Add, view and manage your store categories.
        </p>
    </div>

    <?php if ($message !== ''): ?>

        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <!-- Add Category -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="mb-3">Add New Category</h5>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Category Name</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="Enter category name"
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
                        placeholder="Enter category description"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    name="add_category"
                    class="btn btn-primary"
                >
                    + Add Category
                </button>

            </form>

        </div>

    </div>

    <!-- Categories List -->

    <div class="card shadow-sm">

        <div class="card-body">

            <h5 class="mb-3">All Categories</h5>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (mysqli_num_rows($result) > 0): ?>

                            <?php while ($category = mysqli_fetch_assoc($result)): ?>

                                <tr>

                                    <td>
                                        <?php echo (int)$category['id']; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($category['description'] ?? ''); ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo (int)$category['product_count']; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($category['created_at']); ?>
                                    </td>

                                    <td>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this category?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="category_id"
                                                value="<?php echo (int)$category['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_category"
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
                                    No categories found.
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