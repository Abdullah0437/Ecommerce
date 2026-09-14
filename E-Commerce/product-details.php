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
   GET PRODUCT ID
========================================================= */

$productId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$productId || $productId <= 0) {
    header("Location: products.php");
    exit;
}


/* =========================================================
   VARIABLES
========================================================= */

$cartError = "";
$addedMessage = false;


/* =========================================================
   ADD TO CART / BUY NOW
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $quantity = filter_input(INPUT_POST, "qty", FILTER_VALIDATE_INT);

    if (!$quantity || $quantity < 1) {

        $cartError = "Please select a valid quantity.";
    } else {

        /* -----------------------------------------------------
           Get latest product information
        ----------------------------------------------------- */

        $stmt = mysqli_prepare(
            $connect,
            "SELECT id, name, stock_quantity, status
             FROM products
             WHERE id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $cartProduct = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (!$cartProduct) {

            $cartError = "Product not found.";
        } elseif ($cartProduct["status"] !== "Active") {

            $cartError = "This product is currently unavailable.";
        } elseif ((int)$cartProduct["stock_quantity"] <= 0) {

            $cartError = "This product is out of stock.";
        } else {

            $stock = (int)$cartProduct["stock_quantity"];

            $currentCartQty = isset($_SESSION["cart"][$productId])
                ? (int)$_SESSION["cart"][$productId]
                : 0;


            /* =================================================
               BUY NOW
            ================================================= */

            if (isset($_POST["buy_now"])) {

                if ($quantity > $stock) {

                    $cartError = "Only {$stock} item(s) are available.";
                } else {

                    /*
                     * Buy Now replaces the current quantity
                     * of this product in the cart.
                     */
                    $_SESSION["cart"][$productId] = $quantity;

                    header("Location: checkout.php");
                    exit;
                }


                /* =================================================
               ADD TO CART
            ================================================= */
            } elseif (isset($_POST["add_to_cart"])) {

                $newQuantity = $currentCartQty + $quantity;

                if ($newQuantity > $stock) {

                    $cartError = "Only {$stock} item(s) are available.";
                } else {

                    $_SESSION["cart"][$productId] = $newQuantity;

                    header(
                        "Location: product-details.php?id=" .
                            $productId .
                            "&added=1"
                    );

                    exit;
                }
            }
        }
    }
}


/* =========================================================
   PRODUCT QUERY
========================================================= */

$stmt = mysqli_prepare(
    $connect,
    "SELECT
        p.id,
        p.category_id,
        p.name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        c.name AS category_name
     FROM products p
     LEFT JOIN categories c
        ON c.id = p.category_id
     WHERE p.id = ?
       AND p.status = 'Active'
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   PRODUCT NOT FOUND
========================================================= */

if (!$product) {
    header("Location: products.php");
    exit;
}


/* =========================================================
   PRODUCT DATA
========================================================= */

$productName = $product["name"];
$productDescription = $product["description"];
$productPrice = (float)$product["price"];
$productStock = (int)$product["stock_quantity"];
$productImage = $product["image"];
$categoryName = !empty($product["category_name"])
    ? $product["category_name"]
    : "Uncategorized";


/* =========================================================
   PRODUCT IMAGE FUNCTION
========================================================= */

function productImage($image)
{
    $fallback = "../assets/images/product/1.png";

    if (empty($image)) {
        return $fallback;
    }

    $imageName = basename($image);

    return "../Images/" . rawurlencode($imageName);
}


/* =========================================================
   MAIN IMAGE
========================================================= */

$mainImage = productImage($productImage);


/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;

foreach ($_SESSION["cart"] as $qty) {
    $cartCount += (int)$qty;
}


/* =========================================================
   SUCCESS MESSAGE
========================================================= */

if (
    isset($_GET["added"]) &&
    $_GET["added"] === "1"
) {
    $addedMessage = true;
}


/* =========================================================
   STOCK STATUS
========================================================= */

if ($productStock > 0) {

    $stockText = "In Stock";
    $stockClass = "text-success";
} else {

    $stockText = "Out of Stock";
    $stockClass = "text-danger";
}


/* =========================================================
   SKU
========================================================= */

$sku = "PROD-" . str_pad(
    $productId,
    5,
    "0",
    STR_PAD_LEFT
);


/* =========================================================
   RELATED PRODUCTS
========================================================= */

$relatedProducts = [];

$stmt = mysqli_prepare(
    $connect,
    "SELECT
        id,
        name,
        price,
        image,
        stock_quantity
     FROM products
     WHERE status = 'Active'
       AND id != ?
       AND category_id = ?
     ORDER BY id DESC
     LIMIT 8"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $productId,
    $product["category_id"]
);

mysqli_stmt_execute($stmt);

$relatedResult = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($relatedResult)) {
    $relatedProducts[] = $row;
}

