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

            <a href="my_orders/my_order.php">My Orders</a>

            |

            <a href="logout.php">Logout</a>

        <?php else: ?>

            <p>Welcome, Guest!</p>

            <a href="login.php">Login</a>

            |

            <a href="register.php">Register</a>

        <?php endif; ?>

        |

        <a href="cart.php">
            🛒 Cart
        </a>

    </div>
</header>