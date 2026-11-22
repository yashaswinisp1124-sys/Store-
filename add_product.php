<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/connect.php';

$error = "";

// Fetch brands
$brands = mysqli_query($conn, "SELECT * FROM brands ORDER BY name ASC");

// Fetch categories
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $keywords = trim($_POST['keywords'] ?? '');

    $image_name = null;

    if ($name === '' || !is_numeric($price) || $price < 0 ||
        filter_var($stock, FILTER_VALIDATE_INT) === false || $stock < 0) {

        $error = "Please enter valid product details.";

    } else {

        // Validate uploaded image
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = "Image upload failed.";

            } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
                $error = "Image size must be less than 3 MB.";

            } else {

                $allowed_types = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                $file_info = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $file_info->file($_FILES['image']['tmp_name']);

                if (!isset($allowed_types[$mime_type])) {
                    $error = "Only JPG, PNG and WEBP images are allowed.";

                } else {

                    $extension = $allowed_types[$mime_type];

                    $upload_directory = __DIR__ . '/../images/';

                    if (!is_dir($upload_directory)) {
                        mkdir($upload_directory, 0755, true);
                    }

                    $image_name = 'product_' . bin2hex(random_bytes(8)) . '.' . $extension;

                    if (!move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $upload_directory . $image_name
                    )) {
                        $error = "Unable to save the uploaded image.";
                    }
                }
            }
        }

        if ($error === "") {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO products
                (name, description, price, image, stock, brand_id, category_id, keywords)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssdsiiis",
                $name,
                $description,
                $price,
                $image_name,
                $stock,
                $brand_id,
                $category_id,
                $keywords
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: products.php?added=1");
                exit;
            } else {
                $error = "Unable to add product. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product | Hidden Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
        }

        .form-container {
            max-width: 750px;
            margin: 40px auto;
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
        <a href="dashboard.php" class="navbar-brand">
            HIDDEN STORE ADMIN
        </a>

        <a href="logout.php" class="btn btn-outline-light btn-sm">
            Logout
        </a>
    </div>
</nav>

<div class="container">

    <div class="form-container">

        <div class="card shadow-sm">

            <div class="card-body p-4">

                <h2 class="mb-2">Add New Product</h2>

                <p class="text-muted mb-4">
                    Enter the details of your new product.
                </p>

                <?php if ($error !== ""): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="3"
                        ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₹) *</label>

                            <input
                                type="number"
                                name="price"
                                class="form-control"
                                min="0"
                                step="0.01"
                                value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                                required
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock Quantity *</label>

                            <input
                                type="number"
                                name="stock"
                                class="form-control"
                                min="0"
                                value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>"
                                required
                            >
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand</label>

                            <select name="brand_id" class="form-select">

                                <option value="">Select Brand</option>

                                <?php while ($brand = mysqli_fetch_assoc($brands)): ?>

                                    <option
                                        value="<?php echo $brand['id']; ?>"
                                        <?php echo (($_POST['brand_id'] ?? '') == $brand['id']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>

                                <?php endwhile; ?>

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>

                            <select name="category_id" class="form-select">

                                <option value="">Select Category</option>

                                <?php while ($category = mysqli_fetch_assoc($categories)): ?>

                                    <option
                                        value="<?php echo $category['id']; ?>"
                                        <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>

                                <?php endwhile; ?>

                            </select>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Keywords</label>

                        <input
                            type="text"
                            name="keywords"
                            class="form-control"
                            placeholder="Example: cotton, casual, summer, men"
                            value="<?php echo htmlspecialchars($_POST['keywords'] ?? ''); ?>"
                        >

                        <small class="text-muted">
                            Separate keywords using commas. These help customers find products through search.
                        </small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Product Image</label>

                        <input
                            type="file"
                            name="image"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <small class="text-muted">
                            JPG, PNG or WEBP. Maximum size: 3 MB.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Add Product
                    </button>

                    <a href="products.php" class="btn btn-secondary">
                        Cancel
                    </a>

                </form>

            </div>
        </div>

    </div>

</div>

</body>
</html>