mysqli_stmt_close($stmt);


/* =========================================================
   FALLBACK RELATED PRODUCTS
========================================================= */

if (empty($relatedProducts)) {

    $stmt = mysqli_prepare(
        $connect,
        "SELECT
            id,
            name,
            price,
            image,
            stock_quantity
         FROM products
         WHERE status = 'Active'
           AND id != ?
         ORDER BY id DESC
         LIMIT 8"
    );

    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);

    $relatedResult = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($relatedResult)) {
        $relatedProducts[] = $row;
    }

    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($productName) ?> - Product Details
    </title>

    <meta
        name="description"
        content="<?= htmlspecialchars($productDescription) ?>">

    <link
        rel="icon"
        type="image/x-icon"
        href="../assets/images/favicon.ico">

    <link
        rel="stylesheet"
        href="../assets/css/bootstrap.min.css">

    <link
        rel="stylesheet"
        href="../assets/font/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <link
        rel="stylesheet"
        href="../assets/plugin/nice-select/nice-select.css">

    <link
        rel="stylesheet"
        href="../assets/plugin/OwlCarousel2-2.3.4/dist/assets/owl.carousel.min.css">

    <link
        rel="stylesheet"
        href="../assets/plugin/OwlCarousel2-2.3.4/dist/assets/owl.theme.default.min.css">

    <link
        rel="stylesheet"
        href="../assets/plugin/nouislider/nouislider.min.css">

    <link
        rel="stylesheet"
        href="../assets/plugin/slick/slick.css">

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">


    <style>
        .breadcrumb-item+.breadcrumb-item::before {
            content: ">";
            padding-right: 8px;
            padding-left: 8px;
        }

        .breadcrumb-item a {
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: #6c757d;
        }

        .product-main-image {
            width: 100%;
            height: 500px;
            object-fit: contain;
            background: #fff;
        }

        .thumbnail-image {
            width: 100%;
            height: 90px;
            object-fit: contain;
            cursor: pointer;
        }

        .thumbnail-image:hover {
            opacity: 0.75;
        }

        .product-title-name h1 {
            font-size: 32px;
            line-height: 1.4;
            font-weight: 600;
        }

        .product-description {
            line-height: 1.8;
            color: #666;
        }

        .quantity-wrapper {
            display: flex;
            align-items: center;
        }

        .quantity-wrapper input {
            width: 65px;
            height: 45px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .qty-btn {
            width: 45px;
            height: 45px;
            border: 1px solid #ddd;
            background: #fff;
        }

        .qty-btn:hover {
            background: #f5f5f5;
        }

        .product-info-table td {
            padding: 6px !important;
        }

        .product-alert {
            margin-bottom: 20px;
        }

        .related-product .product-card {
            height: 100%;
        }

        .related-product .product-card img {
            height: 220px;
            object-fit: contain;
        }

        .buy-now-btn {
            width: 100%;
        }

        @media (max-width: 991px) {

            .product-main-image {
                height: 400px;
            }

            .product-title-name h1 {
                font-size: 27px;
            }

        }

        @media (max-width: 575px) {

            .product-main-image {
                height: 300px;
            }

            .product-title-name h1 {
                font-size: 24px;
            }

            .quantity-wrapper input {
                width: 60px;
            }

            .qty-btn {
                width: 40px;
            }

        }
    </style>

</head>


<body>


    <!-- =========================================================
     HEADER
========================================================= -->

    <header>

        <div class="container py-lg-2 mt-0 mt-lg-2">

            <div class="row">


                <!-- LOGO -->

                <div class="col-12 col-lg-2 mb-2 mb-lg-3 pt-3 pt-lg-2">

                    <div class="row">

                        <div class="col-12 d-flex justify-content-center mb-3 mb-lg-0">

                            <a
                                class="navbar-brand py-0"
                                href="index.php">

                                <img
                                    src="../assets/images/logo.png"
                                    class="logo main-logo"
                                    alt="E-Commerce">

                            </a>

                        </div>


                        <!-- MOBILE ACTIONS -->

                        <div class="col-12">

                            <div class="list-inline d-lg-none d-flex justify-content-between">


                                <div class="list-inline-item">

                                    <button
                                        class="navbar-toggler border-0 collapsed"
                                        type="button"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#navbar-default">

                                        <i class="bi bi-text-indent-left"></i>

                                    </button>

                                </div>


                                <div>

                                    <div class="list-inline-item me-3">

                                        <a
                                            href="<?= isset($_SESSION["user_id"]) ? "account.php" : "login.php" ?>"
                                            class="text-muted d-flex flex-column align-items-center">

                                            <i class="bi bi-person"></i>

                                            <span>Account</span>

                                        </a>

                                    </div>


                                    <div class="list-inline-item">

                                        <a
                                            href="cart.php"
                                            class="text-muted d-flex flex-column align-items-center">

                                            <div class="position-relative">

                                                <i class="bi bi-cart"></i>

                                                <?php if ($cartCount > 0): ?>

                                                    <span
                                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">

                                                        <?= $cartCount ?>

                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                            <span>Your cart</span>

                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- NAVIGATION -->

                <div class="col-12 col-lg-7">

                    <nav
                        class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2">

                        <div class="container">

                            <div
                                class="offcanvas offcanvas-start pt-2"
                                tabindex="-1"
                                id="navbar-default">

                                <div class="offcanvas-header pb-1">

                                    <a href="index.php">

                                        <img
                                            src="../assets/images/logo.png"
                                            alt="E-Commerce">

                                    </a>

                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="offcanvas"></button>

                                </div>


                                <div class="offcanvas-body">

                                    <div class="d-block d-lg-none mb-4">

                                        <form
                                            action="products.php"
                                            method="GET">

                                            <div class="input-group">

                                                <input
                                                    class="form-control"
                                                    type="search"
                                                    name="search"
                                                    placeholder="Search for products">

                                                <button
                                                    class="btn bg-white border"
                                                    type="submit">

                                                    <span class="bi bi-search"></span>

                                                </button>

                                            </div>

                                        </form>

                                    </div>


                                    <div class="mx-auto">

                                        <ul class="navbar-nav align-items-center ms-lg-5">


                                            <li class="nav-item">

                                                <a
                                                    class="nav-link"
                                                    href="index.php">

                                                    Home

                                                </a>

                                            </li>


                                            <li class="nav-item dropdown">

                                                <a
                                                    class="nav-link dropdown-toggle"
                                                    href="#"
                                                    data-bs-toggle="dropdown">

                                                    Shop

                                                </a>


                                                <ul class="dropdown-menu">

                                                    <li>

                                                        <a
                                                            class="dropdown-item"
                                                            href="products.php">

                                                            Shop Page

                                                        </a>

                                                    </li>

                                                    <li>

                                                        <a
                                                            class="dropdown-item"
                                                            href="product-details.php?id=<?= $productId ?>">

                                                            Shop Single

                                                        </a>

                                                    </li>

                                                    <li>

                                                        <a
                                                            class="dropdown-item"
                                                            href="cart.php">

                                                            Shop Cart

                                                        </a>

                                                    </li>

                                                    <li>

                                                        <a
                                                            class="dropdown-item"
                                                            href="checkout.php">

                                                            Shop Checkout

                                                        </a>

                                                    </li>

                                                </ul>

                                            </li>


                                            <li class="nav-item">

                                                <a
                                                    class="nav-link"
                                                    href="products.php">

                                                    Products

                                                </a>

                                            </li>


                                            <li class="nav-item dropdown">

                                                <a
                                                    class="nav-link dropdown-toggle"
                                                    href="#"
                                                    data-bs-toggle="dropdown">

                                                    Account

                                                </a>


                                                <ul class="dropdown-menu">

                                                    <?php if (isset($_SESSION["user_id"])): ?>

                                                        <li>

                                                            <a
                                                                class="dropdown-item"
                                                                href="account.php">

                                                                My Account

                                                            </a>

                                                        </li>

                                                        <li>

                                                            <a
                                                                class="dropdown-item"
                                                                href="orders.php">

                                                                My Orders

                                                            </a>

                                                        </li>

                                                        <li>

                                                            <a
                                                                class="dropdown-item"
                                                                href="logout.php">

                                                                Logout

                                                            </a>

                                                        </li>

                                                    <?php else: ?>

                                                        <li>

                                                            <a
                                                                class="dropdown-item"
                                                                href="login.php">

                                                                Sign in

                                                            </a>

                                                        </li>

                                                        <li>

                                                            <a
                                                                class="dropdown-item"
                                                                href="register.php">

                                                                Signup

                                                            </a>

                                                        </li>

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


                <!-- DESKTOP ACTIONS -->

                <div class="col-12 col-lg-3 d-none d-lg-block">

                    <div class="list-inline d-flex pt-2">


                        <!-- SEARCH -->

                        <div class="list-inline-item me-4">

                            <a
                                href="products.php"
                                class="text-muted d-flex flex-column align-items-center">

                                <i class="bi bi-search"></i>

                                <span>Search</span>

                            </a>

                        </div>


                        <!-- ACCOUNT -->

                        <div class="list-inline-item me-4">

                            <a
                                href="<?= isset($_SESSION["user_id"]) ? "account.php" : "login.php" ?>"
                                class="text-muted d-flex flex-column align-items-center">

                                <i class="bi bi-person"></i>

                                <span>Account</span>

                            </a>

                        </div>


                        <!-- CART -->

                        <div class="list-inline-item me-4">

                            <a
                                href="cart.php"
                                class="text-muted d-flex flex-column align-items-center">

                                <div class="position-relative">

                                    <i class="bi bi-cart"></i>

                                    <?php if ($cartCount > 0): ?>

                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">

                                            <?= $cartCount ?>

                                        </span>

                                    <?php endif; ?>

                                </div>

                                <span>Your cart</span>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </header>


    <!-- =========================================================
     PRODUCT DETAILS
========================================================= -->

    <div class="product-details">

        <main>


            <!-- =========================================================
     BREADCRUMB
========================================================= -->

            <div class="breadcrumb-main">

                <div class="container">

                    <div class="breadcrumb-container">

                        <h2 class="page-title">

                            <?= htmlspecialchars($productName) ?>

                        </h2>


                        <ul class="breadcrumb">

                            <li class="breadcrumb-item">

                                <a href="index.php">

                                    Home

                                </a>

                            </li>


                            <li class="breadcrumb-item">

                                <a href="products.php">

                                    Products

                                </a>

                            </li>


                            <li class="breadcrumb-item active">

                                <?= htmlspecialchars($productName) ?>

                            </li>

                        </ul>

                    </div>

                </div>

            </div>


            <!-- =========================================================
     ALERTS
========================================================= -->

            <section class="mt-4">

                <div class="container">


                    <?php if ($addedMessage): ?>

                        <div class="alert alert-success product-alert">

                            <i class="bi bi-check-circle me-2"></i>

                            Product added to your cart successfully.

                            <a
                                href="cart.php"
                                class="alert-link ms-2">

                                View Cart

                            </a>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($cartError)): ?>

                        <div class="alert alert-danger product-alert">

                            <i class="bi bi-exclamation-circle me-2"></i>

                            <?= htmlspecialchars($cartError) ?>

                        </div>

                    <?php endif; ?>


                </div>

            </section>


            <!-- =========================================================
     PRODUCT SECTION
