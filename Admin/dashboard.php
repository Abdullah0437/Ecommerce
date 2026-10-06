<?php

/* =========================================================
   ADMIN - DASHBOARD
   Furnishop Admin Panel
========================================================= */

require_once __DIR__ . "/../Includes/auth.php";


/* =========================================================
   DATABASE (if not already from auth.php)
========================================================= */

if (!isset($connect)) {

    $host     = "localhost";
    $username = "root";
    $password = "";
    $database = "ecommerce";

    $connect = mysqli_connect($host, $username, $password, $database);

    if (!$connect) {
        die("Database Connection Failed: " . mysqli_connect_error());
    }
}


/* =========================================================
   HELPERS
========================================================= */

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

function scalar($connect, $sql, $default = 0)
{
    $res = mysqli_query($connect, $sql);
    if (!$res) return $default;

    $row = mysqli_fetch_assoc($res);
    return $row ? array_values($row)[0] : $default;
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

function orderStatusIcon($status)
{
    switch (strtolower(trim($status))) {
        case "pending":    return "fa-solid fa-clock";
        case "confirmed":  return "fa-solid fa-circle-check";
        case "processing": return "fa-solid fa-gears";
        case "shipped":    return "fa-solid fa-truck-fast";
        case "completed":
        case "delivered":  return "fa-solid fa-box-open";
        case "cancelled":
        case "canceled":   return "fa-solid fa-circle-xmark";
        default:           return "fa-solid fa-circle-info";
    }
}


/* =========================================================
   CORE STATS
========================================================= */

$totalProducts   = (int) scalar($connect, "SELECT COUNT(*) FROM products");
$totalCategories = (int) scalar($connect, "SELECT COUNT(*) FROM categories");
$totalUsers      = (int) scalar($connect, "SELECT COUNT(*) FROM users");
$totalOrders     = (int) scalar($connect, "SELECT COUNT(*) FROM orders");

$pendingOrders   = (int) scalar(
    $connect,
    "SELECT COUNT(*) FROM orders WHERE status = 'Pending'"
);

$completedOrders = (int) scalar(
    $connect,
    "SELECT COUNT(*) FROM orders WHERE status = 'Delivered'"
);

$totalSales      = (float) scalar(
    $connect,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'Cancelled'",
    0
);


/* =========================================================
   RECENT ORDERS
========================================================= */

$recentOrders = [];

$res = mysqli_query(
    $connect,
    "SELECT id, customer_name, total, status, created_at
     FROM orders
     ORDER BY id DESC
     LIMIT 5"
);

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $recentOrders[] = $row;
    }
}


/* =========================================================
   PAGE META + HEADER
========================================================= */

$pageTitle      = "Dashboard";
$pageHeading    = "Dashboard";
$pageSubheading = "Welcome back! Here's what's happening with your store.";

require_once __DIR__ . "/Includes/header.php";
?>


<!-- =========================================================
     TOP ROW: WELCOME + CTA
========================================================= -->

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

    <div>
        <h2 class="mb-1 fw-bold" style="font-size:22px;">
            <i class="fa-solid fa-gauge-high text-primary me-2"></i>
            Store Overview
        </h2>
        <p class="text-muted mb-0" style="font-size:14px;">
            Quick snapshot of your store's performance.
        </p>
    </div>

    <div class="d-flex gap-2">
        <a href="product-add.php" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Product
        </a>
        <a href="orders.php" class="btn btn-outline-primary">
            <i class="fa-solid fa-receipt me-1"></i> View Orders
        </a>
    </div>

</div>


<!-- =========================================================
     PRIMARY STATS
========================================================= -->

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-sm-6">
        <a href="products.php" class="text-decoration-none">
            <div class="stat-box">
                <div class="stat-icon icon-primary">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <div class="stat-value"><?php echo $totalProducts; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-sm-6">
        <a href="categories.php" class="text-decoration-none">
            <div class="stat-box">
                <div class="stat-icon icon-info">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="stat-value"><?php echo $totalCategories; ?></div>
                <div class="stat-label">Total Categories</div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-sm-6">
        <a href="users.php" class="text-decoration-none">
            <div class="stat-box">
                <div class="stat-icon icon-success">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-sm-6">
        <a href="orders.php" class="text-decoration-none">
            <div class="stat-box">
                <div class="stat-icon icon-warning">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div class="stat-value"><?php echo $totalOrders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
        </a>
    </div>

</div>


<!-- =========================================================
     SECONDARY STATS
========================================================= -->

<div class="row g-3 mb-4">

    <div class="col-xl-4 col-sm-6">
        <a href="orders.php?status=Pending" class="text-decoration-none">
            <div class="stat-box">
                <div class="stat-icon icon-warning">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $pendingOrders; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </a>
    </div>

    <div class="col-xl-4 col-sm-6">
        <a href="orders.php?status=Delivered" class="text-decoration-none">
            <div class="stat-box">
                <div class="stat-icon icon-success">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat-value"><?php echo $completedOrders; ?></div>
                <div class="stat-label">Completed Orders</div>
            </div>
        </a>
    </div>

    <div class="col-xl-4 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-primary">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div class="stat-value" style="font-size:20px;">
                Rs. <?php echo number_format($totalSales, 2); ?>
            </div>
            <div class="stat-label">Total Sales</div>
        </div>
    </div>

</div>


<!-- =========================================================
     RECENT ORDERS PANEL
========================================================= -->

<div class="panel">

    <div class="panel-header">

        <h3>
            <i class="fa-solid fa-clock-rotate-left"></i>
            Recent Orders
        </h3>

        <a href="orders.php" class="btn btn-sm btn-outline-primary">
            View All <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>

    </div>

    <?php if (!empty($recentOrders)): ?>

        <div class="table-responsive">

            <table class="admin-table align-middle">

                <thead>
                    <tr>
                        <th style="width:80px;">Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-center" style="width:100px;">Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($recentOrders as $order): ?>

                        <tr>

                            <td>
                                <a
                                    href="order-details.php?order_id=<?php echo (int) $order["id"]; ?>"
                                    style="font-weight:600;color:#4f46e5;text-decoration:none;">
                                    #<?php echo (int) $order["id"]; ?>
                                </a>
                            </td>

                            <td>
                                <?php echo e($order["customer_name"]); ?>
                            </td>

                            <td>
                                <strong>
                                    Rs. <?php echo number_format((float) $order["total"], 2); ?>
                                </strong>
                            </td>

                            <td>
                                <span class="badge <?php echo orderStatusClass($order["status"]); ?> status-badge"
                                      style="padding:6px 12px;border-radius:20px;font-size:12px;font-weight:500;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="<?php echo orderStatusIcon($order["status"]); ?>"></i>
                                    <?php echo e($order["status"]); ?>
                                </span>
                            </td>

                            <td style="color:#6b7280;font-size:13px;">
                                <?php
                                echo !empty($order["created_at"])
                                    ? e(date("d M Y", strtotime($order["created_at"])))
                                    : "-";
                                ?>
                            </td>

                            <td class="text-center">
                                <a
                                    href="order-details.php?order_id=<?php echo (int) $order["id"]; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="View Order">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h5>No orders yet</h5>
            <p class="text-muted mb-0">
                Orders placed by customers will appear here.
            </p>
        </div>

    <?php endif; ?>

</div>


<?php require_once __DIR__ . "/Includes/footer.php"; ?>