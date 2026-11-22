
<?php

require_once 'includes/auth.php';
require_once 'includes/connect.php';

// Get selected filters
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$selected_brand = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;

// Fetch categories
$category_query = "SELECT * FROM categories ORDER BY name ASC";
$categories = mysqli_query($conn, $category_query);

// Fetch brands
$brand_query = "SELECT * FROM brands ORDER BY name ASC";
$brands = mysqli_query($conn, $brand_query);

// Fetch products with category and brand filters
// Fetch products with category and brand filters
$sql = "
    SELECT p.*, b.name AS brand_name, c.name AS category_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";

// CATEGORY FILTER WITH RELATED PRODUCT NAMES
if ($selected_category > 0) {

    $category_stmt = mysqli_prepare(
        $conn,
        "SELECT name FROM categories WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $category_stmt,
        "i",
        $selected_category
    );

    mysqli_stmt_execute($category_stmt);

    $category_result = mysqli_stmt_get_result($category_stmt);
    $category_data = mysqli_fetch_assoc($category_result);

    mysqli_stmt_close($category_stmt);

    if ($category_data) {

        $category_name = strtolower($category_data['name']);

        if (in_array($category_name, ['footwear', 'shoes'])) {

            $sql .= "
                AND (
                    p.category_id = $selected_category
                    OR LOWER(p.name) LIKE '%shoe%'
                    OR LOWER(p.name) LIKE '%sneaker%'
                )
            ";

        } elseif (in_array($category_name, ['clothing', 'apparel'])) {

            $sql .= "
                AND (
                    p.category_id = $selected_category
                    OR LOWER(p.name) LIKE '%shirt%'
                    OR LOWER(p.name) LIKE '%t-shirt%'
                    OR LOWER(p.name) LIKE '%tshirt%'
                    OR LOWER(p.name) LIKE '%hoodie%'
                    OR LOWER(p.name) LIKE '%jeans%'
                    OR LOWER(p.name) LIKE '%denim%'
                )
            ";

        } elseif (in_array($category_name, ['bags', 'luggage'])) {

            $sql .= "
                AND (
                    p.category_id = $selected_category
                    OR LOWER(p.name) LIKE '%bag%'
                    OR LOWER(p.name) LIKE '%backpack%'
                )
            ";

        } else {

            $sql .= " AND p.category_id = $selected_category";

        }
    }
}

// BRAND FILTER
if ($selected_brand > 0) {
    $sql .= " AND p.brand_id = $selected_brand";
}

$sql .= " ORDER BY p.id DESC";

$result = mysqli_query($conn, $sql);