========================================================= -->

            <section class="mt-3">

                <div class="container">

                    <div class="row">


                        <!-- PRODUCT IMAGE -->

                        <div class="col-md-12 col-lg-5 col-xl-5">

                            <div
                                class="zoom border rounded"
                                id="product-img-zoom">

                                <img
                                    src="<?= htmlspecialchars($mainImage) ?>"
                                    class="product-main-image rounded"
                                    alt="<?= htmlspecialchars($productName) ?>">

                            </div>


                            <!-- THUMBNAILS -->

                            <div class="px-3 px-lg-5">

                                <div class="product-tools mt-3">

                                    <div class="row g-3">


                                        <!-- MAIN IMAGE -->

                                        <div class="col-3">

                                            <div class="border rounded p-1">

                                                <img
                                                    src="<?= htmlspecialchars($mainImage) ?>"
                                                    class="thumbnail-image rounded"
                                                    alt="<?= htmlspecialchars($productName) ?>">

                                            </div>

                                        </div>


                                        <!-- TEMPLATE IMAGE -->

                                        <div class="col-3">

                                            <div class="border rounded p-1">

                                                <img
                                                    src="../assets/images/product/2.png"
                                                    class="thumbnail-image rounded"
                                                    alt="Product">

                                            </div>

                                        </div>


                                        <div class="col-3">

                                            <div class="border rounded p-1">

                                                <img
                                                    src="../assets/images/product/3.png"
                                                    class="thumbnail-image rounded"
                                                    alt="Product">

                                            </div>

                                        </div>


                                        <div class="col-3">

                                            <div class="border rounded p-1">

                                                <img
                                                    src="../assets/images/product/4.png"
                                                    class="thumbnail-image rounded"
                                                    alt="Product">

                                            </div>

                                        </div>


                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- PRODUCT INFORMATION -->

                        <div class="col-md-12 col-lg-7 col-xl-7">

                            <div class="ps-lg-5 mt-5 mt-md-0">


                                <!-- RATING -->

                                <div class="mb-3 d-flex align-items-center">

                                    <small class="text-warning">

                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-half"></i>

                                    </small>

                                    <span class="ms-2 text-muted">

                                        No reviews yet

                                    </span>

                                </div>


                                <!-- PRODUCT NAME -->

                                <div class="product-title-name">

                                    <h1 class="mb-2">

                                        <?= htmlspecialchars($productName) ?>

                                    </h1>

                                </div>


                                <!-- PRICE -->

                                <div class="fs-4 mt-2">

                                    <span class="fw-bold text-dark">

                                        Rs. <?= number_format($productPrice, 2) ?>

                                    </span>

                                </div>


                                <hr class="my-3">


                                <!-- PRODUCT INFORMATION -->

                                <table
                                    class="table table-borderless mb-0 product-info-table">

                                    <tbody>

                                        <tr>

                                            <td>

                                                SKU:

                                            </td>

                                            <td>

                                                <?= htmlspecialchars($sku) ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>

                                                Availability:

                                            </td>

                                            <td class="<?= $stockClass ?>">

                                                <?= $stockText ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>

                                                Type:

                                            </td>

                                            <td>

                                                <?= htmlspecialchars($categoryName) ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>

                                                Shipping:

                                            </td>

                                            <td>

                                                Standard shipping

                                            </td>

                                        </tr>

                                    </tbody>

                                </table>


                                <hr class="my-3">


                                <!-- DESCRIPTION -->

                                <div>

                                    <p class="fw-bold mb-2">

                                        Description

                                    </p>


                                    <p class="product-description">

                                        <?= nl2br(htmlspecialchars($productDescription)) ?>

                                    </p>

                                </div>


                                <hr class="my-3">


                                <!-- =================================================
                         QUANTITY + CART
                    ================================================= -->

                                <?php if ($productStock > 0): ?>

                                    <form
                                        method="POST"
                                        action="product-details.php?id=<?= $productId ?>">

                                        <div class="product-action">

                                            <div class="d-flex flex-column flex-sm-row gap-2">


                                                <!-- QUANTITY -->

                                                <div class="quantity-wrapper">

                                                    <button
                                                        type="button"
                                                        class="qty-btn"
                                                        id="decreaseQty">

                                                        <i class="bi bi-dash"></i>

                                                    </button>


                                                    <input
                                                        type="number"
                                                        name="qty"
                                                        id="productQty"
                                                        value="1"
                                                        min="1"
                                                        max="<?= $productStock ?>"
                                                        class="input-qty"
                                                        required>


                                                    <button
                                                        type="button"
                                                        class="qty-btn"
                                                        id="increaseQty">

                                                        <i class="bi bi-plus"></i>

                                                    </button>

                                                </div>


                                                <!-- ADD TO CART -->

                                                <button
                                                    type="submit"
                                                    name="add_to_cart"
                                                    class="btn btn-primary">

                                                    <i class="bi bi-bag me-2"></i>

                                                    Add to Cart

                                                </button>

                                            </div>


                                            <!-- BUY NOW -->

                                            <div class="mt-2">

                                                <button
                                                    type="submit"
                                                    name="buy_now"
                                                    class="btn btn-secondary buy-now-btn">

                                                    <i class="bi bi-lightning-charge-fill me-2"></i>

                                                    Buy Now

                                                </button>

                                            </div>

                                        </div>

                                    </form>

                                <?php else: ?>

                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        disabled>

                                        <i class="bi bi-x-circle me-2"></i>

                                        Out of Stock

                                    </button>

                                <?php endif; ?>


                                <hr class="mt-3">


                                <!-- FEATURES -->

                                <div class="row mt-3">


                                    <div class="col-md-4 mb-3">

                                        <div class="d-flex align-items-center">

                                            <i class="bi bi-truck fs-3 me-3"></i>

                                            <div>

                                                <strong>Delivery</strong>

                                                <p class="mb-0 small text-muted">

                                                    Standard shipping

                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="col-md-4 mb-3">

                                        <div class="d-flex align-items-center">

                                            <i class="bi bi-shield-check fs-3 me-3"></i>

                                            <div>

                                                <strong>Secure</strong>

                                                <p class="mb-0 small text-muted">

                                                    Secure shopping

                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="col-md-4 mb-3">

                                        <div class="d-flex align-items-center">

                                            <i class="bi bi-arrow-repeat fs-3 me-3"></i>

                                            <div>

                                                <strong>Easy Return</strong>

                                                <p class="mb-0 small text-muted">

                                                    Easy return policy

                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </section>


            <!-- =========================================================
     PRODUCT DETAILS / REVIEWS
