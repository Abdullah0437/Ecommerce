<?php
/* =========================================================
   CUSTOMER FOOTER
   Furnishop
========================================================= */

$isLoggedInFooter = isset($_SESSION["user_id"]);

$footerCategories = [];

if (isset($connect)) {
    $fcRes = mysqli_query(
        $connect,
        "SELECT id, name FROM categories WHERE status = 'Active' ORDER BY name ASC LIMIT 4"
    );

    if ($fcRes) {
        while ($row = mysqli_fetch_assoc($fcRes)) {
            $footerCategories[] = $row;
        }
    }
}
?>

<!-- footer Section -->
<footer class="mt-0">
    <div class="container">
        <div class="row">

            <div class="col-lg-4 mb-4 mb-md-0">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-12">
                        <div class="footer_logo">
                            <img loading="lazy" src="../Assets/Images/logo.png" class="logo" alt="Furnishop">
                        </div>
                        <div class="mt-4">
                            <p>Furnishop provides quality furniture and home products for every space.</p>
                            <h3 class="h5 fw-bold">+92 300 1234567</h3>
                            <p>support@furnishop.com</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3 mb-md-0">
                <div class="row">
                    <div class="col-6">
                        <div class="footer_menu">
                            <h4 class="footer_title">My Account</h4>
                            <ul class="m-0 p-0 list-unstyled">
                                <?php if (!$isLoggedInFooter): ?>
                                    <li><a href="login.php">Login</a></li>
                                    <li><a href="register.php">Register</a></li>
                                <?php else: ?>
                                    <li><a href="profile.php">My Profile</a></li>
                                    <li><a href="orders.php">My Orders</a></li>
                                    <li><a href="logout.php">Logout</a></li>
                                <?php endif; ?>
                                <li><a href="cart.php">Cart</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="footer_menu">
                            <h4 class="footer_title">Information</h4>
                            <ul class="m-0 p-0 list-unstyled">
                                <li><a href="#">About Us</a></li>
                                <li><a href="#">Return Policy</a></li>
                                <li><a href="#">Privacy Policy</a></li>
                                <li><a href="#">FAQ</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="row">
                    <div class="col-6">
                        <div class="footer_menu">
                            <h4 class="footer_title">Useful Links</h4>
                            <ul class="m-0 p-0 list-unstyled">
                                <li><a href="products.php">Products</a></li>
                                <li><a href="cart.php">Shopping Cart</a></li>
                                <li><a href="orders.php">My Orders</a></li>
                                <li><a href="#">Contact</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="footer_menu">
                            <h4 class="footer_title">Categories</h4>
                            <ul class="m-0 p-0 list-unstyled">
                                <?php if (!empty($footerCategories)): ?>
                                    <?php foreach ($footerCategories as $cat): ?>
                                        <li>
                                            <a href="products.php?category=<?= (int) $cat["id"] ?>">
                                                <?= htmlspecialchars($cat["name"]) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><a href="products.php">All Products</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="text-center py-3 mt-4 text-white px-3 copyright">
        <span>Copyright © <?= date("Y") ?>. All Rights Reserved. Furnishop.</span>
    </div>
</footer>

<script src="../Assets/JS/jquery-3.6.0.min.js"></script>
<script src="../Assets/JS/bootstrap.bundle.min.js"></script>
<script src="../Assets/Plugin/nice-select/jquery.nice-select.min.js"></script>
<script src="../Assets/Plugin/OwlCarousel2-2.3.4/dist/owl.carousel.min.js"></script>
<script src="../Assets/Plugin/nouislider/nouislider.min.js"></script>
<script src="../Assets/Plugin/slick/slick.min.js"></script>
<script src="../Assets/JS/main.js"></script>
</body>
</html>