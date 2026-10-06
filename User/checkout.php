<?php

session_start();

/* =========================================================
   DATABASE
========================================================= */
require_once __DIR__ . "/../Config/database.php";

/* =========================================================
   LOGIN CHECK
========================================================= */
require_once __DIR__ . "/../Includes/functions.php";
require_customer_login($connect, null);

$userId = (int) $_SESSION["user_id"];

/* =========================================================
   CSRF TOKEN
========================================================= */
if (empty($_SESSION["checkout_csrf"])) {
    $_SESSION["checkout_csrf"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["checkout_csrf"];

/* =========================================================
   CONSTANTS
========================================================= */
const SHIPPING_FLAT_RATE = 200.0;
const DEFAULT_COUNTRY    = "Pakistan";

$countryList = [
    "Pakistan",
    "India",
    "United Arab Emirates",
    "United Kingdom",
    "United States",
];

/* =========================================================
   INITIALIZE CART
========================================================= */
if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if (empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit;
}

/* =========================================================
   VARIABLES
========================================================= */
$errors = [];

$billingFirstName = "";
$billingLastName  = "";
$billingEmail     = "";
$billingPhone     = "";
$billingAddress   = "";
$billingApartment = "";
$billingCity      = "";
$billingState     = "";
$billingZip       = "";
$billingCountry   = DEFAULT_COUNTRY;

$shippingFirstName = "";
$shippingLastName  = "";
$shippingEmail     = "";
$shippingPhone     = "";
$shippingAddress   = "";
$shippingApartment = "";
$shippingCity      = "";
$shippingState     = "";
$shippingZip       = "";
$shippingCountry   = DEFAULT_COUNTRY;

$paymentMethod   = "Cash on Delivery";
$sameShipping    = true;
$termsAccepted   = false;

/* =========================================================
   GET USER INFORMATION
========================================================= */
$userQuery = mysqli_prepare(
    $connect,
    "SELECT name, email, phone, address, city, country
     FROM users
     WHERE id = ?"
);
mysqli_stmt_bind_param($userQuery, "i", $userId);
mysqli_stmt_execute($userQuery);

$userResult = mysqli_stmt_get_result($userQuery);
$user = $userResult ? mysqli_fetch_assoc($userResult) : null;

mysqli_stmt_close($userQuery);

if ($user) {
    $nameParts = explode(" ", trim($user["name"]), 2);

    $billingFirstName = $nameParts[0] ?? "";
    $billingLastName  = $nameParts[1] ?? "";

    $billingEmail   = $user["email"]   ?? "";
    $billingPhone   = $user["phone"]   ?? "";
    $billingAddress = $user["address"] ?? "";
    $billingCity    = $user["city"]    ?? "";
    $billingCountry = $user["country"] ?? DEFAULT_COUNTRY;
}

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
        return "../Assets/Images/product/1.png";
    }
    if (strpos($image, "../Assets/Images/product/") === 0) {
        return $image;
    }
    return "../Assets/Images/product/" . rawurlencode(basename($image));
}

/* =========================================================
   FETCH ACTIVE CATEGORIES (for navbar mega menu)
========================================================= */
$categories = [];

$catRes = mysqli_query(
    $connect,
    "SELECT id, name
     FROM categories
     WHERE status = 'Active'
     ORDER BY name ASC
     LIMIT 8"
);

if ($catRes) {
    while ($row = mysqli_fetch_assoc($catRes)) {
        $categories[] = $row;
    }
}

/* =========================================================
   LOAD CART PRODUCTS
========================================================= */
$cartProducts = [];
$subtotal     = 0;

foreach ($_SESSION["cart"] as $productId => $quantity) {

    $productId = (int) $productId;
    $quantity  = (int) $quantity;

    if ($productId <= 0 || $quantity <= 0) {
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
            c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.id = ? AND p.status = 'Active'"
    );

    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);

    $result  = mysqli_stmt_get_result($stmt);
    $product = $result ? mysqli_fetch_assoc($result) : null;

    mysqli_stmt_close($stmt);

    if (!$product) {
        unset($_SESSION["cart"][$productId]);
        continue;
    }

    $stock = (int) $product["stock_quantity"];

    if ($stock <= 0) {
        unset($_SESSION["cart"][$productId]);
        continue;
    }

    if ($quantity > $stock) {
        $quantity = $stock;
        $_SESSION["cart"][$productId] = $quantity;
    }

    $price        = (float) $product["price"];
    $itemSubtotal = $price * $quantity;

    $subtotal += $itemSubtotal;

    $product["cart_quantity"] = $quantity;
    $product["item_subtotal"] = $itemSubtotal;

    $cartProducts[] = $product;
}

