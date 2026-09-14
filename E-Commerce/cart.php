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
   ADD PRODUCT TO CART
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_to_cart"])
) {

    $productId = filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );

    if ($productId && $productId > 0) {

        $stmt = mysqli_prepare(
            $connect,
            "SELECT id, stock_quantity
             FROM products
             WHERE id = ?
             AND status = 'Active'
             LIMIT 1"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $productId
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $product = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (
                $product &&
                (int)$product["stock_quantity"] > 0
            ) {

                $stock = (int)$product["stock_quantity"];

                $currentQuantity = isset(
                    $_SESSION["cart"][$productId]
                )
                    ? (int)$_SESSION["cart"][$productId]
                    : 0;

                $newQuantity = $currentQuantity + 1;

                if ($newQuantity > $stock) {
                    $newQuantity = $stock;
                }

                $_SESSION["cart"][$productId] = $newQuantity;
            }
        }
    }

    header("Location: products.php");
    exit;
}


/* =========================================================
   REMOVE PRODUCT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["remove"])
) {

    $productId = filter_input(
        INPUT_POST,
        "remove",
        FILTER_VALIDATE_INT
    );

    if ($productId && $productId > 0) {
        unset($_SESSION["cart"][$productId]);
    }

    header("Location: cart.php");
    exit;
}


/* =========================================================
   INCREASE QUANTITY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["increase"])
) {

    $productId = filter_input(
        INPUT_POST,
        "increase",
        FILTER_VALIDATE_INT
    );

    if (
        $productId &&
        $productId > 0 &&
        isset($_SESSION["cart"][$productId])
    ) {

        $stmt = mysqli_prepare(
            $connect,
            "SELECT stock_quantity, status
             FROM products
             WHERE id = ?
             LIMIT 1"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $productId
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $product = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($product) {

                $stock = (int)$product["stock_quantity"];

                $status = strtolower(
                    trim($product["status"])
                );

                if (
                    $status === "active" &&
                    $stock > 0
                ) {

                    $currentQuantity =
                        (int)$_SESSION["cart"][$productId];

                    if ($currentQuantity < $stock) {

                        $_SESSION["cart"][$productId] =
                            $currentQuantity + 1;
                    }
                } else {

                    unset($_SESSION["cart"][$productId]);
                }
            } else {

                unset($_SESSION["cart"][$productId]);
            }
        }
    }

    header("Location: cart.php");
    exit;
}


/* =========================================================
   DECREASE QUANTITY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["decrease"])
) {

    $productId = filter_input(
        INPUT_POST,
        "decrease",
        FILTER_VALIDATE_INT
    );

    if (
        $productId &&
        $productId > 0 &&
        isset($_SESSION["cart"][$productId])
    ) {

        $currentQuantity =
            (int)$_SESSION["cart"][$productId];

        $currentQuantity--;

        if ($currentQuantity <= 0) {

            unset($_SESSION["cart"][$productId]);
        } else {

            $_SESSION["cart"][$productId] =
                $currentQuantity;
        }
    }

    header("Location: cart.php");
    exit;
}


/* =========================================================
   UPDATE CART
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_cart"]) &&
    isset($_POST["quantity"]) &&
    is_array($_POST["quantity"])
) {

    foreach (
        $_POST["quantity"] as $productId => $quantity
    ) {

        $productId = filter_var(
            $productId,
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $quantity,
            FILTER_VALIDATE_INT
        );

        if (
            $productId === false ||
            $productId <= 0
        ) {
            continue;
        }

        if (
            $quantity === false ||
            $quantity <= 0
        ) {

            unset($_SESSION["cart"][$productId]);

            continue;
        }

        $stmt = mysqli_prepare(
            $connect,
            "SELECT id, stock_quantity, status
             FROM products
             WHERE id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            continue;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $productId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $product = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$product) {

            unset($_SESSION["cart"][$productId]);

            continue;
        }

        if (
            strtolower(trim($product["status"])) !== "active"
        ) {

            unset($_SESSION["cart"][$productId]);

            continue;
        }

        $stock = (int)$product["stock_quantity"];

        if ($stock <= 0) {

            unset($_SESSION["cart"][$productId]);

            continue;
        }

        if ($quantity > $stock) {
            $quantity = $stock;
        }

        $_SESSION["cart"][$productId] = $quantity;
    }

    header("Location: cart.php");
    exit;
}


/* =========================================================
   GET CART PRODUCTS
========================================================= */