========================================================= -->

            <section class="mt-5">

                <div class="container">

                    <div class="product-detail-review">

                        <div class="row">

                            <div class="col-md-12">


                                <!-- TABS -->

                                <ul
                                    class="nav nav-pills nav-lb-tab"
                                    id="myTab"
                                    role="tablist">


                                    <li
                                        class="nav-item"
                                        role="presentation">

                                        <button
                                            class="nav-link active"
                                            id="product-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#product-tab-pane"
                                            type="button"
                                            role="tab">

                                            Product Details

                                        </button>

                                    </li>


                                    <li
                                        class="nav-item"
                                        role="presentation">

                                        <button
                                            class="nav-link"
                                            id="reviews-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#reviews-tab-pane"
                                            type="button"
                                            role="tab">

                                            Reviews

                                        </button>

                                    </li>


                                </ul>


                                <!-- TAB CONTENT -->

                                <div
                                    class="tab-content"
                                    id="myTabContent">


                                    <!-- PRODUCT DETAILS -->

                                    <div
                                        class="tab-pane fade show active"
                                        id="product-tab-pane"
                                        role="tabpanel">

                                        <div class="my-4">

                                            <h4>

                                                <?= htmlspecialchars($productName) ?>

                                            </h4>


                                            <p>

                                                <?= nl2br(htmlspecialchars($productDescription)) ?>

                                            </p>


                                            <h5 class="mt-4">

                                                Product Information

                                            </h5>


                                            <ul class="m-0 ps-3">

                                                <li>

                                                    Category:
                                                    <?= htmlspecialchars($categoryName) ?>

                                                </li>


                                                <li>

                                                    Price:
                                                    Rs. <?= number_format($productPrice, 2) ?>

                                                </li>


                                                <li>

                                                    Available Stock:
                                                    <?= $productStock ?>

                                                </li>


                                                <li>

                                                    SKU:
                                                    <?= htmlspecialchars($sku) ?>

                                                </li>

                                            </ul>

                                        </div>

                                    </div>


                                    <!-- REVIEWS -->

                                    <div
                                        class="tab-pane fade"
                                        id="reviews-tab-pane"
                                        role="tabpanel">

                                        <div class="my-4">

                                            <div class="alert alert-info">

                                                <i class="bi bi-info-circle me-2"></i>

                                                No customer reviews are available for
                                                this product yet.

                                            </div>

                                        </div>

                                    </div>


                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </section>


            <!-- =========================================================
     RELATED PRODUCTS