if (empty($cartProducts)) {
    $_SESSION["cart"] = [];
    header("Location: cart.php");
    exit;
}

/* =========================================================
   SHIPPING + TOTAL
========================================================= */
$shipping = SHIPPING_FLAT_RATE;
$total    = $subtotal + $shipping;

/* =========================================================
   CHECKOUT FORM SUBMISSION
========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* -------- CSRF -------- */
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["checkout_csrf"], $_POST["csrf_token"])
    ) {
        $errors[] = "Invalid session. Please refresh the page and try again.";
    }

    /* -------- BILLING -------- */
    $billingFirstName = trim($_POST["billing_first_name"] ?? "");
    $billingLastName  = trim($_POST["billing_last_name"]  ?? "");
    $billingEmail     = trim($_POST["billing_email"]      ?? "");
    $billingPhone     = trim($_POST["billing_phone"]      ?? "");
    $billingAddress   = trim($_POST["billing_address"]    ?? "");
    $billingApartment = trim($_POST["billing_apartment"]  ?? "");
    $billingCity      = trim($_POST["billing_city"]       ?? "");
    $billingState     = trim($_POST["billing_state"]      ?? "");
    $billingZip       = trim($_POST["billing_zip"]        ?? "");
    $billingCountry   = trim($_POST["billing_country"]    ?? DEFAULT_COUNTRY);

    /* -------- SHIPPING -------- */
    $sameShipping = isset($_POST["same_shipping"]);

    if ($sameShipping) {
        $shippingFirstName = $billingFirstName;
        $shippingLastName  = $billingLastName;
        $shippingEmail     = $billingEmail;
        $shippingPhone     = $billingPhone;
        $shippingAddress   = $billingAddress;
        $shippingApartment = $billingApartment;
        $shippingCity      = $billingCity;
        $shippingState     = $billingState;
        $shippingZip       = $billingZip;
        $shippingCountry   = $billingCountry;
    } else {
        $shippingFirstName = trim($_POST["shipping_first_name"] ?? "");
        $shippingLastName  = trim($_POST["shipping_last_name"]  ?? "");
        $shippingEmail     = trim($_POST["shipping_email"]      ?? "");
        $shippingPhone     = trim($_POST["shipping_phone"]      ?? "");
        $shippingAddress   = trim($_POST["shipping_address"]    ?? "");
        $shippingApartment = trim($_POST["shipping_apartment"]  ?? "");
        $shippingCity      = trim($_POST["shipping_city"]       ?? "");
        $shippingState     = trim($_POST["shipping_state"]      ?? "");
        $shippingZip       = trim($_POST["shipping_zip"]        ?? "");
        $shippingCountry   = trim($_POST["shipping_country"]    ?? DEFAULT_COUNTRY);
    }

    /* -------- VALIDATION: BILLING -------- */
    if ($billingFirstName === "") $errors[] = "Billing first name is required.";
    if ($billingLastName  === "") $errors[] = "Billing last name is required.";

    if ($billingEmail === "") {
        $errors[] = "Billing email is required.";
    } elseif (!filter_var($billingEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid billing email.";
    }

    if ($billingPhone   === "") $errors[] = "Billing phone number is required.";
    if ($billingAddress === "") $errors[] = "Billing address is required.";
    if ($billingCity    === "") $errors[] = "Billing city is required.";
    if ($billingCountry === "") $errors[] = "Billing country is required.";

    /* -------- VALIDATION: SHIPPING -------- */
    if (!$sameShipping) {
        if ($shippingFirstName === "") $errors[] = "Shipping first name is required.";
        if ($shippingLastName  === "") $errors[] = "Shipping last name is required.";

        if ($shippingEmail === "") {
            $errors[] = "Shipping email is required.";
        } elseif (!filter_var($shippingEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid shipping email.";
        }

        if ($shippingPhone   === "") $errors[] = "Shipping phone number is required.";
        if ($shippingAddress === "") $errors[] = "Shipping address is required.";
        if ($shippingCity    === "") $errors[] = "Shipping city is required.";
        if ($shippingCountry === "") $errors[] = "Shipping country is required.";
    }

    /* -------- PAYMENT -------- */
    $paymentMethod = $_POST["payment_method"] ?? "Cash on Delivery";

    if ($paymentMethod !== "Cash on Delivery") {
        $errors[] = "Only Cash on Delivery is currently available.";
    }

    /* -------- TERMS -------- */
    $termsAccepted = isset($_POST["terms"]);
    if (!$termsAccepted) {
        $errors[] = "You must agree to the terms and conditions.";
    }

    /* =====================================================
       CREATE ORDER
    ===================================================== */
    if (empty($errors)) {

        mysqli_begin_transaction($connect);

        try {

            $finalSubtotal = 0;
            $finalCart     = [];

            foreach ($_SESSION["cart"] as $productId => $quantity) {

                $productId = (int) $productId;
                $quantity  = (int) $quantity;

                if ($productId <= 0 || $quantity <= 0) {
                    throw new Exception("Invalid product in cart.");
                }

                $stmt = mysqli_prepare(
                    $connect,
                    "SELECT id, name, price, stock_quantity, status
                     FROM products
                     WHERE id = ?
                     FOR UPDATE"
                );

                mysqli_stmt_bind_param($stmt, "i", $productId);
                mysqli_stmt_execute($stmt);

                $result  = mysqli_stmt_get_result($stmt);
                $product = $result ? mysqli_fetch_assoc($result) : null;

                mysqli_stmt_close($stmt);

                if (!$product) {
                    throw new Exception("A product in your cart no longer exists.");
                }

                if ($product["status"] !== "Active") {
                    throw new Exception(
                        "Product '" . $product["name"] . "' is no longer available."
                    );
                }

                $stock = (int) $product["stock_quantity"];

                if ($stock < $quantity) {
                    throw new Exception(
                        "Not enough stock available for '" . $product["name"] . "'."
                    );
                }

                $price        = (float) $product["price"];
                $itemSubtotal = $price * $quantity;

                $finalSubtotal += $itemSubtotal;

                $finalCart[] = [
                    "id"       => $productId,
                    "name"     => $product["name"],
                    "price"    => $price,
                    "quantity" => $quantity,
                    "subtotal" => $itemSubtotal,
                ];
            }

            $finalShipping = SHIPPING_FLAT_RATE;
            $finalTotal    = $finalSubtotal + $finalShipping;

            $customerName = trim($shippingFirstName . " " . $shippingLastName);

            $addressParts = [];

            if ($shippingApartment !== "") $addressParts[] = $shippingApartment;
            if ($shippingAddress   !== "") $addressParts[] = $shippingAddress;
            if ($shippingCity      !== "") $addressParts[] = $shippingCity;
            if ($shippingState     !== "") $addressParts[] = $shippingState;
            if ($shippingZip       !== "") $addressParts[] = $shippingZip;

            $finalAddress = implode(", ", $addressParts);

            $orderStatus = "Pending";

            /* -------- INSERT ORDER -------- */
            $stmt = mysqli_prepare(
                $connect,
                "INSERT INTO orders
                (user_id, customer_name, email, phone, address, city, country,
                 subtotal, shipping, total, payment_method, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "issssssdddss",
                $userId,
                $customerName,
                $shippingEmail,
                $shippingPhone,
                $finalAddress,
                $shippingCity,
                $shippingCountry,
                $finalSubtotal,
                $finalShipping,
                $finalTotal,
                $paymentMethod,
                $orderStatus
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to create order: " . mysqli_stmt_error($stmt));
            }

            $orderId = mysqli_insert_id($connect);
            mysqli_stmt_close($stmt);

            /* -------- INSERT ORDER ITEMS + REDUCE STOCK -------- */
            foreach ($finalCart as $item) {

                $productId    = $item["id"];
                $productName  = $item["name"];
                $price        = $item["price"];
                $quantity     = $item["quantity"];
                $itemSubtotal = $item["subtotal"];

                $stmt = mysqli_prepare(
                    $connect,
                    "INSERT INTO order_items
                    (order_id, product_id, product_name, price, quantity, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iisdid",
                    $orderId,
                    $productId,
                    $productName,
                    $price,
                    $quantity,
                    $itemSubtotal
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to add order item: " . mysqli_stmt_error($stmt));
                }

                mysqli_stmt_close($stmt);

                /* Reduce stock */
                $stmt = mysqli_prepare(
                    $connect,
                    "UPDATE products
                     SET stock_quantity = stock_quantity - ?
                     WHERE id = ? AND stock_quantity >= ?"
                );

                mysqli_stmt_bind_param($stmt, "iii", $quantity, $productId, $quantity);

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to update product stock.");
                }

                if (mysqli_stmt_affected_rows($stmt) !== 1) {
                    throw new Exception("Stock could not be updated for product.");
                }

                mysqli_stmt_close($stmt);
            }

            mysqli_commit($connect);

            $_SESSION["cart"] = [];
            unset($_SESSION["checkout_csrf"]);

            header("Location: order-success.php?order_id=" . $orderId);
            exit;

        } catch (Exception $e) {
            mysqli_rollback($connect);
            $errors[] = $e->getMessage();
        }
    }
}

/* =========================================================
   CART COUNT
========================================================= */
$cartCount = 0;
foreach ($_SESSION["cart"] as $quantity) {
    $cartCount += (int) $quantity;
}

/* =========================================================
   LOGIN STATUS
========================================================= */
$isLoggedIn = isset($_SESSION["user_id"]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Furnishop</title>
    <meta name="description" content="Secure Checkout">
    <link rel="icon" type="image/x-icon" href="../Assets/Images/favicon.ico">

    <link rel="stylesheet" href="../Assets/CSS/bootstrap.min.css">
    <link rel="stylesheet" href="../Assets/Font/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/nice-select/nice-select.css">
    <link rel="stylesheet" href="../Assets/Plugin/OwlCarousel2-2.3.4/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/OwlCarousel2-2.3.4/dist/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/nouislider/nouislider.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/slick/slick.css">
    <link rel="stylesheet" href="../Assets/CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* CHECKOUT CARDS */
        .checkout-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .checkout-card h4 {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkout-card h4 i {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #FEF6F0;
            color: var(--theme-default);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            color: #333;
            margin-bottom: 6px;
        }
        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 10px 14px;
            font-size: 0.9rem;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: var(--theme-default);
            box-shadow: 0 0 0 3px rgba(140, 89, 59, 0.15);
        }
        .form-check-input:checked {
            background-color: var(--theme-default);
            border-color: var(--theme-default);
        }

        /* OPTION ROW (payment) */
        .option-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
        }
        .option-row.selected {
            border-color: var(--theme-default);
            background: #FEF6F0;
        }
        .option-row .form-check-input { margin-top: 4px; }

        /* ORDER SUMMARY */
        .summary-wrapper { position: sticky; top: 20px; }

        .summary-product {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f5;
        }
        .summary-product:last-child { border-bottom: none; }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            background: #f8f9fa;
            flex-shrink: 0;
        }
        .summary-product-name {
            font-weight: 600;
            font-size: 0.88rem;
            line-height: 1.3;
        }
        .summary-product-price {
            font-size: 0.78rem;
            color: #6c757d;
        }
        .total-row { font-size: 1.15rem; font-weight: 700; }

        .place-order-btn {
            width: 100%;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
        }

        @media (max-width: 991px) {
            .summary-wrapper { position: static; }
        }
    </style>
</head>
<body>

    <!-- =========================
         HEADER (matches index.php)
    ========================== -->
    <header>
        <div class="container py-lg-2 mt-0 mt-lg-2">
            <div class="row">

                <div class="col-12 col-lg-2 mb-2 mb-lg-3 pt-3 pt-lg-2">
                    <div class="row">
                        <div class="col-12 d-flex justify-content-center mb-3 mb-lg-0">
                            <a class="navbar-brand flex-shrink-0 py-0" href="index.php">
                                <img src="../Assets/Images/logo.png" class="logo main-logo" alt="Furnishop">
                            </a>
                        </div>
                        <div class="col-12">
                            <div class="list-inline d-lg-none d-flex justify-content-between">
                                <div class="list-inline-item d-inline-block d-lg-none">
                                    <button class="navbar-toggler border-0 collapsed" type="button"
                                            data-bs-toggle="offcanvas" data-bs-target="#navbar-default"
                                            aria-controls="navbar-default" aria-label="Toggle navigation">
                                        <i class="bi bi-text-indent-left"></i>
                                    </button>
                                </div>
                                <div>
                                    <div class="list-inline-item me-4">
                                        <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"
                                           class="text-muted d-flex flex-column justify-content-center align-items-center">
                                            <i class="bi bi-person"></i>
                                            <span class="d-none d-lg-block">Account</span>
                                        </a>
                                    </div>
                                    <div class="list-inline-item me-4">
                                        <a href="cart.php"
                                           class="text-muted d-flex flex-column justify-content-center align-items-center">
                                            <div class="position-relative">
                                                <i class="bi bi-cart"></i>
                                                <?php if ($cartCount > 0): ?>
                                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                        <?= $cartCount ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="d-none d-lg-block">Your cart</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <nav class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2"
                         aria-label="Offcanvas navbar large">
                        <div class="container">
                            <div class="offcanvas offcanvas-start pt-2" tabindex="-1" id="navbar-default"
                                 aria-labelledby="navbar-defaultLabel">

                                <div class="offcanvas-header pb-1">
                                    <a href="index.php">
                                        <img src="../Assets/Images/logo.png" alt="Furnishop">
                                    </a>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                            aria-label="Close"></button>
                                </div>

                                <div class="offcanvas-body">
                                    <div class="d-block d-lg-none mb-4">
                                        <form action="products.php" method="GET">
                                            <div class="input-group">
                                                <input class="form-control" type="search" name="search"
                                                       placeholder="Search for products">
                                                <span class="input-group-append">
                                                    <button class="btn bg-white border border-start-0 ms-n10 rounded-0 rounded-end"
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
                                                    <li><a class="dropdown-item" href="products.php">All Products</a></li>
                                                    <li><a class="dropdown-item" href="cart.php">Shopping Cart</a></li>
                                                    <li><a class="dropdown-item" href="checkout.php">Checkout</a></li>
                                                </ul>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto dropdown-fullwidth">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                                                <div class="dropdown-menu category-mega-menu">
                                                    <div class="category-mega-inner">
                                                        <div class="category-mega-heading">
                                                            <span>Shop by Category</span>
                                                            <a href="products.php">View All Products</a>
                                                        </div>
                                                        <?php if (!empty($categories)): ?>
                                                            <div class="category-mega-grid">
                                                                <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                                                                    <a class="category-menu-item"
                                                                       href="products.php?category=<?= (int) $cat["id"] ?>">
                                                                        <span class="category-menu-icon"><i class="bi bi-grid"></i></span>
                                                                        <span>
                                                                            <strong><?= e($cat["name"]) ?></strong>
                                                                        </span>
                                                                        <i class="bi bi-arrow-right category-menu-arrow"></i>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <p class="text-muted mb-0">No categories yet.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">Account</a>
                                                <ul class="dropdown-menu">
                                                    <?php if ($isLoggedIn): ?>
                                                        <li><a class="dropdown-item" href="profile.php">My Account</a></li>
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

                <div class="col-12 col-lg-3 d-none d-lg-block">
                    <div class="list-inline d-flex pt-2">
                        <div class="list-inline-item me-4">
                            <a href="products.php"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-search"></i>
                                <span class="d-none d-lg-block">Search</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-person"></i>
                                <span class="d-none d-lg-block">Account</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="cart.php"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <div class="position-relative">
                                    <i class="bi bi-cart"></i>
                                    <?php if ($cartCount > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            <?= $cartCount ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="d-none d-lg-block">Your cart</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>
    <!-- Header End -->

    <!-- =========================
         MAIN
    ========================== -->
    <main class="container py-5">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h6 class="fw-bold mb-2">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Please fix the following:
                </h6>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="row g-4">

                <div class="col-lg-8">

                    <!-- BILLING -->
                    <div class="checkout-card">
                        <h4><i class="bi bi-receipt"></i> Billing Details</h4>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="billing_first_name">First Name *</label>
                                <input type="text" class="form-control" id="billing_first_name"
                                       name="billing_first_name" autocomplete="given-name"
                                       value="<?= e($billingFirstName) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_last_name">Last Name *</label>
                                <input type="text" class="form-control" id="billing_last_name"
                                       name="billing_last_name" autocomplete="family-name"
                                       value="<?= e($billingLastName) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_email">Email *</label>
                                <input type="email" class="form-control" id="billing_email"
                                       name="billing_email" autocomplete="email"
                                       value="<?= e($billingEmail) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_phone">Phone *</label>
                                <input type="tel" class="form-control" id="billing_phone"
                                       name="billing_phone" autocomplete="tel"
                                       value="<?= e($billingPhone) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="billing_address">Address *</label>
                                <input type="text" class="form-control" id="billing_address"
                                       name="billing_address" autocomplete="street-address"
                                       placeholder="Street address"
                                       value="<?= e($billingAddress) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="billing_apartment">Apartment / Suite</label>
                                <input type="text" class="form-control" id="billing_apartment"
                                       name="billing_apartment"
                                       placeholder="Apartment, suite, unit, etc."
                                       value="<?= e($billingApartment) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_city">City *</label>
                                <input type="text" class="form-control" id="billing_city"
                                       name="billing_city" autocomplete="address-level2"
                                       value="<?= e($billingCity) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_state">State / Province</label>
                                <input type="text" class="form-control" id="billing_state"
                                       name="billing_state" autocomplete="address-level1"
                                       value="<?= e($billingState) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_zip">ZIP / Postal Code</label>
                                <input type="text" class="form-control" id="billing_zip"
                                       name="billing_zip" autocomplete="postal-code"
                                       value="<?= e($billingZip) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_country">Country *</label>
                                <select class="form-select" id="billing_country" name="billing_country"
                                        autocomplete="country-name" required>
                                    <?php foreach ($countryList as $c): ?>
                                        <option value="<?= e($c) ?>"
                                            <?= $billingCountry === $c ? "selected" : "" ?>>
                                            <?= e($c) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SHIPPING -->
                    <div class="checkout-card">
                        <h4><i class="bi bi-truck"></i> Shipping Details</h4>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox"
                                   id="same_shipping" name="same_shipping"
                                   <?= $sameShipping ? "checked" : "" ?>>
                            <label class="form-check-label" for="same_shipping">
                                Shipping address is the same as billing address
                            </label>
                        </div>

                        <div id="shippingFields" style="<?= $sameShipping ? 'display:none;' : '' ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="shipping_first_name">First Name *</label>
                                    <input type="text" class="form-control" id="shipping_first_name"
                                           name="shipping_first_name"
                                           value="<?= e($shippingFirstName) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="shipping_last_name">Last Name *</label>
                                    <input type="text" class="form-control" id="shipping_last_name"
                                           name="shipping_last_name"
                                           value="<?= e($shippingLastName) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="shipping_email">Email *</label>
                                    <input type="email" class="form-control" id="shipping_email"
                                           name="shipping_email"
                                           value="<?= e($shippingEmail) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="shipping_phone">Phone *</label>
                                    <input type="tel" class="form-control" id="shipping_phone"
                                           name="shipping_phone"
                                           value="<?= e($shippingPhone) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="shipping_address">Address *</label>
                                    <input type="text" class="form-control" id="shipping_address"
                                           name="shipping_address"
                                           value="<?= e($shippingAddress) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="shipping_apartment">Apartment / Suite</label>
                                    <input type="text" class="form-control" id="shipping_apartment"
                                           name="shipping_apartment"
                                           value="<?= e($shippingApartment) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="shipping_city">City *</label>
                                    <input type="text" class="form-control" id="shipping_city"
                                           name="shipping_city"
                                           value="<?= e($shippingCity) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="shipping_state">State / Province</label>
                                    <input type="text" class="form-control" id="shipping_state"
                                           name="shipping_state"
                                           value="<?= e($shippingState) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="shipping_zip">ZIP / Postal Code</label>
                                    <input type="text" class="form-control" id="shipping_zip"
                                           name="shipping_zip"
                                           value="<?= e($shippingZip) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="shipping_country">Country *</label>
                                    <select class="form-select" id="shipping_country" name="shipping_country">
                                        <?php foreach ($countryList as $c): ?>
                                            <option value="<?= e($c) ?>"
                                                <?= $shippingCountry === $c ? "selected" : "" ?>>
                                                <?= e($c) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT -->
                    <div class="checkout-card">
                        <h4><i class="bi bi-credit-card"></i> Payment Method</h4>

                        <label class="option-row selected" for="cod">
                            <input class="form-check-input" type="radio"
                                   name="payment_method" id="cod"
                                   value="Cash on Delivery" checked>
                            <div>
                                <div class="fw-semibold">Cash on Delivery</div>
                                <small class="text-muted">Pay when your order is delivered.</small>
                            </div>
                        </label>
                    </div>

                    <!-- TERMS -->
                    <div class="checkout-card">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   id="terms" name="terms"
                                   <?= $termsAccepted ? "checked" : "" ?> required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms and Conditions</a>
                                and <a href="#" class="text-decoration-none">Privacy Policy</a>.
                            </label>
                        </div>
                    </div>

                </div>

                <!-- ORDER SUMMARY -->
                <div class="col-lg-4">
                    <div class="summary-wrapper">
                        <div class="checkout-card">
                            <h4><i class="bi bi-bag"></i> Order Summary</h4>

                            <?php foreach ($cartProducts as $product): ?>
                                <div class="summary-product">
                                    <?php $imgSrc = productImage($product["image"]); ?>

                                    <?php if (!empty($product["image"])): ?>
                                        <img src="<?= e($imgSrc) ?>"
                                             alt="<?= e($product["name"]) ?>"
                                             class="product-image"
                                             onerror="this.onerror=null;this.src='../Assets/Images/product/1.png';">
                                    <?php else: ?>
                                        <div class="product-image d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex-grow-1">
                                        <div class="summary-product-name">
                                            <?= e($product["name"]) ?>
                                        </div>
                                        <div class="summary-product-price">
                                            <?= (int) $product["cart_quantity"] ?> ×
                                            Rs. <?= number_format((float) $product["price"], 2) ?>
                                        </div>
                                    </div>

                                    <div class="fw-bold small">
                                        Rs. <?= number_format((float) $product["item_subtotal"], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <strong>Rs. <?= number_format($subtotal, 2) ?></strong>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Shipping</span>
                                <strong>Rs. <?= number_format($shipping, 2) ?></strong>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between total-row mb-4">
                                <span>Total</span>
                                <span>Rs. <?= number_format($total, 2) ?></span>
                            </div>

                            <button type="submit" class="btn btn-primary place-order-btn">
                                <i class="bi bi-lock me-2"></i>
                                Confirm Order
                            </button>

                            <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2"
                               style="border-radius:8px; padding:11px; font-weight:600;">
                                <i class="bi bi-arrow-left me-1"></i>
                                Back to Cart
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </main>

    <!-- =========================
         FOOTER (matches index.php)
    ========================== -->
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
                                    <?php if (!$isLoggedIn): ?>
                                        <li><a href="login.php">Login</a></li>
                                        <li><a href="register.php">Register</a></li>
                                    <?php else: ?>
                                        <li><a href="profile.php">My Account</a></li>
                                        <li><a href="orders.php">My Orders</a></li>
                                        <li><a href="logout.php">Logout</a></li>
                                    <?php endif; ?>
                                    <li><a href="cart.php">Cart</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Shopping</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <li><a href="products.php">Products</a></li>
                                    <li><a href="checkout.php">Checkout</a></li>
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
                                    <li><a href="cart.php">Shopping Cart</a></li>
                                    <li><a href="orders.php">My Orders</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Categories</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
                                            <li>
                                                <a href="products.php?category=<?= (int) $cat["id"] ?>">
                                                    <?= e($cat["name"]) ?>
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

    <!-- =========================
         SCRIPTS
    ========================== -->
    <script src="../Assets/JS/jquery-3.6.0.min.js"></script>
    <script src="../Assets/JS/bootstrap.bundle.min.js"></script>
    <script src="../Assets/Plugin/nice-select/jquery.nice-select.min.js"></script>
    <script src="../Assets/Plugin/OwlCarousel2-2.3.4/dist/owl.carousel.min.js"></script>
    <script src="../Assets/Plugin/nouislider/nouislider.min.js"></script>
    <script src="../Assets/Plugin/slick/slick.min.js"></script>
    <script src="../Assets/JS/main.js"></script>

    <script>
        (function () {
            var sameShipping   = document.getElementById("same_shipping");
            var shippingFields = document.getElementById("shippingFields");

            function toggle() {
                shippingFields.style.display = sameShipping.checked ? "none" : "block";
            }
            sameShipping.addEventListener("change", toggle);
            toggle();
        })();
    </script>
</body>
</html>