$cartProducts = [];

$subtotal = 0;

if (!empty($_SESSION["cart"])) {

    foreach (
        $_SESSION["cart"] as $productId => $quantity
    ) {

        $productId = (int)$productId;

        $quantity = (int)$quantity;

        if (
            $productId <= 0 ||
            $quantity <= 0
        ) {

            unset($_SESSION["cart"][$productId]);

            continue;
        }

        $stmt = mysqli_prepare(
            $connect,
            "SELECT
                p.id,
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
             LIMIT 1"
        );

        if (!$stmt) {
            continue;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $productId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $product = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$product) {

            unset($_SESSION["cart"][$productId]);

            continue;
        }

        if (
            strtolower(trim($product["status"])) !== "active"
        ) {

            unset($_SESSION["cart"][$productId]);

            continue;
        }

        $stock = (int)$product["stock_quantity"];

        if ($stock <= 0) {

            unset($_SESSION["cart"][$productId]);

            continue;
        }

        if ($quantity > $stock) {

            $quantity = $stock;

            $_SESSION["cart"][$productId] =
                $quantity;
        }

        $price = (float)$product["price"];

        $itemSubtotal =
            $price * $quantity;

        $subtotal += $itemSubtotal;

        $product["cart_quantity"] =
            $quantity;

        $product["item_subtotal"] =
            $itemSubtotal;

        $cartProducts[] =
            $product;
    }
}


/* =========================================================
   SHIPPING
========================================================= */

$shipping = 0;

if ($subtotal > 0) {
    $shipping = 200;
}


/* =========================================================
   GRAND TOTAL
========================================================= */

$total =
    $subtotal + $shipping;


/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;

foreach ($_SESSION["cart"] as $quantity) {

    $quantity = (int)$quantity;

    if ($quantity > 0) {
        $cartCount += $quantity;
    }
}


/* =========================================================
   PRODUCT IMAGE
========================================================= */

