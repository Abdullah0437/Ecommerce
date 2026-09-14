<?php

session_start();

require_once __DIR__ . "/../Config/database.php";


/* =========================================================
   INITIALIZE CART
========================================================= */

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}


/* =========================================================
   INITIALIZE WISHLIST
========================================================= */

if (!isset($_SESSION["wishlist"]) || !is_array($_SESSION["wishlist"])) {
    $_SESSION["wishlist"] = [];
}


/* =========================================================
   HANDLE POST ACTIONS
========================================================= */

$flashMessage = "";
$flashType    = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* --- WISHLIST TOGGLE --- */
    if (isset($_POST["toggle_wishlist"])) {

        $productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

        if ($productId) {

            if (in_array($productId, $_SESSION["wishlist"], true)) {

                $_SESSION["wishlist"] = array_values(
                    array_diff($_SESSION["wishlist"], [$productId])
                );

            } else {

                $_SESSION["wishlist"][] = $productId;
            }
        }

        header("Location: index.php");
        exit;
    }


    /* --- ADD TO CART --- */
    if (isset($_POST["add_to_cart"])) {

        $productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

        if ($productId) {

            $stmt = mysqli_prepare(
                $connect,
                "SELECT id, stock_quantity
                 FROM products
                 WHERE id = ? AND status = 'Active'
                 LIMIT 1"
            );

            if ($stmt) {

                mysqli_stmt_bind_param($stmt, "i", $productId);
                mysqli_stmt_execute($stmt);

                $result  = mysqli_stmt_get_result($stmt);
                $product = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if ($product) {

                    $stock = (int) $product["stock_quantity"];

                    if ($stock > 0) {

                        $currentQty = isset($_SESSION["cart"][$productId])
                            ? (int) $_SESSION["cart"][$productId]
                            : 0;

                        $_SESSION["cart"][$productId] = min($currentQty + 1, $stock);

                        $flashMessage = "Product added to cart.";
                        $flashType    = "success";
                    }
                }
            }
        }

        header("Location: index.php");
        exit;
    }
}


/* =========================================================
   FETCH ACTIVE CATEGORIES
========================================================= */

$categories = [];

$categoryResult = mysqli_query(
    $connect,
    "SELECT id, name
     FROM categories
     WHERE status = 'Active'
     ORDER BY name ASC
     LIMIT 8"
);

if ($categoryResult) {
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row;
    }
}


/* =========================================================
   FETCH ACTIVE PRODUCTS
========================================================= */

$products = [];

$productResult = mysqli_query(
    $connect,
    "SELECT id, category_id, name, price, stock_quantity, image
     FROM products
     WHERE status = 'Active'
     ORDER BY id DESC"
);

if ($productResult) {
    while ($row = mysqli_fetch_assoc($productResult)) {
        $products[] = $row;
    }
}


/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;

foreach ($_SESSION["cart"] as $qty) {
    $cartCount += (int) $qty;
}


/* =========================================================
   PRODUCT BUCKETS FOR TABS
========================================================= */

$latestProducts = array_slice($products, 0, 8);

$featuredProducts = $products;

usort($featuredProducts, function ($a, $b) {
    return (float) $b["price"] <=> (float) $a["price"];
});

$featuredProducts = array_slice($featuredProducts, 0, 8);

$specialProducts = array_values(array_filter($products, function ($p) {
    return (int) $p["stock_quantity"] > 0
        && (int) $p["stock_quantity"] <= 5;
}));

if (empty($specialProducts)) {
    $specialProducts = array_slice($products, 0, 8);
}

$newArrivals = array_slice($products, 0, 8);


/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}


function productImage($image)
{
    if (empty($image)) {
        return "../assets/images/product/1.png";
    }

    if (strpos($image, "../") === 0) {
        return $image;
    }

    return "../Images/" . rawurlencode(basename($image));
}


function stockLabel($stock)
{
    $stock = (int) $stock;

    if ($stock <= 0) {
        return "Out of Stock";
    }

    if ($stock <= 5) {
        return "Only " . $stock . " left";
    }

    return "In Stock";
}