========================================================= -->

            <?php if (!empty($relatedProducts)): ?>

                <section class="my-5">

                    <div class="container">

                        <div class="related-product">


                            <div class="row">

                                <div class="col-12">

                                    <h3>

                                        Related Items

                                    </h3>

                                </div>

                            </div>


                            <div class="product mt-3">

                                <div
                                    class="owl-carousel product-slider related-product-slider">


                                    <?php foreach ($relatedProducts as $related): ?>

                                        <?php

                                        $relatedImage = productImage(
                                            $related["image"]
                                        );

                                        $relatedPrice = (float)$related["price"];

                                        $relatedStock = (int)$related["stock_quantity"];

                                        ?>


                                        <div class="card product-card me-3">


                                            <!-- IMAGE -->

                                            <a
                                                href="product-details.php?id=<?= (int)$related["id"] ?>">

                                                <img
                                                    src="<?= htmlspecialchars($relatedImage) ?>"
                                                    class="card-img-top image-first"
                                                    alt="<?= htmlspecialchars($related["name"]) ?>">

                                            </a>


                                            <!-- ICON -->

                                            <div class="card-body pt-0">

                                                <div class="icons">

                                                    <a
                                                        href="#"
                                                        data-bs-toggle="tooltip"
                                                        title="Wishlist">

                                                        <i class="bi bi-heart"></i>

                                                    </a>

                                                </div>

                                            </div>


                                            <!-- PRODUCT PRICE -->

                                            <div class="product-price px-3 pb-2">

                                                <h5 class="card-title">

                                                    <a
                                                        href="product-details.php?id=<?= (int)$related["id"] ?>">

                                                        <?= htmlspecialchars($related["name"]) ?>

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

                                                        Rs. <?= number_format($relatedPrice, 2) ?>

                                                    </span>

                                                </div>

                                            </div>


                                            <!-- BUTTONS -->

                                            <div class="d-block mb-2">

                                                <div class="d-flex flex-column px-2">


                                                    <?php if ($relatedStock > 0): ?>

                                                        <form
                                                            method="POST"
                                                            action="cart.php"
                                                            class="mb-2">

                                                            <input
                                                                type="hidden"
                                                                name="product_id"
                                                                value="<?= (int)$related["id"] ?>">

                                                            <input
                                                                type="hidden"
                                                                name="quantity"
                                                                value="1">

                                                            <input
                                                                type="hidden"
                                                                name="add_to_cart"
                                                                value="1">

                                                            <button
                                                                type="submit"
                                                                class="btn btn-primary w-100">

                                                                Add to Cart

                                                            </button>

                                                        </form>

                                                    <?php else: ?>

                                                        <button
                                                            type="button"
                                                            class="btn btn-secondary w-100 mb-2"
                                                            disabled>

                                                            Out of Stock

                                                        </button>

                                                    <?php endif; ?>


                                                    <a
                                                        href="product-details.php?id=<?= (int)$related["id"] ?>"
                                                        class="btn btn-secondary">

                                                        View Details

                                                    </a>


                                                </div>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>


                                </div>

                            </div>

                        </div>

                    </div>

                </section>

            <?php endif; ?>


        </main>

    </div>


    <!-- =========================================================
     FOOTER