function productImage($image)
{
    if (empty($image)) {
        return "../assets/images/product/1.png";
    }

    return "../Images/" .
        rawurlencode(
            basename($image)
        );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart | Furnishop</title>

    <meta
        name="description"
        content="Shopping Cart">

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
        /* =====================================================
           CART QUANTITY
        ===================================================== */

        .qty-container {
            display: flex;
            align-items: center;
            width: 145px;
        }

        .qty-btn-minus,
        .qty-btn-plus {
            width: 38px;
            height: 38px;
            border: 1px solid #dee2e6;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .qty-btn-minus:hover,
        .qty-btn-plus:hover {
            background: #f5f5f5;
        }

        .input-qty {
            width: 55px;
            height: 38px;
            text-align: center;
            border: 1px solid #dee2e6;
            margin: 0 3px;
        }

        .input-qty:focus {
            outline: none;
            box-shadow: none;
            border-color: #86b7fe;
        }


        /* =====================================================
           CART PRODUCT IMAGE
        ===================================================== */

        .cart-product-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }


        /* =====================================================
           CHECKOUT BUTTON
        ===================================================== */

        .checkout-btn a {
            min-width: 160px;
        }


        /* =====================================================
           EMPTY CART
        ===================================================== */

        .cart-empty-icon {
            font-size: 80px;
        }


        /* =====================================================
           BREADCRUMB
        ===================================================== */

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


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767px) {

            .qty-container {
                width: 140px;
            }

            .table td,
            .table th {
                white-space: nowrap;
            }

            .checkout-btn {
                justify-content: center !important;
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

                <div
                    class="col-12 col-sm-12 col-md-12 col-lg-2 mb-2 mb-lg-3 pt-3 pt-lg-2">

                    <div class="row">

                        <div
                            class="col-12 d-flex justify-content-center mb-3 mb-lg-0">

                            <a
                                class="navbar-brand flex-shrink-0 py-0"
                                href="index.php">

                                <img
                                    src="../assets/images/logo.png"
                                    class="logo main-logo"
                                    alt="Furnishop">

                            </a>

                        </div>


                        <!-- MOBILE ACTIONS -->

                        <div class="col-12">

                            <div
                                class="list-inline d-lg-none d-flex justify-content-between">

                                <div class="list-inline-item">

                                    <button
                                        class="navbar-toggler border-0 collapsed"
                                        type="button"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#navbar-default"
                                        aria-controls="navbar-default"
                                        aria-label="Toggle navigation">

                                        <i class="bi bi-text-indent-left"></i>

                                    </button>

                                </div>


                                <div>

                                    <!-- ACCOUNT -->

                                    <div class="list-inline-item me-3">

                                        <a
                                            href="login.php"
                                            class="text-muted d-flex flex-column justify-content-center align-items-center">

                                            <i class="bi bi-person"></i>

                                            <span class="d-none d-md-block">
                                                Account
                                            </span>

                                        </a>

                                    </div>


                                    <!-- WISHLIST -->

                                    <div class="list-inline-item me-3">

                                        <a
                                            href="#"
                                            class="text-muted d-flex flex-column justify-content-center align-items-center">

                                            <i class="bi bi-heart"></i>

                                            <span class="d-none d-md-block">
                                                Wishlist
                                            </span>

                                        </a>

                                    </div>


                                    <!-- CART -->

                                    <div class="list-inline-item">

                                        <a
                                            href="cart.php"
                                            class="text-muted d-flex flex-column justify-content-center align-items-center">

                                            <div class="position-relative">

                                                <i class="bi bi-cart"></i>

                                                <?php if ($cartCount > 0): ?>

                                                    <span
                                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                        <?= $cartCount ?>
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                            <span class="d-none d-md-block">
                                                Your cart
                                            </span>

                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- NAVIGATION -->

                <div
                    class="col-12 col-sm-12 col-md-12 col-lg-7">

                    <nav
                        class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2">

                        <div class="container">

                            <div
                                class="offcanvas offcanvas-start pt-2"
                                tabindex="-1"
                                id="navbar-default"
                                aria-labelledby="navbar-defaultLabel">

                                <div class="offcanvas-header pb-1">

                                    <a href="index.php">

                                        <img
                                            src="../assets/images/logo.png"
                                            alt="Furnishop">

                                    </a>

                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>

                                </div>


                                <div class="offcanvas-body">

                                    <!-- MOBILE SEARCH -->

                                    <div
                                        class="d-block d-lg-none mb-4">

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
                                                    class="btn bg-white border border-start-0"
                                                    type="submit">

                                                    <span class="bi bi-search"></span>

                                                </button>

                                            </div>

                                        </form>

                                    </div>


                                    <!-- MENU -->

                                    <div class="mx-auto">

                                        <ul
                                            class="navbar-nav align-items-center ms-lg-5">

                                            <li
                                                class="nav-item dropdown w-100 w-lg-auto">

                                                <a
                                                    class="nav-link"
                                                    href="index.php">
                                                    Home
                                                </a>

                                            </li>


                                            <li
                                                class="nav-item dropdown w-100 w-lg-auto">

                                                <a
                                                    class="nav-link dropdown-toggle"
                                                    href="#"
                                                    role="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    Shop
                                                </a>

                                                <ul class="dropdown-menu">

                                                    <li>
                                                        <a
                                                            class="dropdown-item"
                                                            href="products.php">
                                                            Products
                                                        </a>
                                                    </li>

                                                    <li>
                                                        <a
                                                            class="dropdown-item"
                                                            href="cart.php">
                                                            Cart
                                                        </a>
                                                    </li>

                                                    <li>
                                                        <a
                                                            class="dropdown-item"
                                                            href="checkout.php">
                                                            Checkout
                                                        </a>
                                                    </li>

                                                </ul>

                                            </li>


                                            <li
                                                class="nav-item dropdown w-100 w-lg-auto">

                                                <a
                                                    class="nav-link"
                                                    href="products.php">
                                                    Categories
                                                </a>

                                            </li>


                                            <li
                                                class="nav-item dropdown w-100 w-lg-auto">

                                                <a
                                                    class="nav-link"
                                                    href="#">
                                                    Pages
                                                </a>

                                            </li>


                                            <li
                                                class="nav-item dropdown w-100 w-lg-auto">

                                                <a
                                                    class="nav-link dropdown-toggle"
                                                    href="#"
                                                    role="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    Account
                                                </a>

                                                <ul class="dropdown-menu">

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

                <div
                    class="col-12 col-sm-12 col-md-12 col-lg-3 d-none d-lg-block">

                    <div class="list-inline d-flex pt-2">

                        <!-- SEARCH -->

                        <div class="list-inline-item me-4">

                            <a
                                href="products.php"
                                class="text-muted d-flex flex-column justify-content-center align-items-center">

                                <i class="bi bi-search"></i>

                                <span>
                                    Search
                                </span>

                            </a>

                        </div>


                        <!-- ACCOUNT -->

                        <div class="list-inline-item me-4">

                            <a
                                href="login.php"
                                class="text-muted d-flex flex-column justify-content-center align-items-center">

                                <i class="bi bi-person"></i>

                                <span>
                                    Account
                                </span>

                            </a>

                        </div>


                        <!-- WISHLIST -->

                        <div class="list-inline-item me-4">

                            <a
                                href="#"
                                class="text-muted d-flex flex-column justify-content-center align-items-center">

                                <i class="bi bi-heart"></i>

                                <span>
                                    Wishlist
                                </span>

                            </a>

                        </div>


                        <!-- CART -->

                        <div class="list-inline-item">

                            <a
                                href="cart.php"
                                class="text-muted d-flex flex-column justify-content-center align-items-center">

                                <div class="position-relative">

                                    <i class="bi bi-cart"></i>

                                    <?php if ($cartCount > 0): ?>

                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            <?= $cartCount ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <span>
                                    Your cart
                                </span>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </header>


    <!-- =========================================================
     MAIN
========================================================= -->

    <main>


        <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

        <div class="breadcrumb-main">

            <div class="container">

                <div class="breadcrumb-container">

                    <h2 class="page-title">
                        Cart
                    </h2>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php">
                                Home
                            </a>

                        </li>

                        <li class="breadcrumb-item active">
                            Cart
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <!-- =====================================================
         CART
    ====================================================== -->

        <div class="product-cart">

            <section class="mt-5 mb-5">

                <div class="container">

                    <div
                        class="row d-flex justify-content-center align-items-center">

                        <div
                            class="col-lg-10 col-md-12 col-sm-12 col-12">

                            <div
                                class="p-lg-5 p-md-0 p-sm-0 p-0">


                                <?php if (empty($cartProducts)): ?>


                                    <!-- =================================================
                                     EMPTY CART
                                ================================================== -->

                                    <div
                                        class="text-center py-5">

                                        <i
                                            class="bi bi-cart-x text-muted cart-empty-icon"></i>

                                        <h3 class="mt-4">
                                            Your Cart is Empty
                                        </h3>

                                        <p class="text-muted">
                                            You haven't added any products
                                            to your cart yet.
                                        </p>

                                        <a
                                            href="products.php"
                                            class="btn btn-primary">

                                            <i class="bi bi-shop me-1"></i>

                                            Continue Shopping

                                        </a>

                                    </div>


                                <?php else: ?>


                                    <!-- =================================================
                                     CART TABLE
                                ================================================== -->

                                    <form
                                        method="POST"
                                        id="updateCartForm">

                                        <div class="table-responsive">

                                            <table
                                                class="table table-bordered align-middle">

                                                <thead>

                                                    <tr>

                                                        <td class="text-center">
                                                            Image
                                                        </td>

                                                        <td class="text-start">
                                                            Product Name
                                                        </td>

                                                        <td class="text-start">
                                                            Category
                                                        </td>

                                                        <td class="text-start">
                                                            Quantity
                                                        </td>

                                                        <td class="text-end">
                                                            Unit Price
                                                        </td>

                                                        <td class="text-end">
                                                            Total
                                                        </td>

                                                    </tr>

                                                </thead>


                                                <tbody>

                                                    <?php foreach ($cartProducts as $product): ?>

                                                        <tr>

                                                            <!-- IMAGE -->

                                                            <td
                                                                class="text-center">

                                                                <a
                                                                    href="product-details.php?id=<?= (int)$product["id"] ?>">

                                                                    <img
                                                                        src="<?= htmlspecialchars(
                                                                                    productImage(
                                                                                        $product["image"]
                                                                                    )
                                                                                ) ?>"
                                                                        alt="<?= htmlspecialchars(
                                                                                    $product["name"]
                                                                                ) ?>"
                                                                        title="<?= htmlspecialchars(
                                                                                    $product["name"]
                                                                                ) ?>"
                                                                        class="img-thumbnail cart-product-image"
                                                                        loading="lazy"
                                                                        onerror="this.src='../assets/images/product/1.png';">

                                                                </a>

                                                            </td>


                                                            <!-- PRODUCT -->

                                                            <td
                                                                class="text-start text-wrap">

                                                                <a
                                                                    href="product-details.php?id=<?= (int)$product["id"] ?>"
                                                                    class="text-decoration-none fw-semibold">

                                                                    <?= htmlspecialchars(
                                                                        $product["name"]
                                                                    ) ?>

                                                                </a>


                                                                <?php if (!empty($product["description"])): ?>

                                                                    <small
                                                                        class="d-block text-muted mt-1">

                                                                        <?= htmlspecialchars(
                                                                            mb_strimwidth(
                                                                                $product["description"],
                                                                                0,
                                                                                70,
                                                                                "..."
                                                                            )
                                                                        ) ?>

                                                                    </small>

                                                                <?php endif; ?>

                                                            </td>


                                                            <!-- CATEGORY -->

                                                            <td
                                                                class="text-start">

                                                                <?= htmlspecialchars(
                                                                    $product["category_name"]
                                                                        ?? "N/A"
                                                                ) ?>

                                                            </td>


                                                            <!-- QUANTITY -->

                                                            <td
                                                                class="text-start">

                                                                <div
                                                                    class="qty-container">

                                                                    <!-- DECREASE -->

                                                                    <button
                                                                        class="qty-btn-minus"
                                                                        type="submit"
                                                                        name="decrease"
                                                                        value="<?= (int)$product["id"] ?>"
                                                                        formnovalidate
                                                                        title="Decrease quantity">

                                                                        <i
                                                                            class="bi bi-dash"></i>

                                                                    </button>


                                                                    <!-- QUANTITY INPUT -->

                                                                    <input
                                                                        type="number"
                                                                        name="quantity[<?= (int)$product["id"] ?>]"
                                                                        value="<?= (int)$product["cart_quantity"] ?>"
                                                                        min="1"
                                                                        max="<?= (int)$product["stock_quantity"] ?>"
                                                                        class="input-qty">


                                                                    <!-- INCREASE -->

                                                                    <button
                                                                        class="qty-btn-plus"
                                                                        type="submit"
                                                                        name="increase"
                                                                        value="<?= (int)$product["id"] ?>"
                                                                        formnovalidate
                                                                        title="Increase quantity">

                                                                        <i
                                                                            class="bi bi-plus"></i>

                                                                    </button>

                                                                </div>


                                                                <small
                                                                    class="text-muted d-block mt-2">

                                                                    Stock:
                                                                    <?= (int)$product["stock_quantity"] ?>

                                                                </small>


                                                                <!-- REMOVE -->

                                                                <div class="mt-3">

                                                                    <button
                                                                        type="submit"
                                                                        name="remove"
                                                                        value="<?= (int)$product["id"] ?>"
                                                                        class="btn btn-danger btn-sm"
                                                                        formnovalidate
                                                                        onclick="return confirm('Are you sure you want to remove this product?');"
                                                                        title="Remove product">

                                                                        <i
                                                                            class="bi bi-trash"></i>

                                                                        Remove

                                                                    </button>

                                                                </div>

                                                            </td>


                                                            <!-- UNIT PRICE -->

                                                            <td
                                                                class="text-end">

                                                                Rs.
                                                                <?= number_format(
                                                                    (float)$product["price"],
                                                                    2
                                                                ) ?>

                                                            </td>


                                                            <!-- ITEM TOTAL -->

                                                            <td
                                                                class="text-end fw-semibold">

                                                                Rs.
                                                                <?= number_format(
                                                                    (float)$product["item_subtotal"],
                                                                    2
                                                                ) ?>

                                                            </td>

                                                        </tr>

                                                    <?php endforeach; ?>

                                                </tbody>


                                                <!-- =================================================
                                                 TOTALS
                                            ================================================== -->

                                                <tfoot id="checkout-total">

                                                    <tr>

                                                        <td
                                                            colspan="5"
                                                            class="text-end">

                                                            <strong>
                                                                Sub-Total
                                                            </strong>

                                                        </td>

                                                        <td
                                                            class="text-end">

                                                            Rs.
                                                            <?= number_format(
                                                                $subtotal,
                                                                2
                                                            ) ?>

                                                        </td>

                                                    </tr>


                                                    <tr>

                                                        <td
                                                            colspan="5"
                                                            class="text-end">

                                                            <strong>
                                                                Shipping
                                                            </strong>

                                                        </td>

                                                        <td
                                                            class="text-end">

                                                            Rs.
                                                            <?= number_format(
                                                                $shipping,
                                                                2
                                                            ) ?>

                                                        </td>

                                                    </tr>


                                                    <tr>

                                                        <td
                                                            colspan="5"
                                                            class="text-end">

                                                            <strong>
                                                                Total
                                                            </strong>

                                                        </td>

                                                        <td
                                                            class="text-end">

                                                            <strong>

                                                                Rs.
                                                                <?= number_format(
                                                                    $total,
                                                                    2
                                                                ) ?>

                                                            </strong>

                                                        </td>

                                                    </tr>

                                                </tfoot>

                                            </table>

                                        </div>


                                        <!-- =================================================
                                         UPDATE CART
                                    ================================================== -->

                                        <div
                                            class="d-flex justify-content-end mt-3">

                                            <button
                                                type="submit"
                                                name="update_cart"
                                                value="1"
                                                class="btn btn-primary">

                                                <i
                                                    class="bi bi-arrow-repeat me-1"></i>

                                                Update Cart

                                            </button>

                                        </div>

                                    </form>


                                    <!-- =================================================
                                     CHECKOUT
                                ================================================== -->

                                    <div
                                        class="checkout-btn mt-5 d-flex justify-content-between align-items-center flex-wrap gap-3">

                                        <a
                                            href="products.php"
                                            class="btn btn-outline-secondary btn-lg">

                                            <i
                                                class="bi bi-arrow-left me-1"></i>

                                            Continue Shopping

                                        </a>


                                        <?php if (isset($_SESSION["user_id"])): ?>

                                            <a
                                                href="checkout.php"
                                                class="btn btn-primary btn-lg">

                                                Checkout

                                                <i
                                                    class="bi bi-arrow-right ms-1"></i>

                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="login.php"
                                                class="btn btn-primary btn-lg">

                                                Login to Checkout

                                                <i
                                                    class="bi bi-arrow-right ms-1"></i>

                                            </a>

                                        <?php endif; ?>

                                    </div>


                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

        </div>

    </main>


    <!-- =========================================================
     FOOTER
