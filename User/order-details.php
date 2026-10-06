<?php

/* =========================================================
   ORDER DETAILS PAGE
   Furnishop - Customer Order Details
========================================================= */

session_start();

require_once __DIR__ . "/../Config/database.php";


/* =========================================================
   LOGIN CHECK
========================================================= */

require_once __DIR__ . "/../Includes/functions.php";
require_customer_login($connect, "order-details.php?order_id=" . (int) ($_GET["order_id"] ?? 0));


/* =========================================================
   USER ID
========================================================= */

$userId = (int) $_SESSION["user_id"];


/* =========================================================
   GET ORDER ID
========================================================= */

$orderId = filter_input(
    INPUT_GET,
    "order_id",
    FILTER_VALIDATE_INT
);

if (!$orderId || $orderId <= 0) {
    header("Location: orders.php");
    exit;
}


/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function productImage($image)
{
    $fallback = "../Assets/Images/product/1.png";

    $image = trim((string) $image);

    if ($image === "") {
        return $fallback;
    }

    if (strpos($image, "../Assets/Images/product/") === 0) {
        return $image;
    }

    return "../Assets/Images/product/" . rawurlencode(basename($image));
}

function orderStatusClass($status)
{
    switch (strtolower(trim($status))) {

        case "pending":    return "bg-warning text-dark";
        case "confirmed":  return "bg-success";
        case "processing": return "bg-info text-dark";
        case "shipped":    return "bg-primary";
        case "completed":
        case "delivered":  return "bg-success";
        case "cancelled":
        case "canceled":   return "bg-danger";
        default:           return "bg-secondary";
    }
}

function formatOrderStatus($status)
{
    switch (strtolower(trim($status))) {

        case "pending":    return "Pending";
        case "confirmed":  return "Confirmed";
        case "processing": return "Processing";
        case "shipped":    return "Shipped";
        case "completed":  return "Completed";
        case "delivered":  return "Delivered";
        case "cancelled":
        case "canceled":   return "Cancelled";
        default:           return ucfirst($status);
    }
}

function orderStatusIcon($status)
{
    switch (strtolower(trim($status))) {

        case "pending":    return "bi bi-clock";
        case "confirmed":  return "bi bi-check-circle";
        case "processing": return "bi bi-gear";
        case "shipped":    return "bi bi-truck";
        case "completed":
        case "delivered":  return "bi bi-box-seam";
        case "cancelled":
        case "canceled":   return "bi bi-x-circle";
        default:           return "bi bi-info-circle";
    }
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
   FETCH ORDER
========================================================= */

$order = null;

$sql = "
    SELECT
        id, user_id, customer_name, email, phone, address,
        city, country, subtotal, shipping, total,
        payment_method, status, created_at
    FROM orders
    WHERE id = ? AND user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($connect, $sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars(mysqli_error($connect), ENT_QUOTES, "UTF-8"));
}