/* Renders product card in the SAME markup used in the file */
function renderProductCard($product)
{
    $id     = (int) $product["id"];
    $stock  = (int) $product["stock_quantity"];
    $image  = productImage($product["image"]);
    $inWish = in_array($id, $_SESSION["wishlist"] ?? [], true);
    ?>

    <div class="card product-card position-relative">

        <!-- WISHLIST HEART -->
        <form
            method="post"
            class="position-absolute"
            style="top:8px;right:15px;z-index:5;">

            <input type="hidden" name="product_id" value="<?= $id ?>">

            <button
                type="submit"
                name="toggle_wishlist"
                class="btn btn-light btn-sm rounded-circle shadow-sm border-0"
                title="<?= $inWish ? 'Remove from wishlist' : 'Add to wishlist' ?>"
                style="width:32px;height:32px;padding:0;line-height:1;">

                <i class="bi <?= $inWish ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>

            </button>

        </form>

        <a href="product-details.php?id=<?= $id ?>">
            <img
                src="<?= e($image) ?>"
                class="card-img-top image-first"
                alt="<?= e($product["name"]) ?>"
                onerror="this.onerror=null;this.src='../assets/images/product/1.png';">
        </a>

        <div class="card-body pt-0">

            <?php if ($stock <= 0): ?>
                <span class="discount-badge bg-danger text-white">Out of Stock</span>
            <?php elseif ($stock <= 5): ?>
                <span class="discount-badge">Low Stock</span>
            <?php endif; ?>

        </div>

        <div class="product-price px-3 pb-2">

            <h5 class="card-title">
                <a href="product-details.php?id=<?= $id ?>">
                    <?= e($product["name"]) ?>
                </a>
            </h5>

            <div class="mb-2">
                <small class="text-warning">
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-half"></i>
                </small>
            </div>

            <div class="d-block">
                <span class="sell-price">
                    Rs. <?= number_format((float) $product["price"], 2) ?>
                </span>
            </div>

            <div class="mt-1">
                <small class="<?= $stock > 0 ? 'text-success' : 'text-danger' ?>">
                    <?= e(stockLabel($stock)) ?>
                </small>
            </div>

        </div>

        <div class="d-block mb-2">

            <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">

                <?php if ($stock > 0): ?>

                    <form method="post" class="mb-2 mb-lg-0 w-100">

                        <input type="hidden" name="product_id" value="<?= $id ?>">

                        <button
                            type="submit"
                            name="add_to_cart"
                            class="btn btn-primary w-100">

                            Add to Cart

                        </button>

                    </form>

                <?php else: ?>

                    <button
                        type="button"
                        class="btn btn-primary w-100 mb-2 mb-lg-0"
                        disabled>

                        Out of Stock

                    </button>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <?php
}


function renderProductGrid($items)
{
    if (empty($items)) {

        echo '<div class="col-12">
                <div class="alert alert-light text-center mb-0">
                    No products available.
                </div>
              </div>';

        return;
    }

    foreach ($items as $product) {

        echo '<div class="col">';
        renderProductCard($product);
        echo '</div>';
    }
}


/* =========================================================
   LOGIN STATUS
========================================================= */