========================================================= -->

    <footer class="mt-50">

        <div class="container">

            <div class="row">


                <!-- COMPANY -->

                <div class="col-lg-4 mb-4 mb-md-0">

                    <div class="row">

                        <div
                            class="col-12 col-md-6 col-lg-12">

                            <div class="footer_logo">

                                <img
                                    loading="lazy"
                                    src="../assets/images/logo.png"
                                    class="logo"
                                    alt="Furnishop">

                            </div>

                            <div class="mt-4">

                                <p>

                                    Widgetify Inc, 456 Gadget Avenue,
                                    <br>
                                    Techtown, TX 67890,
                                    <br>
                                    United States of America

                                </p>

                                <h3 class="h5 fw-bold">
                                    (987) 654-3210
                                </h3>

                                <p>
                                    info@example.com
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- MY ACCOUNT -->

                <div
                    class="col-lg-4 mb-3 mb-md-0">

                    <div class="row">

                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">
                                    My Account
                                </h4>

                                <ul
                                    class="m-0 p-0 list-unstyled">

                                    <li>
                                        <a href="login.php">
                                            Login
                                        </a>
                                    </li>

                                    <li>
                                        <a href="#">
                                            Wishlist
                                        </a>
                                    </li>

                                    <li>
                                        <a href="#">
                                            Track Order
                                        </a>
                                    </li>

                                    <li>
                                        <a href="profile.php">
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

                                <ul
                                    class="m-0 p-0 list-unstyled">

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

                                <ul
                                    class="m-0 p-0 list-unstyled">

                                    <li>
                                        <a href="index.php">
                                            Home
                                        </a>
                                    </li>

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

                                <ul
                                    class="m-0 p-0 list-unstyled">

                                    <li>
                                        <a href="products.php">
                                            All Products
                                        </a>
                                    </li>

                                    <li>
                                        <a href="products.php?sort=latest">
                                            Latest
                                        </a>
                                    </li>

                                    <li>
                                        <a href="products.php?sort=low">
                                            Low Price
                                        </a>
                                    </li>

                                    <li>
                                        <a href="products.php?sort=high">
                                            High Price
                                        </a>
                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- COPYRIGHT -->

        <div
            class="text-center py-3 mt-4 text-white px-3 copyright">

            <span>

                Copyright © <?= date("Y") ?>.
                All Rights Reserved.
                Themes By TemplateRise

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


</body>

</html>