mysqli_stmt_bind_param($stmt, "ii", $orderId, $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($result) {
    $order = mysqli_fetch_assoc($result);
}

mysqli_stmt_close($stmt);


/* =========================================================
   ORDER NOT FOUND
========================================================= */

if (!$order) {
    $_SESSION["flash_error"] = "Order not found or access denied.";
    header("Location: orders.php");
    exit;
}


/* =========================================================
   FETCH ORDER ITEMS
========================================================= */

$orderItems = [];

$sql = "
    SELECT
        oi.id, oi.order_id, oi.product_id, oi.product_name,
        oi.price, oi.quantity, oi.subtotal,
        p.name  AS live_name,
        p.image AS live_image
    FROM order_items AS oi
    LEFT JOIN products AS p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
";

$stmt = mysqli_prepare($connect, $sql);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $orderItems[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   SANITIZE ORDER ITEM NAMES
========================================================= */

foreach ($orderItems as &$item) {

    $storedName = trim((string) ($item["product_name"] ?? ""));

    $isBad = (
        $storedName === "" ||
        $storedName === "0" ||
        (is_numeric($storedName) && (float) $storedName === 0.0)
    );

    if ($isBad) {
        if (!empty($item["live_name"])) {
            $item["product_name"] = $item["live_name"];
        } else {
            $item["product_name"] = "Product #" . (int) $item["product_id"];
        }
    }

    if (empty($item["live_image"]) && !empty($item["image"])) {
        $item["live_image"] = $item["image"];
    }
}

unset($item);


/* =========================================================
   BUILD DISPLAY ADDRESS (SINGLE LINE)
========================================================= */

$addressDisplay = "";

$rawAddress = trim((string) $order["address"]);

if ($rawAddress !== "") {

    $parts = array_filter(
        array_map("trim", explode(",", $rawAddress)),
        function ($p) {
            return $p !== "";
        }
    );

    if (!empty($parts)) {
        $addressDisplay = implode(", ", $parts);
    }
}


/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;

if (isset($_SESSION["cart"]) && is_array($_SESSION["cart"])) {

    foreach ($_SESSION["cart"] as $entry) {

        if (is_array($entry)) {
            if (isset($entry["qty"])) {
                $cartCount += (int) $entry["qty"];
            } elseif (isset($entry["quantity"])) {
                $cartCount += (int) $entry["quantity"];
            }
        } else {
            $cartCount += (int) $entry;
        }
    }
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
    <title>Order #<?= (int) $order["id"] ?> | Furnishop</title>
    <meta name="description" content="Order Details">
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
        .order-details-section {
            padding: 55px 0;
            min-height: 650px;
        }
        .order-card {
            background: #fff;
            border: 1px solid #e8eaee;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .order-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }
        .order-title {
            font-size: 25px;
            font-weight: 600;
            margin: 0;
        }
        .order-status {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .order-section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 18px;
            color: #212529;
        }
        .info-row {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 11px 0;
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            width: 160px;
            color: #6c757d;
            font-weight: 500;
            flex-shrink: 0;
        }
        .info-value {
            color: #212529;
            word-break: break-word;
            line-height: 1.6;
        }
        .order-table { margin-bottom: 0; }
        .order-table thead th {
            background: #f8f9fa;
            padding: 14px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }
        .order-table tbody td {
            padding: 15px 14px;
            vertical-align: middle;
            font-size: 14px;
        }
        .product-name { font-weight: 500; }
        .summary-box {
            max-width: 500px;
            margin-left: auto;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-total {
            font-size: 18px;
            font-weight: 600;
            padding-top: 16px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .order-product-image {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e5e5;
            background: #f8f9fa;
        }

        @media (max-width: 767px) {
            .order-details-section { padding: 35px 0; }
            .order-card { padding: 20px 15px; }
            .order-title { font-size: 21px; }
            .info-row { display: block; }
            .info-label {
                width: auto;
                display: block;
                margin-bottom: 3px;
            }
            .order-table { min-width: 650px; }
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
    <main>

        <!-- BREADCRUMB -->
        <div class="breadcrumb-main">
            <div class="container">
                <div class="breadcrumb-container">
                    <h2 class="page-title">Order Details</h2>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
                        <li class="breadcrumb-item active">Order Details</li>
                    </ul>
                </div>
            </div>
        </div>

        <section class="order-details-section">
            <div class="container">

                <!-- HEADER CARD -->
                <div class="order-card">
                    <div class="order-heading">
                        <div>
                            <h2 class="order-title">Order #<?= (int) $order["id"] ?></h2>
                            <small class="text-muted">
                                <?php
                                if (!empty($order["created_at"])) {
                                    echo e(date("d M Y, h:i A", strtotime($order["created_at"])));
                                }
                                ?>
                            </small>
                        </div>
                        <div>
                            <span class="badge order-status <?= orderStatusClass($order["status"]) ?>">
                                <i class="<?= orderStatusIcon($order["status"]) ?>"></i>
                                <?= e(formatOrderStatus($order["status"])) ?>
                            </span>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back to My Orders
                        </a>
                        <a href="products.php" class="btn btn-primary">
                            <i class="bi bi-cart me-2"></i>
                            Continue Shopping
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- CUSTOMER INFO -->
                    <div class="col-lg-6">
                        <div class="order-card">
                            <h3 class="order-section-title">
                                <i class="bi bi-person me-2"></i>
                                Customer Information
                            </h3>

                            <div class="info-row">
                                <div class="info-label">Name</div>
                                <div class="info-value"><?= e($order["customer_name"]) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?= e($order["email"]) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?= e($order["phone"]) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?= e($addressDisplay) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Country</div>
                                <div class="info-value"><?= e($order["country"]) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- ORDER INFO -->
                    <div class="col-lg-6">
                        <div class="order-card">
                            <h3 class="order-section-title">
                                <i class="bi bi-receipt me-2"></i>
                                Order Information
                            </h3>

                            <div class="info-row">
                                <div class="info-label">Order ID</div>
                                <div class="info-value">#<?= (int) $order["id"] ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Order Date</div>
                                <div class="info-value">
                                    <?php
                                    echo !empty($order["created_at"])
                                        ? e(date("d M Y, h:i A", strtotime($order["created_at"])))
                                        : "-";
                                    ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="badge order-status <?= orderStatusClass($order["status"]) ?>">
                                        <i class="<?= orderStatusIcon($order["status"]) ?>"></i>
                                        <?= e(formatOrderStatus($order["status"])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Payment Method</div>
                                <div class="info-value"><?= e($order["payment_method"]) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ORDER ITEMS -->
                <div class="order-card">
                    <h3 class="order-section-title">
                        <i class="bi bi-box me-2"></i>
                        Ordered Products
                    </h3>

                    <?php if (!empty($orderItems)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered order-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $index => $item): ?>
                                        <?php
                                        $imgSrc   = productImage($item["live_image"] ?? "");
                                        $fallback = "../Assets/Images/product/1.png";
                                        ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= e($imgSrc) ?>"
                                                         alt="<?= e($item["product_name"]) ?>"
                                                         class="order-product-image"
                                                         onerror="this.onerror=null;this.src='<?= e($fallback) ?>';">
                                                    <span class="product-name">
                                                        <?= e($item["product_name"]) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>Rs. <?= number_format((float) $item["price"], 2) ?></td>
                                            <td><?= (int) $item["quantity"] ?></td>
                                            <td>
                                                <strong>Rs. <?= number_format((float) $item["subtotal"], 2) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            No order items were found for this order.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SUMMARY -->
                <div class="order-card">
                    <h3 class="order-section-title">
                        <i class="bi bi-calculator me-2"></i>
                        Order Summary
                    </h3>

                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>Rs. <?= number_format((float) $order["subtotal"], 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>Rs. <?= number_format((float) $order["shipping"], 2) ?></span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Grand Total</span>
                            <span>Rs. <?= number_format((float) $order["total"], 2) ?></span>
                        </div>
                    </div>
                </div>

            </div>
        </section>
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
</body>
</html>