$isLoggedIn    = isset($_SESSION["user_id"]);
$wishlistCount = count($_SESSION["wishlist"]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furnishop</title>
    <meta name="description" content="" >
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
     <!-- font-awesome CSS -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/plugin/nice-select/nice-select.css">
    <link rel="stylesheet" href="../assets/plugin/OwlCarousel2-2.3.4/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="../assets/plugin/OwlCarousel2-2.3.4/dist/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="../assets/plugin/nouislider/nouislider.min.css">
    <link rel="stylesheet" href="../assets/plugin/slick/slick.css">
    <link rel="stylesheet" href="../assets/css/style.css">
      <!-- Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap"  rel="stylesheet">
</head>
<body>
    <!-- Header Section Start -->
    <header>
        <!-- Navbar -->
            <div class="container py-lg-2 mt-0 mt-lg-2">
                <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-2  mb-2 mb-lg-3 pt-3 pt-lg-2">
                                <div class="row">
                                    <div class="col-12 d-flex justify-content-center mb-3 mb-lg-0">
                                        <!-- Logo -->
                                        <a class="navbar-brand flex-shrink-0 py-0 py-lg-0" href="index.php">
                                            <img src="../assets/images/logo.png" class="logo main-logo" alt="eCommerce HTML Template">
                                        </a>
                                        <!-- Logo -->
                                        </div>
                                    
                                        <div class="col-12">
                                        <div class="list-inline  d-lg-none d-flex justify-content-between">

                                            <div class="list-inline-item d-inline-block d-lg-none">
                                                <button class="navbar-toggler border-0 collapsed" type="button" data-bs-toggle="offcanvas"
                                                    data-bs-target="#navbar-default" aria-controls="navbar-default"
                                                    aria-label="Toggle navigation">
                                                    <i class="bi bi-text-indent-left"></i>
                                                </button>
                                            </div>

                                            <div>
                                                <div class="list-inline-item me-4">
                                                    <a href="<?= $isLoggedIn ? 'account.php' : 'login.php' ?>" class="text-muted d-flex flex-column justity-content-center align-items-center">
                                                        <i class="bi bi-person"></i>
                                                        <span class="d-none d-sm-none d-md-none d-lg-block" >Account</span>
                                                    </a>
                                                </div>
                                                <div class="list-inline-item me-4">
                                                    <a href="wishlist.php" class="text-muted  d-flex flex-column justity-content-center align-items-center">
                                                        <i class="bi bi-heart"></i>
                                                        <span class="d-none d-sm-none d-md-none d-lg-block" >Wishlist</span>
                                                    </a>
                                                </div>
                                                <div class="list-inline-item me-4">
                                                    <a href="cart.php" class="text-muted  d-flex flex-column justity-content-center align-items-center">
                                                        <div class="position-relative">
                                                            <i class="bi bi-cart"></i>
                                                            <?php if ($cartCount > 0): ?>
                                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                                <?= $cartCount ?>
                                                            </span>
                                                            <?php endif; ?>
                                                        </div> 
                                                        <span class="d-none d-sm-none d-md-none d-lg-block" >Your cart</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-12 col-lg-7">
                            <nav class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2" aria-label="Offcanvas navbar large">
                            <div class="container">
                                <div class="offcanvas offcanvas-start pt-2" tabindex="-1" id="navbar-default"
                                    aria-labelledby="navbar-defaultLabel">
                                    <div class="offcanvas-header pb-1">
                                        <a href="index.php"><img src="../assets/images/logo.png"
                                                alt="eCommerce HTML Template"></a>
                                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="offcanvas-body">
                                        <div class="d-block d-lg-none mb-4">
                                            <form action="products.php" method="GET">
                                                <div class="input-group">
                                                    <input class="form-control" type="search" name="search" placeholder="Search for products">
                                                    <span class="input-group-append">
                                                        <button
                                                            class="btn bg-white border border-start-0 ms-n10 rounded-0 rounded-end"
                                                            type="submit">
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
                                                        data-bs-toggle="dropdown" aria-expanded="false">Shop</a>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="products.php">Shop Page - Filter</a></li>
                                                        <li><a class="dropdown-item" href="product-details.php">Shop Single</a></li>
                                                        <li><a class="dropdown-item" href="wishlist.php">Shop Wishlist</a></li>
                                                        <li><a class="dropdown-item" href="cart.php">Shop Cart</a></li>
                                                        <li><a class="dropdown-item" href="checkout.php">Shop Checkout</a></li>
                                                    </ul>
                                                </li>
                                                <li class="nav-item dropdown w-100 w-lg-auto dropdown-fullwidth">
                                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                                                    <div class="dropdown-menu pb-0">
                                                        <div class="row p-2 p-lg-4">
                                                            <?php if (!empty($categories)): ?>
                                                                <?php foreach (array_slice($categories, 0, 3) as $cat): ?>
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
                                                        data-bs-toggle="dropdown" aria-expanded="false">Pages</a>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#">Blog</a></li>
                                                        <li><a class="dropdown-item" href="#">Blog Single</a></li>
                                                        <li><a class="dropdown-item" href="#">About us</a></li>
                                                        <li><a class="dropdown-item" href="#">Contact</a></li>
                                                    </ul>
                                                </li>
                                                <li class="nav-item dropdown w-100 w-lg-auto">
                                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">Account</a>
                                                        <ul class="dropdown-menu">
                                                            <?php if ($isLoggedIn): ?>
                                                                <li><a class="dropdown-item" href="account.php">My Account</a></li>
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
                        <div class="col-12 col-sm-12 col-md-12 col-lg-3 d-none d-lg-block">
                                <!-- Navbar Actions -->
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
                                                        <button
                                                            class="btn bg-white border border-start-0 ms-n10 py-3 rounded-0 rounded-end"
                                                            type="submit">
                                                            <span class="bi bi-search"></span>
                                                        </button>
                                                    </span>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    </div>

                                <div class="list-inline  d-flex pt-2">
                                    <div class="list-inline-item me-4">
                                        <a href="products.php"  data-bs-toggle="offcanvas" data-bs-target="#offcanvasTop" aria-controls="offcanvasTop" class="text-muted d-flex flex-column justity-content-center align-items-center">
                                            <i class="bi bi-search"></i>
                                            <span class="d-none d-sm-none d-md-none d-lg-block" >Search</span>
                                        </a>
                                    </div>
                                    <div class="list-inline-item me-4">
                                        <a href="<?= $isLoggedIn ? 'account.php' : 'login.php' ?>" class="text-muted d-flex flex-column justity-content-center align-items-center">
                                            <i class="bi bi-person"></i>
                                            <span class="d-none d-sm-none d-md-none d-lg-block" >Account</span>
                                        </a>
                                    </div>
                                    <div class="list-inline-item me-4">
                                        <a href="wishlist.php" class="text-muted  d-flex flex-column justity-content-center align-items-center">
                                            <i class="bi bi-heart"></i>
                                            <span class="d-none d-sm-none d-md-none d-lg-block" >Wishlist</span>
                                        </a>
                                    </div>
                                    <div class="list-inline-item me-4">
                                        <a href="cart.php" class="text-muted  d-flex flex-column justity-content-center align-items-center">
                                            <i class="bi bi-cart"></i>
                                            <span class="d-none d-sm-none d-md-none d-lg-block" >Your cart</span>
                                        </a>
                                    </div>
                                </div>
                                <!-- Navbar Actions -->
                        </div>
                </div>
            </div>
        <!-- Navbar -->
    </header>
    <!-- Header Section End -->

    <?php if ($flashMessage !== ""): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show mb-0" role="alert">
                <?= e($flashMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Banner Slider -->
    <div class="banner-section" style="background-color: #FEF6F0;">
        <div class="container">
            <div class="owl-carousel owl-theme banner-slider">
                <div class="item"> 
                    <div class="banner-item" style="background-image: url(../assets/images/banner/1.png)">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-8 col-lg-6">
                                    <div class="banner-content text-left">
                                        <span class="mb-3 d-block">Top Selling!</span>
                                        <h2>Best Collection Furniture</h2>
                                        <a href="products.php" class="btn btn-primary uppercase mt-4">Shop Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
                 <div class="item"> 
                    <div class="banner-item" style="background-image: url(../assets/images/banner/2.png)">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-8 col-lg-6">
                                    <div class="banner-content text-left">
                                        <span class="mb-3 d-block">Top Selling!</span>
                                        <h2>Best Collection Furniture</h2>
                                        <a href="products.php" class="btn btn-primary uppercase mt-4">Shop Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
            </div>
        </div>
    </div>
    <!-- End Banner Slider -->

    <!-- start hero banner Section -->
    <div class="hero-banner-area">
        <div class="container">
            <div class="row">
                <!-- Single -->
                <div class="col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
                    <div class="hero-banner-item rounded">
                        <img src="../assets/images/banner/4.png" alt="banner">
                        <div class="hero-banner-item-overly">
                            <div class="hero-banner-item-overly-full">
                                <h4>Exclusive Sale</h4>
                                <h3>Modern</h3>
                                <a class="btn btn-primary mt-2" href="products.php">Shop now</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Single -->
                <div class="col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
                    <div class="hero-banner-item rounded">
                        <img src="../assets/images/banner/3.png" alt="banner">
                        <div class="hero-banner-item-overly">
                            <div class="hero-banner-item-overly-full">
                                <h4>Super Sale</h4>
                                <h3>Furniture</h3>
                                <a class="btn btn-primary mt-2" href="products.php">Shop now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
    <!-- end hero banner Section -->

    <!-- Categories Section -->
    <div class="category-list">
        <div class="container">
            <div class="section-title">
                <h2>Category</h2>
            </div>
             <div class="category-container">
                <div class="owl-carousel category-slider">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $index => $category): ?>
                            <?php $categoryImage = (($index % 7) + 1); ?>
                            <div class="item"> 
                                <div>
                                 <div class="category-hover">
                                    <a href="products.php?category=<?= (int) $category["id"] ?>">
                                        <img src="../assets/images/category/<?= $categoryImage ?>.png" class="img-fluid" alt="eCommerce Template"> 
                                    </a>
                                  </div>
                                  <a href="products.php?category=<?= (int) $category["id"] ?>" class="d-block category-title">
                                    <?= e($category["name"]) ?>
                                </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="item">
                            <p class="text-muted">No categories yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
             </div>
        </div>
    </div>
    <!-- End Categories Section -->
     
    <!-- Product Section -->
    <div class="product mt-100">
        <div class="container">
            <div>
                <div class="section-title">
                    <h2>Our Product</h2>
                </div>
                <div class="d-flex justify-content-center">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-lastest-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-lastest" type="button" role="tab" aria-controls="pills-lastest"
                                aria-selected="true">Lastest</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-popularity-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-popularity" type="button" role="tab"
                                aria-controls="pills-popularity" aria-selected="false">Featured</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-top-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-top" type="button" role="tab" aria-controls="pills-top"
                                aria-selected="false">Special</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-lastest" role="tabpanel"  aria-labelledby="pills-lastest-tab" tabindex="0">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <?php renderProductGrid($latestProducts); ?>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <a href="products.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-popularity" role="tabpanel" aria-labelledby="pills-popularity-tab" tabindex="0">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <?php renderProductGrid($featuredProducts); ?>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <a href="products.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-top" role="tabpanel" aria-labelledby="pills-top-tab" tabindex="0">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <?php renderProductGrid($specialProducts); ?>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <a href="products.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <!-- Product Section End -->

     <!-- Feature Section -->
    <div class="feature-section mt-100">
        <div class="container">
            <div class="row">
              <div class="col-lg-12">
                <div class="white-bg border px-4 py-3">
                  <div class="row">
                    <div class="mb-20 col-12 col-sm-12 col-md-6 col-lg-3">
                      <div class="feature-item">
                        <div class="feature-icon">
                            <img src="../assets/images/icon/delivery-truck.png" class="w-100"  alt="truck Icon">
                        </div>
                        <div class="feature-info pt-3 ps-2">
                            <h4>Free Shipping</h4>
                            <p>On orders over&nbsp;<strong>Rs. 5000.</strong></p>
                        </div>
                      </div>
                    </div>
                    <div class="mb-20 col-12 col-sm-12 col-md-6 col-lg-3">
                      <div class="feature-item">
                        <div class="feature-icon">
                            <img src="../assets/images/icon/loan.png" class="w-100"  alt="truck Icon">
                        </div>
                        <div class="feature-info pt-3 ps-2">
                            <h4>Money Back</h4>
                            <p>Money back in 7 days.</p>
                        </div>
                      </div>
                    </div>
                    <div class="mb-20 col-12 col-sm-12 col-md-6 col-lg-3">
                      <div class="feature-item">
                        <div class="feature-icon">
                            <img src="../assets/images/icon/credit-card.png" class="w-100"  alt="truck Icon">
                        </div>
                        <div class="feature-info pt-3 ps-2">
                            <h4>Secure Checkout</h4>
                            <p>100% Payment Secure.</p>
                        </div>
                      </div>
                    </div>
                    <div class="mb-20 col-12 col-sm-12 col-md-6 col-lg-3">
                      <div class="feature-item">
                        <div class="feature-icon">
                            <img src="../assets/images/icon/customer-service.png" class="w-100"  alt="truck Icon">
                        </div>
                        <div class="feature-info pt-3 ps-2">
                            <h4>Online Support</h4>
                            <p>Ensure the product quality</p>
                        </div>
                      </div>
                    </div>
                </div>
                </div>
              </div>
            </div>
          </div>
       </div>
    <!-- Feature Section -->

     <!-- Product Section -->
     <div class="product mt-100">
        <div class="container">
            <div class="section-title">
                <h2>New Arrivals</h2>
            </div>
            <div class="mt-0">
                <div class="owl-carousel product-slider">
                    <?php foreach ($newArrivals as $product): ?>
                        <?php
                        $id    = (int) $product["id"];
                        $image = productImage($product["image"]);
                        $stock = (int) $product["stock_quantity"];
                        $inWish = in_array($id, $_SESSION["wishlist"] ?? [], true);
                        ?>
                       <div class="card product-card mx-2 mb-3 position-relative">
                            <form method="post" class="position-absolute" style="top:8px;right:15px;z-index:5;">
                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                <button type="submit" name="toggle_wishlist"
                                    class="btn btn-light btn-sm rounded-circle shadow-sm border-0"
                                    style="width:32px;height:32px;padding:0;line-height:1;">
                                    <i class="bi <?= $inWish ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                                </button>
                            </form>
                           <a href="product-details.php?id=<?= $id ?>">
                               <img src="<?= e($image) ?>" class="card-img-top image-first" alt="<?= e($product["name"]) ?>" onerror="this.onerror=null;this.src='../assets/images/product/1.png';">
                            </a>
                            <div class="card-body pt-0">
                               <?php if ($stock <= 0): ?>
                                   <span class="discount-badge bg-danger text-white">Out of Stock</span>
                               <?php elseif ($stock <= 5): ?>
                                   <span class="discount-badge">Low Stock</span>
                               <?php endif; ?>
                           </div>
                           <div class="product-price px-3 pb-2">
                            <h5 class="card-title"><a href="product-details.php?id=<?= $id ?>">
                                <?= e($product["name"]) ?>
                            </a>
                            </h5>
                            <div class="mb-2">
                                <small class="text-warning">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                </small>
                            </div>
                            <div class="d-block">
                                <span class="sell-price">Rs. <?= number_format((float) $product["price"], 2) ?></span>
                            </div>
                            <div class="mt-1">
                                <small class="<?= $stock > 0 ? 'text-success' : 'text-danger' ?>"><?= e(stockLabel($stock)) ?></small>
                            </div>
                        </div>
                        <div class="d-block mb-2">
                            <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                <?php if ($stock > 0): ?>
                                    <form method="post" class="w-100">
                                        <input type="hidden" name="product_id" value="<?= $id ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary w-100">Add to Cart</button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary w-100" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>    
                        </div>
                       </div>
                    <?php endforeach; ?>
               </div>
            </div>
            <div class="text-center mt-5">
                <a href="products.php" class="btn btn-primary">View All</a>
            </div>
        </div>
      </div>
     <!-- Product Section End -->

    <!-- footer Section -->
    <footer class="mt-0">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-md-0">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-12">
                            <div class="footer_logo">
                                <img loading="lazy" src="../assets/images/logo.png" class="logo" alt="easy shop">
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
                                    <?php if (!$isLoggedIn): ?>
                                        <li><a href="login.php">Login</a></li>
                                        <li><a href="register.php">Register</a></li>
                                    <?php else: ?>
                                        <li><a href="account.php">My Account</a></li>
                                        <li><a href="orders.php">My Orders</a></li>
                                        <li><a href="logout.php">Logout</a></li>
                                    <?php endif; ?>
                                    <li><a href="cart.php">Cart</a></li>
                                    <li><a href="wishlist.php">Wishlist</a></li>
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
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
                                            <li><a href="products.php?category=<?= (int) $cat["id"] ?>"><?= e($cat["name"]) ?></a></li>
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
        <div class="text-center py-3 mt-4 text-white px-3 copyright" >
            <span>Copyright © <?= date("Y") ?>. All Rights Reserved. Furnishop.</span>
        </div>
    </footer>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/plugin/nice-select/jquery.nice-select.min.js"></script>
    <script src="../assets/plugin/OwlCarousel2-2.3.4/dist/owl.carousel.min.js"></script>
    <script src="../assets/plugin/nouislider/nouislider.min.js"></script>
    <script src="../assets/plugin/slick/slick.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>