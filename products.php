
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/connect.php';

// Search functionality
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT 
        p.*,
        b.name AS brand_name,
        c.name AS category_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
";

if ($search !== '') {

    $search_words = strtolower($search);

    $category_synonyms = [
        'clothing' => ['clothing', 'clothes', 'apparel', 'wear'],
        'footwear' => ['footwear', 'shoes', 'shoe', 'sneakers'],
        'bags' => ['bags', 'bag', 'backpacks', 'backpack'],
        'accessories' => ['accessories', 'accessory']
    ];

    $matched_categories = [];

    foreach ($category_synonyms as $category => $synonyms) {
        if (in_array($search_words, $synonyms, true)) {
            $matched_categories[] = $category;
        }
    }

    if (!empty($matched_categories)) {
        $placeholders = implode(',', array_fill(0, count($matched_categories), '?'));

        $sql .= " WHERE c.name IN ($placeholders)
                  OR p.name LIKE ?
                  OR p.description LIKE ?
                  OR p.keywords LIKE ?
                  OR b.name LIKE ?";
    } else {
        $sql .= " WHERE p.name LIKE ?
                  OR p.description LIKE ?
                  OR p.keywords LIKE ?
                  OR b.name LIKE ?
                  OR c.name LIKE ?";
    }
}
$sql .= " ORDER BY p.id ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($search !== '') {
    $keyword = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Products | Hidden Store Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
        }

        .page-container {
            margin-top: 35px;
        }

        .product-image {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
        }

        .table th {
            white-space: nowrap;
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
                Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            </span>

            <a href="logout.php" class="btn btn-outline-light btn-sm">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container page-container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Manage Products</h2>
            <p class="text-muted">View and manage your store products.</p>
        </div>

        <a href="add_product.php" class="btn btn-primary">
            + Add New Product
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="GET" class="row g-2 mb-4">

                <div class="col-md-9">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search products by name, description or keywords..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        Search
                    </button>

                    <a href="products.php" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (mysqli_num_rows($result) > 0): ?>

                        <?php while ($product = mysqli_fetch_assoc($result)): ?>

                            <tr>
                                <td>
                                    <?php echo (int)$product['id']; ?>
                                </td>

                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img
                                            src="../images/<?php echo rawurlencode(basename($product['image'])); ?>"
                                            class="product-image"
                                            alt="Product"
                                        >
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($product['brand_name'] ?? 'Not assigned'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Not assigned'); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format((float)$product['price'], 2); ?>
                                </td>

                                <td>
                                    <?php echo (int)$product['stock']; ?>
                                </td>

                                <td>
                                    <a
                                        href="edit_product.php?id=<?php echo (int)$product['id']; ?>"
                                        class="btn btn-sm btn-warning"
                                    >
                                        Edit
                                    </a>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-4">
                                No products found.
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