========================================================= -->

    <footer class="mt-0">

        <div class="container">

            <div class="row">


                <!-- COMPANY -->

                <div class="col-lg-4 mb-4 mb-md-0">

                    <div class="footer_logo">

                        <img
                            loading="lazy"
                            src="../assets/images/logo.png"
                            class="logo"
                            alt="E-Commerce">

                    </div>


                    <div class="mt-4">

                        <p>

                            Your trusted online shopping destination.

                        </p>


                        <h3 class="h5 fw-bold">

                            Customer Support

                        </h3>


                        <p>

                            support@example.com

                        </p>

                    </div>

                </div>


                <!-- ACCOUNT -->

                <div class="col-lg-4 mb-3 mb-md-0">

                    <div class="row">

                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">

                                    My Account

                                </h4>


                                <ul class="m-0 p-0 list-unstyled">

                                    <li>

                                        <a href="orders.php">

                                            Orders

                                        </a>

                                    </li>


                                    <li>

                                        <a href="cart.php">

                                            Cart

                                        </a>

                                    </li>


                                    <li>

                                        <a href="account.php">

                                            Manage Account

                                        </a>

                                    </li>

                                </ul>

                            </div>

                        </div>


                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">

                                    Information

                                </h4>


                                <ul class="m-0 p-0 list-unstyled">

                                    <li>

                                        <a href="#">

                                            About Us

                                        </a>

                                    </li>


                                    <li>

                                        <a href="#">

                                            Return Policy

                                        </a>

                                    </li>


                                    <li>

                                        <a href="#">

                                            Privacy Policy

                                        </a>

                                    </li>


                                    <li>

                                        <a href="#">

                                            FAQ

                                        </a>

                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- USEFUL LINKS -->

                <div class="col-lg-4">

                    <div class="row">

                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">

                                    Useful Links

                                </h4>


                                <ul class="m-0 p-0 list-unstyled">

                                    <li>

                                        <a href="products.php">

                                            Products

                                        </a>

                                    </li>


                                    <li>

                                        <a href="cart.php">

                                            Cart

                                        </a>

                                    </li>


                                    <li>

                                        <a href="checkout.php">

                                            Checkout

                                        </a>

                                    </li>

                                </ul>

                            </div>

                        </div>


                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">

                                    Categories

                                </h4>


                                <ul class="m-0 p-0 list-unstyled">

                                    <li>

                                        <a href="products.php">

                                            All Products

                                        </a>

                                    </li>


                                    <li>

                                        <a href="products.php">

                                            Shop

                                        </a>

                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>

                </div>


            </div>

        </div>


        <div class="text-center py-3 mt-4 text-white px-3 copyright">

            <span>

                Copyright © <?= date("Y") ?>. All Rights Reserved.

            </span>

        </div>

    </footer>


    <!-- =========================================================
     JAVASCRIPT
