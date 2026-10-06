<?php

/* =========================================================
   MY ORDERS PAGE
   Furnishop - Customer Order History
========================================================= */

session_start();

require_once __DIR__ . "/../Config/database.php";


/* =========================================================
   LOGIN CHECK
========================================================= */

require_once __DIR__ . "/../Includes/functions.php";
require_customer_login($connect, "orders.php");


/* =========================================================
   USER ID
========================================================= */

$userId = (int) $_SESSION["user_id"];


/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
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
   FETCH USER ORDERS (WITH ITEM COUNT)
========================================================= */

$orders = [];

$sql = "
    SELECT
        o.id, o.customer_name, o.email, o.phone, o.address,
        o.city, o.country, o.subtotal, o.shipping, o.total,
        o.payment_method, o.status, o.created_at,
        (
            SELECT COUNT(*)
            FROM order_items oi
            WHERE oi.order_id = o.id
        ) AS item_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC, o.id DESC
";

$stmt = mysqli_prepare($connect, $sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars(mysqli_error($connect), ENT_QUOTES, "UTF-8"));
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

mysqli_stmt_close($stmt);


/* =========================================================
   ORDER STATISTICS
========================================================= */

$totalOrders  = count($orders);
$pendingCount = 0;
$totalSpent   = 0.0;

foreach ($orders as $order) {

    $status = strtolower(trim($order["status"] ?? ""));

    if ($status === "pending") {
        $pendingCount++;
    }

    if ($status !== "cancelled" && $status !== "canceled") {
        $totalSpent += (float) $order["total"];
    }
}


/* =========================================================
   FLASH SUCCESS
========================================================= */

$flashSuccess = $_SESSION["flash_success"] ?? null;
unset($_SESSION["flash_success"]);


/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;

if (isset($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
    foreach ($_SESSION["cart"] as $quantity) {
        $cartCount += (int) $quantity;
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
    <title>My Orders | Furnishop</title>
    <meta name="description" content="My Orders">
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
        .orders-section { padding: 60px 0; }

        .orders-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.06);
        }
        .orders-title {
            font-size: 25px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .orders-subtitle {
            color: #777;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* STAT CARDS */
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            height: 100%;
            border: 1px solid #ececec;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .stat-icon {
            font-size: 26px;
            margin-bottom: 10px;
            color: #212529;
        }
        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #212529;
            line-height: 1.2;
        }
        .stat-label {
            font-size: 13px;
            color: #777;
            margin-top: 4px;
        }

        /* TABLE */
        .orders-table { width: 100%; margin-bottom: 0; }

        .orders-table thead th {
            background: #f8f9fa;
            font-size: 14px;
            font-weight: 600;
            padding: 15px;
            border-bottom: 1px solid #e5e5e5;
            white-space: nowrap;
            color: #495057;
        }
        .orders-table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }
        .orders-table tbody tr { transition: background 0.15s ease; }
        .orders-table tbody tr:hover { background: #fafbfc; }
        .orders-table tbody tr:last-child td { border-bottom: none; }

        .order-number { font-weight: 600; color: #212529; }
        .order-date { color: #777; }
        .order-date small { display: block; color: #adb5bd; }
        .order-total { font-weight: 600; color: #212529; }

        .payment-method {
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            font-weight: 500;
            color: #495057;
            background: #f1f3f5;
            padding: 4px 10px;
            border-radius: 4px;
            display: inline-block;
        }

        .item-count { font-size: 13px; color: #6c757d; }
        .item-count i { margin-right: 4px; }

        .status-badge {
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .view-order-btn {
            border-radius: 6px;
            padding: 7px 14px;
            font-size: 13px;
        }

        /* EMPTY */
        .empty-orders { text-align: center; padding: 60px 20px; }
        .empty-orders-icon {
            font-size: 55px;
            color: #adb5bd;
            margin-bottom: 20px;
        }
        .empty-orders h4 { font-size: 22px; font-weight: 600; margin-bottom: 10px; }
        .empty-orders p { color: #777; margin-bottom: 25px; }

        @media (max-width: 768px) {
            .orders-section { padding: 40px 0; }
            .orders-card { padding: 20px 15px; }
            .orders-title { font-size: 21px; }
            .orders-table { min-width: 900px; }
            .stat-value { font-size: 18px; }
            .stat-icon { font-size: 22px; }
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
                    <h2 class="page-title">My Orders</h2>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">My Orders</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- ORDERS -->
        <section class="orders-section">
            <div class="container">
                <div class="orders-card">

                    <h2 class="orders-title">My Orders</h2>
                    <p class="orders-subtitle">
                        View your order history and track your orders.
                    </p>

                    <?php if ($flashSuccess): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-1"></i>
                            <?= e($flashSuccess) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($orders)): ?>

                        <!-- STATS -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                                    <div class="stat-value"><?= (int) $totalOrders ?></div>
                                    <div class="stat-label">Total Orders</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                                    <div class="stat-value"><?= (int) $pendingCount ?></div>
                                    <div class="stat-label">Pending Orders</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                                    <div class="stat-value">Rs. <?= number_format($totalSpent, 2) ?></div>
                                    <div class="stat-label">Total Spent</div>
                                </div>
                            </div>
                        </div>

                        <!-- ORDERS TABLE -->
                        <div class="table-responsive">
                            <table class="table orders-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>

                                            <td>
                                                <span class="order-number">
                                                    #<?= (int) $order["id"] ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="order-date">
                                                    <?php
                                                    if (!empty($order["created_at"])) {
                                                        echo date("d M Y", strtotime($order["created_at"]));
                                                    } else {
                                                        echo "-";
                                                    }
                                                    ?>
                                                    <small>
                                                        <?php
                                                        if (!empty($order["created_at"])) {
                                                            echo date("h:i A", strtotime($order["created_at"]));
                                                        }
                                                        ?>
                                                    </small>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="item-count">
                                                    <i class="bi bi-box"></i>
                                                    <?= (int) $order["item_count"] ?>
                                                    item<?= ((int) $order["item_count"] === 1) ? "" : "s" ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="order-total">
                                                    Rs. <?= number_format((float) $order["total"], 2) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="payment-method">
                                                    <?= e($order["payment_method"] ?? "N/A") ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="badge status-badge <?= orderStatusClass($order["status"] ?? "") ?>">
                                                    <i class="<?= orderStatusIcon($order["status"] ?? "") ?>"></i>
                                                    <?= e(formatOrderStatus($order["status"] ?? "")) ?>
                                                </span>
                                            </td>

                                            <td class="text-center">
                                                <a href="order-details.php?order_id=<?= (int) $order["id"] ?>"
                                                   class="btn btn-outline-secondary view-order-btn">
                                                    <i class="bi bi-eye me-1"></i>
                                                    View
                                                </a>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>

                        <!-- NO ORDERS -->
                        <div class="empty-orders">
                            <div class="empty-orders-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <h4>No Orders Found</h4>
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-cart me-2"></i>
                                Start Shopping
                            </a>
                        </div>

                    <?php endif; ?>

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