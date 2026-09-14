<?php

session_start();

/* =========================================================
   DATABASE
========================================================= */
$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

/* =========================================================
   LOGIN CHECK
========================================================= */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

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
$sameShipping    = true;   // sticky state
$termsAccepted   = false;  // sticky state

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
   IMAGE HELPER (matches index.php)
========================================================= */
function productImage($image)
{
    if (empty($image)) {
        return "../assets/images/product/1.png";
    }
    if (strpos($image, "../Images/") === 0) {
        return $image;
    }
    // DB may store "Images/xxxx.jpg" or just "xxxx.jpg"
    $basename = basename($image);
    return "../Images/" . rawurlencode($basename);
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

            $finalAddress = $shippingAddress;
            if ($shippingApartment !== "") $finalAddress .= ", " . $shippingApartment;
            if ($shippingState     !== "") $finalAddress .= ", " . $shippingState;
            if ($shippingZip       !== "") $finalAddress .= ", " . $shippingZip;

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
                    "iidsid",
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Furnishop</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --fs-primary: #4f46e5;
            --fs-primary-dark: #4338ca;
            --fs-bg: #f5f7fb;
            --fs-border: #e9eef5;
            --fs-text: #0f172a;
            --fs-muted: #64748b;
        }

        * { font-family: 'Poppins', system-ui, -apple-system, sans-serif; }

        body {
            background-color: var(--fs-bg);
            color: var(--fs-text);
            padding-bottom: 40px;
        }

        /* ---------- Navbar ---------- */
        .fs-navbar {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.15);
        }
        .fs-navbar .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .fs-navbar .nav-link {
            font-weight: 500;
            opacity: 0.9;
        }
        .fs-navbar .nav-link:hover { opacity: 1; }

        /* ---------- Page header ---------- */
        .checkout-hero {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border: 1px solid var(--fs-border);
            border-radius: 18px;
            padding: 26px 28px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }
        .checkout-title {
            font-weight: 700;
            font-size: 1.6rem;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .checkout-title i {
            background: linear-gradient(135deg, var(--fs-primary), #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ---------- Step indicator ---------- */
        .step-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 18px;
        }
        .step-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #e0e7ff;
        }
        .step-chip.active {
            background: linear-gradient(135deg, var(--fs-primary), #6366f1);
            color: #fff;
            border-color: transparent;
        }
        .step-chip i { font-size: 0.75rem; }

        /* ---------- Cards ---------- */
        .checkout-card {
            background: #ffffff;
            border: 1px solid var(--fs-border);
            border-radius: 16px;
            padding: 26px;
            margin-bottom: 22px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
            transition: box-shadow 0.2s ease;
        }
        .checkout-card:hover {
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
        }

        .checkout-card h4 {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--fs-text);
        }
        .checkout-card h4 i {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            color: var(--fs-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        /* ---------- Form ---------- */
        .form-label {
            font-weight: 600;
            font-size: 0.84rem;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            font-size: 0.9rem;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: var(--fs-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        .form-check-input:checked {
            background-color: var(--fs-primary);
            border-color: var(--fs-primary);
        }

        /* ---------- Summary ---------- */
        .summary-wrapper {
            position: sticky;
            top: 20px;
        }

        .order-summary { margin-bottom: 0; }

        .summary-product {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .summary-product:last-child { border-bottom: none; }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid var(--fs-border);
            background: #f8fafc;
            flex-shrink: 0;
        }

        .summary-product-name {
            font-weight: 600;
            font-size: 0.88rem;
            line-height: 1.3;
            color: var(--fs-text);
        }
        .summary-product-price {
            font-size: 0.78rem;
            color: var(--fs-muted);
        }

        .total-row {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .place-order-btn {
            width: 100%;
            padding: 13px;
            font-weight: 600;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--fs-primary), #6366f1);
            border: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .place-order-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
        }

        /* ---------- Alerts ---------- */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
        }
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        /* ---------- Payment / shipping options ---------- */
        .option-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid var(--fs-border);
            border-radius: 12px;
            margin-bottom: 10px;
            transition: border-color 0.15s ease, background 0.15s ease;
            cursor: pointer;
        }
        .option-row:hover {
            border-color: #c7d2fe;
            background: #fafbff;
        }
        .option-row.selected {
            border-color: var(--fs-primary);
            background: #eef2ff;
        }
        .option-row .form-check-input { margin-top: 4px; }
        .option-row .form-check-label { cursor: pointer; }
        .option-row .option-price { font-weight: 700; }

        .option-row.disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .option-row.disabled:hover {
            border-color: var(--fs-border);
            background: transparent;
        }

        footer.fs-footer {
            background: #0f172a;
            color: #cbd5e1;
            margin-top: 60px;
            padding: 28px 0;
            border-radius: 20px 20px 0 0;
        }

        @media (max-width: 991px) {
            .summary-wrapper { position: static; }
        }
    </style>
</head>
<body>

<!-- =========================================================
     NAVBAR
========================================================= -->
<nav class="navbar navbar-expand-lg navbar-dark fs-navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fa-solid fa-couch me-1"></i> Furnishop
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        <i class="fa-solid fa-cart-shopping me-1"></i> Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- =========================================================
     MAIN
========================================================= -->
<div class="container py-5">

    <!-- HERO -->
    <div class="checkout-hero">
        <h1 class="checkout-title">
            <i class="fa-solid fa-bag-shopping me-2"></i>
            Secure Checkout
        </h1>
        <p class="text-muted mb-0 mt-2">
            You're one step away from your new furniture. Just confirm your details below.
        </p>

        <div class="step-bar">
            <span class="step-chip active"><i class="fa-solid fa-file-invoice"></i> Billing</span>
            <span class="step-chip"><i class="fa-solid fa-truck"></i> Shipping</span>
            <span class="step-chip"><i class="fa-solid fa-credit-card"></i> Payment</span>
            <span class="step-chip"><i class="fa-solid fa-check"></i> Confirm</span>
        </div>
    </div>

    <!-- ERRORS -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h6 class="fw-bold mb-2">
                <i class="fa-solid fa-circle-exclamation me-1"></i>
                Please fix the following:
            </h6>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="row g-4">

            <!-- =================================================
                 LEFT: FORM
            ================================================== -->
            <div class="col-lg-8">

                <!-- BILLING -->
                <div class="checkout-card">
                    <h4><i class="fa-solid fa-file-invoice"></i> Billing Details</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="billing_first_name">First Name *</label>
                            <input type="text" class="form-control" id="billing_first_name"
                                   name="billing_first_name" autocomplete="given-name"
                                   value="<?= htmlspecialchars($billingFirstName) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="billing_last_name">Last Name *</label>
                            <input type="text" class="form-control" id="billing_last_name"
                                   name="billing_last_name" autocomplete="family-name"
                                   value="<?= htmlspecialchars($billingLastName) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="billing_email">Email *</label>
                            <input type="email" class="form-control" id="billing_email"
                                   name="billing_email" autocomplete="email"
                                   value="<?= htmlspecialchars($billingEmail) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="billing_phone">Phone *</label>
                            <input type="tel" class="form-control" id="billing_phone"
                                   name="billing_phone" autocomplete="tel"
                                   value="<?= htmlspecialchars($billingPhone) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="billing_address">Address *</label>
                            <input type="text" class="form-control" id="billing_address"
                                   name="billing_address" autocomplete="street-address"
                                   placeholder="Street address"
                                   value="<?= htmlspecialchars($billingAddress) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="billing_apartment">Apartment / Suite</label>
                            <input type="text" class="form-control" id="billing_apartment"
                                   name="billing_apartment"
                                   placeholder="Apartment, suite, unit, etc."
                                   value="<?= htmlspecialchars($billingApartment) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="billing_city">City *</label>
                            <input type="text" class="form-control" id="billing_city"
                                   name="billing_city" autocomplete="address-level2"
                                   value="<?= htmlspecialchars($billingCity) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="billing_state">State / Province</label>
                            <input type="text" class="form-control" id="billing_state"
                                   name="billing_state" autocomplete="address-level1"
                                   value="<?= htmlspecialchars($billingState) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="billing_zip">ZIP / Postal Code</label>
                            <input type="text" class="form-control" id="billing_zip"
                                   name="billing_zip" autocomplete="postal-code"
                                   value="<?= htmlspecialchars($billingZip) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="billing_country">Country *</label>
                            <select class="form-select" id="billing_country" name="billing_country"
                                    autocomplete="country-name" required>
                                <?php foreach ($countryList as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"
                                        <?= $billingCountry === $c ? "selected" : "" ?>>
                                        <?= htmlspecialchars($c) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SHIPPING -->
                <div class="checkout-card">
                    <h4><i class="fa-solid fa-truck"></i> Shipping Details</h4>

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
                                       value="<?= htmlspecialchars($shippingFirstName) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_last_name">Last Name *</label>
                                <input type="text" class="form-control" id="shipping_last_name"
                                       name="shipping_last_name"
                                       value="<?= htmlspecialchars($shippingLastName) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_email">Email *</label>
                                <input type="email" class="form-control" id="shipping_email"
                                       name="shipping_email"
                                       value="<?= htmlspecialchars($shippingEmail) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_phone">Phone *</label>
                                <input type="tel" class="form-control" id="shipping_phone"
                                       name="shipping_phone"
                                       value="<?= htmlspecialchars($shippingPhone) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="shipping_address">Address *</label>
                                <input type="text" class="form-control" id="shipping_address"
                                       name="shipping_address"
                                       value="<?= htmlspecialchars($shippingAddress) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="shipping_apartment">Apartment / Suite</label>
                                <input type="text" class="form-control" id="shipping_apartment"
                                       name="shipping_apartment"
                                       value="<?= htmlspecialchars($shippingApartment) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_city">City *</label>
                                <input type="text" class="form-control" id="shipping_city"
                                       name="shipping_city"
                                       value="<?= htmlspecialchars($shippingCity) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_state">State / Province</label>
                                <input type="text" class="form-control" id="shipping_state"
                                       name="shipping_state"
                                       value="<?= htmlspecialchars($shippingState) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_zip">ZIP / Postal Code</label>
                                <input type="text" class="form-control" id="shipping_zip"
                                       name="shipping_zip"
                                       value="<?= htmlspecialchars($shippingZip) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_country">Country *</label>
                                <select class="form-select" id="shipping_country" name="shipping_country">
                                    <?php foreach ($countryList as $c): ?>
                                        <option value="<?= htmlspecialchars($c) ?>"
                                            <?= $shippingCountry === $c ? "selected" : "" ?>>
                                            <?= htmlspecialchars($c) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SHIPPING METHOD -->
                <div class="checkout-card">
                    <h4><i class="fa-solid fa-box"></i> Shipping Method</h4>

                    <label class="option-row selected">
                        <div class="d-flex align-items-start gap-3 flex-grow-1">
                            <input class="form-check-input" type="radio"
                                   name="shipping_method" id="standard_shipping"
                                   value="Standard" checked>
                            <div>
                                <div class="fw-semibold">Standard Shipping</div>
                                <small class="text-muted">Delivery within 3–7 working days</small>
                            </div>
                        </div>
                        <div class="option-price text-nowrap">
                            Rs. <?= number_format(SHIPPING_FLAT_RATE, 2) ?>
                        </div>
                    </label>
                </div>

                <!-- PAYMENT METHOD -->
                <div class="checkout-card">
                    <h4><i class="fa-solid fa-credit-card"></i> Payment Method</h4>

                    <label class="option-row selected" for="cod">
                        <div class="d-flex align-items-start gap-3 flex-grow-1">
                            <input class="form-check-input" type="radio"
                                   name="payment_method" id="cod"
                                   value="Cash on Delivery" checked>
                            <div>
                                <div class="fw-semibold">Cash on Delivery</div>
                                <small class="text-muted">Pay when your order is delivered.</small>
                            </div>
                        </div>
                    </label>

                    <label class="option-row disabled">
                        <div class="d-flex align-items-start gap-3 flex-grow-1">
                            <input class="form-check-input" type="radio" disabled>
                            <div>
                                <div class="fw-semibold">PayPal</div>
                                <small class="text-muted">Coming soon</small>
                            </div>
                        </div>
                    </label>

                    <label class="option-row disabled">
                        <div class="d-flex align-items-start gap-3 flex-grow-1">
                            <input class="form-check-input" type="radio" disabled>
                            <div>
                                <div class="fw-semibold">Razorpay</div>
                                <small class="text-muted">Coming soon</small>
                            </div>
                        </div>
                    </label>

                    <label class="option-row disabled">
                        <div class="d-flex align-items-start gap-3 flex-grow-1">
                            <input class="form-check-input" type="radio" disabled>
                            <div>
                                <div class="fw-semibold">Stripe</div>
                                <small class="text-muted">Coming soon</small>
                            </div>
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

            <!-- =================================================
                 RIGHT: ORDER SUMMARY
            ================================================== -->
            <div class="col-lg-4">
                <div class="summary-wrapper">
                    <div class="checkout-card order-summary">
                        <h4><i class="fa-solid fa-receipt"></i> Order Summary</h4>

                        <?php foreach ($cartProducts as $product): ?>
                            <div class="summary-product">
                                <?php $imgSrc = productImage($product["image"]); ?>

                                <?php if (!empty($product["image"])): ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>"
                                         alt="<?= htmlspecialchars($product["name"]) ?>"
                                         class="product-image"
                                         onerror="this.onerror=null;this.src='../assets/images/product/1.png';">
                                <?php else: ?>
                                    <div class="product-image d-flex align-items-center justify-content-center">
                                        <i class="fa-solid fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="flex-grow-1">
                                    <div class="summary-product-name">
                                        <?= htmlspecialchars($product["name"]) ?>
                                    </div>
                                    <div class="summary-product-price">
                                        <?= $product["cart_quantity"] ?> ×
                                        Rs. <?= number_format($product["price"], 2) ?>
                                    </div>
                                </div>

                                <div class="fw-bold small">
                                    Rs. <?= number_format($product["item_subtotal"], 2) ?>
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
                            <i class="fa-solid fa-lock me-2"></i>
                            Confirm Order
                        </button>

                        <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2"
                           style="border-radius:10px; padding:11px; font-weight:600;">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Back to Cart
                        </a>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fa-solid fa-shield-halved me-1"></i>
                                Your information is secure and encrypted.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- =========================================================
     FOOTER
========================================================= -->
<footer class="fs-footer">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-1"><i class="fa-solid fa-couch me-1"></i> Furnishop</h5>
                <p class="text-white-50 mb-0 small">Your trusted online furniture store.</p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <p class="mb-0 text-white-50 small">
                    &copy; <?= date("Y") ?> Furnishop. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /* ===== Shipping toggle ===== */
    (function () {
        var sameShipping   = document.getElementById("same_shipping");
        var shippingFields = document.getElementById("shippingFields");

        function toggle() {
            shippingFields.style.display = sameShipping.checked ? "none" : "block";
        }
        sameShipping.addEventListener("change", toggle);
        toggle();
    })();

    /* ===== Selectable option rows (shipping + payment) ===== */
    (function () {
        document.querySelectorAll('input[type="radio"]:not([disabled])').forEach(function (radio) {
            radio.addEventListener("change", function () {
                document.querySelectorAll(".option-row").forEach(function (row) {
                    var input = row.querySelector('input[type="radio"]');
                    if (!input) return;
                    row.classList.toggle("selected", input.checked);
                });
            });
        });
    })();
</script>

</body>
</html>