========================================================= -->

    <script src="../assets/js/jquery-3.6.0.min.js"></script>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <script src="../assets/plugin/nice-select/jquery.nice-select.min.js"></script>

    <script src="../assets/plugin/OwlCarousel2-2.3.4/dist/owl.carousel.min.js"></script>

    <script src="../assets/plugin/nouislider/nouislider.min.js"></script>

    <script src="../assets/plugin/slick/slick.min.js"></script>

    <script src="../assets/js/main.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {


            /* =====================================================
               QUANTITY CONTROLS
            ===================================================== */

            const qtyInput =
                document.getElementById("productQty");

            const decreaseBtn =
                document.getElementById("decreaseQty");

            const increaseBtn =
                document.getElementById("increaseQty");


            if (
                qtyInput &&
                decreaseBtn &&
                increaseBtn
            ) {


                decreaseBtn.addEventListener(
                    "click",
                    function() {

                        let quantity =
                            parseInt(qtyInput.value) || 1;

                        const min =
                            parseInt(qtyInput.min) || 1;

                        if (quantity > min) {

                            quantity--;

                            qtyInput.value = quantity;
                        }

                    }
                );


                increaseBtn.addEventListener(
                    "click",
                    function() {

                        let quantity =
                            parseInt(qtyInput.value) || 1;

                        const max =
                            parseInt(qtyInput.max);


                        if (quantity < max) {

                            quantity++;

                            qtyInput.value = quantity;
                        }

                    }
                );


                qtyInput.addEventListener(
                    "change",
                    function() {

                        let quantity =
                            parseInt(this.value) || 1;

                        const min =
                            parseInt(this.min) || 1;

                        const max =
                            parseInt(this.max);


                        if (quantity < min) {
                            quantity = min;
                        }


                        if (quantity > max) {
                            quantity = max;
                        }


                        this.value = quantity;

                    }
                );

            }


            /* =====================================================
               PRODUCT IMAGE THUMBNAILS
            ===================================================== */

            const thumbnails =
                document.querySelectorAll(
                    ".thumbnail-image"
                );

            const mainProductImage =
                document.querySelector(
                    ".product-main-image"
                );


            thumbnails.forEach(
                function(thumbnail) {

                    thumbnail.addEventListener(
                        "click",
                        function() {

                            if (mainProductImage) {

                                mainProductImage.src =
                                    this.src;

                            }

                        }
                    );

                }
            );


            /* =====================================================
               OWL CAROUSEL
            ===================================================== */

            if (
                typeof jQuery !== "undefined" &&
                typeof jQuery.fn.owlCarousel !== "undefined"
            ) {

                $(".related-product-slider").owlCarousel({

                    loop: false,

                    margin: 15,

                    nav: true,

                    dots: false,

                    responsive: {

                        0: {
                            items: 1
                        },

                        576: {
                            items: 2
                        },

                        768: {
                            items: 3
                        },

                        992: {
                            items: 4
                        },

                        1200: {
                            items: 4
                        }

                    }

                });

            }


            /* =====================================================
               BOOTSTRAP TOOLTIPS
            ===================================================== */

            const tooltipTriggerList =
                document.querySelectorAll(
                    '[data-bs-toggle="tooltip"]'
                );

            tooltipTriggerList.forEach(
                function(tooltipTriggerEl) {

                    new bootstrap.Tooltip(
                        tooltipTriggerEl
                    );

                }
            );

        });
    </script>


</body>

</html>