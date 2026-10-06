<?php
/* =========================================================
   CUSTOMER HEADER
   Furnishop
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../Config/database.php";

$pageTitle = $pageTitle ?? "Furnishop";

$isLoggedIn = isset($_SESSION["user_id"]);

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$cartCount = 0;
foreach ($_SESSION["cart"] as $qty) {
    $cartCount += (int) $qty;
}

$navCategories = [];

$navCatResult = mysqli_query(
    $connect,
    "SELECT id, name FROM categories WHERE status = 'Active' ORDER BY name ASC LIMIT 3"
);

if ($navCatResult) {
    while ($row = mysqli_fetch_assoc($navCatResult)) {
        $navCategories[] = $row;
    }
}

if (!function_exists("e")) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="">
    <link rel="icon" type="image/x-icon" href="../Assets/Images/favicon.ico">
    <link rel="stylesheet" href="../Assets/CSS/bootstrap.min.css">
    <link rel="stylesheet" href="../Assets/Font/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/nice-select/nice-select.css">
    <link rel="stylesheet" href="../Assets/Plugin/OwlCarousel2-2.3.4/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/OwlCarousel2-2.3.4/dist/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/nouislider/nouislider.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/slick/slick.css">
    <link rel="stylesheet" href="../Assets/CSS/style.css">

    <!-- ✨ PREMIUM CUSTOM STYLES -->
    <link rel="stylesheet" href="../CSS/custom.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Header Section Start -->
<header>
    <div class="container py-lg-2 mt-0 mt-lg-2">
        <div class="row">

            <!-- LOGO COLUMN -->
            <div class="col-12 col-sm-12 col-md-12 col-lg-2 mb-2 mb-lg-3 pt-3 pt-lg-2">
                <div class="row">
                    <div class="col-12 d-flex justify-content-center mb-3 mb-lg-0">
                        <a class="navbar-brand flex-shrink-0 py-0 py-lg-0" href="index.php">
                            <img src="../Assets/Images/logo.png" class="logo main-logo" alt="Furnishop">
                        </a>
                    </div>

                    <div class="col-12">
                        <div class="list-inline d-lg-none d-flex justify-content-between">

                            <div class="list-inline-item d-inline-block d-lg-none">
                                <button class="navbar-toggler border-0 collapsed" type="button"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#navbar-default"
                                        aria-controls="navbar-default"
                                        aria-label="Toggle navigation">
                                    <i class="bi bi-text-indent-left"></i>
                                </button>
                            </div>

                            <div>
                                <div class="list-inline-item me-4">
                                    <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"
                                       class="text-muted d-flex flex-column justity-content-center align-items-center">
                                        <i class="bi bi-person"></i>
                                        <span class="d-none d-sm-none d-md-none d-lg-block">Account</span>
                                    </a>
                                </div>
                                <div class="list-inline-item me-4">
                                    <a href="cart.php"
                                       class="text-muted d-flex flex-column justity-content-center align-items-center">
                                        <div class="position-relative">
                                            <i class="bi bi-cart"></i>
                                            <?php if ($cartCount > 0): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                    <?= $cartCount ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="d-none d-sm-none d-md-none d-lg-block">Your cart</span>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- NAV COLUMN -->
            <div class="col-12 col-sm-12 col-md-12 col-lg-7">
                <nav class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2" aria-label="Offcanvas navbar large">
                    <div class="container">
                        <div class="offcanvas offcanvas-start pt-2" tabindex="-1" id="navbar-default" aria-labelledby="navbar-defaultLabel">

                            <div class="offcanvas-header pb-1">
                                <a href="index.php">
                                    <img src="../Assets/Images/logo.png" alt="Furnishop">
                                </a>
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                            </div>

                            <div class="offcanvas-body">
                                <div class="d-block d-lg-none mb-4">
                                    <form action="products.php" method="GET">
                                        <div class="input-group">
                                            <input class="form-control" type="search" name="search" placeholder="Search for products">
                                            <span class="input-group-append">
                                                <button class="btn bg-white border border-start-0 ms-n10 rounded-0 rounded-end" type="submit">
                                                    <span class="bi bi-search"></span>
                                                </button>
                                            </span>
                                        </div>
                                    </form>
                                </div>

                                <div class="mx-auto">
                                    <ul class="navbar-nav align-items-center ms-lg-5">

                                        <li class="nav-item dropdown w-100 w-lg-auto">
                                            <a class="nav-link" href="index.php">Home</a>
                                        </li>

                                        <li class="nav-item dropdown w-100 w-lg-auto">
                                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                               data-bs-toggle="dropdown" aria-expanded="false">
                                                Shop
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="products.php">All Products</a></li>
                                                <li><a class="dropdown-item" href="cart.php">Cart</a></li>
                                                <li><a class="dropdown-item" href="checkout.php">Checkout</a></li>
                                            </ul>
                                        </li>

                                        <li class="nav-item dropdown w-100 w-lg-auto dropdown-fullwidth">
                                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                               data-bs-toggle="dropdown" aria-expanded="false">
                                                Categories
                                            </a>
                                            <div class="dropdown-menu pb-0">
                                                <div class="row p-2 p-lg-4">
                                                    <?php if (!empty($navCategories)): ?>
                                                        <?php foreach ($navCategories as $cat): ?>
                                                            <div class="col-lg-4 col-12 mb-4 mb-lg-0">
                                                                <h6 class="text-primary ps-3"><?= e($cat["name"]) ?></h6>
                                                                <a class="dropdown-item" href="products.php?category=<?= (int) $cat["id"] ?>">
                                                                    Browse <?= e($cat["name"]) ?>
                                                                </a>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="col-12">
                                                            <p class="text-muted mb-0 ps-3">No categories yet.</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </li>

                                        <li class="nav-item dropdown w-100 w-lg-auto">
                                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                               data-bs-toggle="dropdown" aria-expanded="false">
                                                Account
                                            </a>
                                            <ul class="dropdown-menu">
                                                <?php if ($isLoggedIn): ?>
                                                    <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                                    <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                                <?php else: ?>
                                                    <li><a class="dropdown-item" href="login.php">Sign in</a></li>
                                                    <li><a class="dropdown-item" href="register.php">Signup</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </li>

                                    </ul>
                                </div>
                            </div>

                        </div>
                    </div>
                </nav>
            </div>

            <!-- ACTIONS COLUMN -->
            <div class="col-12 col-sm-12 col-md-12 col-lg-3 d-none d-lg-block">

                <div class="offcanvas offcanvas-top" tabindex="-1" id="offcanvasTop" aria-labelledby="offcanvasTopLabel">
                    <div class="offcanvas-header">
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <div class="mt-50">
                            <form action="products.php" method="GET">
                                <div class="input-group">
                                    <input class="form-control py-3" type="search" name="search" placeholder="Search for products">
                                    <span class="input-group-append">
                                        <button class="btn bg-white border border-start-0 ms-n10 py-3 rounded-0 rounded-end" type="submit">
                                            <span class="bi bi-search"></span>
                                        </button>
                                    </span>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="list-inline d-flex pt-2">

                    <div class="list-inline-item me-4">
                        <a href="products.php" data-bs-toggle="offcanvas" data-bs-target="#offcanvasTop"
                           class="text-muted d-flex flex-column justity-content-center align-items-center">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-sm-none d-md-none d-lg-block">Search</span>
                        </a>
                    </div>

                    <div class="list-inline-item me-4">
                        <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"
                           class="text-muted d-flex flex-column justity-content-center align-items-center">
                            <i class="bi bi-person"></i>
                            <span class="d-none d-sm-none d-md-none d-lg-block">Account</span>
                        </a>
                    </div>

                    <div class="list-inline-item me-4">
                        <a href="cart.php"
                           class="text-muted d-flex flex-column justity-content-center align-items-center">
                            <div class="position-relative">
                                <i class="bi bi-cart"></i>
                                <?php if ($cartCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                        <?= $cartCount ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="d-none d-sm-none d-md-none d-lg-block">Your cart</span>
                        </a>
                    </div>

                </div>

            </div>

        </div>
    </div>
</header>
<!-- Header Section End -->