// Calculate cart item count
$cart = $_SESSION['cart'] ?? [];
$cart_count = array_sum($cart);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hidden Store | Discover Your Style</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* HEADER NAVIGATION */

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .admin-login-btn {
            background: #ffc107;
            color: #171717 !important;
            padding: 9px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .admin-login-btn:hover {
            background: #e0a800;
        }

        /* PRODUCT HEADING AND FILTERS */

        .products-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .products-top h2 {
            margin: 0;
        }

        .filter-section {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-section select {
            padding: 11px 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: white;
            font-size: 14px;
            min-width: 155px;
            cursor: pointer;
        }

        .clear-filter {
            padding: 11px 14px;
            background: #222;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }

        .clear-filter:hover {
            background: #444;
        }

        .product-grid {
            margin-top: 15px;
        }

        .filter-message {
            width: 100%;
            padding: 25px;
            text-align: center;
            background: #fff;
            border-radius: 8px;
            color: #555;
        }

        @media(max-width: 600px) {

            .products-top {
                align-items: flex-start;
            }

            .filter-section {
                width: 100%;
            }

            .filter-section select {
                width: 100%;
            }

        }

    </style>

</head>

<body>

    <!-- HEADER -->

    <header>

        <h1>HIDDEN STORE</h1>

        <div class="user-section">

            <?php if (isset($_SESSION['user'])): ?>

                <p>
                    Welcome,
                    <strong>
                        <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                    </strong>!
                </p>

                <a href="logout.php">Logout</a>

                <a href="my_orders/my_order.php">My Orders</a>

            <?php else: ?>

                <p>Welcome, Guest!</p>

                <a href="login.php">Login</a>

                <a href="register.php">Register</a>

            <?php endif; ?>

            <a href="cart.php">
                🛒 Cart (<?php echo (int)$cart_count; ?>)
            </a>

            <a href="admin/login.php" class="admin-login-btn">
                Admin Login
            </a>

        </div>

    </header>


    <!-- HERO SECTION -->

    <section class="hero">

        <div class="hero-content">

            <h2>Discover Your Style</h2>

            <p>
                Explore our collection of stylish and comfortable
                everyday essentials.
            </p>

            <a href="#products" class="shop-btn">
                Shop Now
            </a>

        </div>

    </section>


    <!-- PRODUCTS SECTION -->

    <main id="products">

        <div class="products-top">

            <h2>Our Products</h2>

            <!-- CATEGORY AND BRAND FILTERS -->

            <form method="GET" action="index.php" class="filter-section">

                <select name="category" onchange="this.form.submit()">

                    <option value="0">All Categories</option>

                    <?php while ($category = mysqli_fetch_assoc($categories)): ?>

                        <option
                            value="<?php echo (int)$category['id']; ?>"
                            <?php echo ($selected_category === (int)$category['id']) ? 'selected' : ''; ?>
                        >

                            <?php echo htmlspecialchars($category['name']); ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <select name="brand" onchange="this.form.submit()">

                    <option value="0">All Brands</option>

                    <?php while ($brand = mysqli_fetch_assoc($brands)): ?>

                        <option
                            value="<?php echo (int)$brand['id']; ?>"
                            <?php echo ($selected_brand === (int)$brand['id']) ? 'selected' : ''; ?>
                        >

                            <?php echo htmlspecialchars($brand['name']); ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <noscript>
                    <button type="submit">Apply Filters</button>
                </noscript>

                <?php if ($selected_category > 0 || $selected_brand > 0): ?>

                    <a href="index.php" class="clear-filter">
                        Clear Filters
                    </a>

                <?php endif; ?>

            </form>

        </div>


        <!-- PRODUCT GRID -->

        <div class="product-grid">

            <?php if ($result && mysqli_num_rows($result) > 0): ?>

                <?php while ($product = mysqli_fetch_assoc($result)): ?>

                    <div class="product-card">

                        <!-- PRODUCT IMAGE -->

                        <?php

                        $imageName = basename($product['image'] ?? '');
                        $imagePath = 'images/' . $imageName;

                        ?>

                        <?php if (
                            $imageName !== '' &&
                            is_file(__DIR__ . '/' . $imagePath)
                        ): ?>

                            <img
                                src="<?php echo htmlspecialchars($imagePath); ?>"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                            >

                        <?php else: ?>

                            <div class="product-placeholder">
                                <span>No Image Available</span>
                            </div>

                        <?php endif; ?>


                        <!-- PRODUCT DETAILS -->

                        <div class="product-info">

                            <h3>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                            </p>

                            <p>
                                <strong>Brand:</strong>
                                <?php echo htmlspecialchars($product['brand_name'] ?? 'Not specified'); ?>
                            </p>

                            <p>
                                <strong>Category:</strong>
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Not specified'); ?>
                            </p>

                            <p class="price">
                                ₹<?php echo number_format((float)$product['price'], 2); ?>
                            </p>

                            <p class="stock">

                                <?php if ($product['stock'] > 0): ?>

                                    Stock: <?php echo (int)$product['stock']; ?>

                                <?php else: ?>

                                    <span style="color:red;">
                                        Out of Stock
                                    </span>

                                <?php endif; ?>

                            </p>


                            <!-- ADD TO CART -->

                            <form action="add_to_cart.php" method="POST">

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?php echo (int)$product['id']; ?>"
                                >

                                <button
                                    type="submit"
                                    <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>
                                >

                                    <?php
                                    echo ($product['stock'] > 0)
                                        ? 'Add to Cart'
                                        : 'Out of Stock';
                                    ?>

                                </button>

                            </form>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="filter-message">

                    <?php if ($selected_category > 0 && $selected_brand > 0): ?>

                        Sorry! No products are available for this category and brand combination.

                    <?php elseif ($selected_category > 0): ?>

                        No stock available in this category.

                    <?php elseif ($selected_brand > 0): ?>

                        Sorry! This brand currently has no products available for sale.

                    <?php else: ?>

                        No products available at the moment.

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </main>


    <!-- FOOTER -->

    <footer>

        <h3>HIDDEN STORE</h3>

        <p>Your style, your choice.</p>

        <p>
            &copy; <?php echo date('Y'); ?> Hidden Store.
            All Rights Reserved.
        </p>

    </footer>

</body>

</html>
