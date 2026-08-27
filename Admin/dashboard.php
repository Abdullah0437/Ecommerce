<?php

require_once "../includes/auth.php";

$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}


/* =========================
   TOTAL PRODUCTS
========================= */

$productQuery = "SELECT COUNT(*) AS total FROM products";

$productResult = mysqli_query(
    $connect,
    $productQuery
);

$productData = mysqli_fetch_assoc(
    $productResult
);

$totalProducts = $productData['total'];


/* =========================
   TOTAL CATEGORIES
========================= */

$categoryQuery = "SELECT COUNT(*) AS total FROM categories";

$categoryResult = mysqli_query(
    $connect,
    $categoryQuery
);

$categoryData = mysqli_fetch_assoc(
    $categoryResult
);

$totalCategories = $categoryData['total'];


/* =========================
   TOTAL USERS
========================= */

$userQuery = "SELECT COUNT(*) AS total FROM users";

$userResult = mysqli_query(
    $connect,
    $userQuery
);

$userData = mysqli_fetch_assoc(
    $userResult
);

$totalUsers = $userData['total'];


/* =========================
   TOTAL ORDERS
========================= */

$orderQuery = "SELECT COUNT(*) AS total FROM orders";

$orderResult = mysqli_query(
    $connect,
    $orderQuery
);

$orderData = mysqli_fetch_assoc(
    $orderResult
);

$totalOrders = $orderData['total'];


/* =========================
   PENDING ORDERS
========================= */

$pendingQuery = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'Pending'
";

$pendingResult = mysqli_query(
    $connect,
    $pendingQuery
);

$pendingData = mysqli_fetch_assoc(
    $pendingResult
);

$pendingOrders = $pendingData['total'];


/* =========================
   COMPLETED ORDERS
========================= */

$completedQuery = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'Delivered'
";

$completedResult = mysqli_query(
    $connect,
    $completedQuery
);

$completedData = mysqli_fetch_assoc(
    $completedResult
);

$completedOrders = $completedData['total'];


/* =========================
   TOTAL SALES
========================= */

$salesQuery = "
    SELECT COALESCE(SUM(total), 0) AS total_sales
    FROM orders
    WHERE status != 'Cancelled'
";

$salesResult = mysqli_query(
    $connect,
    $salesQuery
);

$salesData = mysqli_fetch_assoc(
    $salesResult
);

$totalSales = $salesData['total_sales'];


/* =========================
   RECENT ORDERS
========================= */

$recentOrdersQuery = "
    SELECT id, customer_name, total, status, created_at
    FROM orders
    ORDER BY id DESC
    LIMIT 5
";

$recentOrdersResult = mysqli_query(
    $connect,
    $recentOrdersQuery
);


include "includes/header.php";

?>


<!-- PAGE HEADER -->

<div class="page-header">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="page-title">

                <i class="fa-solid fa-gauge-high text-primary me-2"></i>

                Dashboard

            </h2>

            <p class="text-muted mb-0 mt-2">

                Welcome back! Here's what's happening with your store.

            </p>

        </div>

        <div>

            <a href="product-add.php" class="btn btn-primary">

                <i class="fa-solid fa-plus me-1"></i>

                Add Product

            </a>

        </div>

    </div>

</div>


<!-- DASHBOARD CARDS -->

<div class="row g-4">


    <!-- PRODUCTS -->

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card h-100">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted mb-2">
                            Total Products
                        </p>

                        <h2 class="mb-0">
                            <?= $totalProducts ?>
                        </h2>

                    </div>

                    <div
                        class="dashboard-icon"
                        style="background:#dbeafe; color:#2563eb;">

                        <i class="fa-solid fa-box-open"></i>

                    </div>

                </div>

                <a
                    href="products.php"
                    class="btn btn-primary btn-sm mt-4">

                    View Products

                    <i class="fa-solid fa-arrow-right ms-1"></i>

                </a>

            </div>

        </div>

    </div>


    <!-- CATEGORIES -->

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card h-100">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted mb-2">
                            Total Categories
                        </p>

                        <h2 class="mb-0">
                            <?= $totalCategories ?>
                        </h2>

                    </div>

                    <div
                        class="dashboard-icon"
                        style="background:#e0f2fe; color:#0284c7;">

                        <i class="fa-solid fa-layer-group"></i>

                    </div>

                </div>

                <a
                    href="categories.php"
                    class="btn btn-info btn-sm mt-4 text-white">

                    View Categories

                    <i class="fa-solid fa-arrow-right ms-1"></i>

                </a>

            </div>

        </div>

    </div>


    <!-- USERS -->

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card h-100">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted mb-2">
                            Total Users
                        </p>

                        <h2 class="mb-0">
                            <?= $totalUsers ?>
                        </h2>

                    </div>

                    <div
                        class="dashboard-icon"
                        style="background:#dcfce7; color:#16a34a;">

                        <i class="fa-solid fa-users"></i>

                    </div>

                </div>

                <a
                    href="users.php"
                    class="btn btn-success btn-sm mt-4">

                    View Users

                    <i class="fa-solid fa-arrow-right ms-1"></i>

                </a>

            </div>

        </div>

    </div>


    <!-- ORDERS -->

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card h-100">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted mb-2">
                            Total Orders
                        </p>

                        <h2 class="mb-0">
                            <?= $totalOrders ?>
                        </h2>

                    </div>

                    <div
                        class="dashboard-icon"
                        style="background:#fef3c7; color:#d97706;">

                        <i class="fa-solid fa-cart-shopping"></i>

                    </div>

                </div>

                <a
                    href="orders.php"
                    class="btn btn-warning btn-sm mt-4">

                    View Orders

                    <i class="fa-solid fa-arrow-right ms-1"></i>

                </a>

            </div>

        </div>

    </div>


    <!-- PENDING ORDERS -->

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card h-100">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted mb-2">
                            Pending Orders
                        </p>

                        <h2 class="mb-0">
                            <?= $pendingOrders ?>
                        </h2>

                    </div>

                    <div
                        class="dashboard-icon"
                        style="background:#fef3c7; color:#d97706;">

                        <i class="fa-solid fa-clock"></i>

                    </div>

                </div>

                <a
                    href="orders.php?status=Pending"
                    class="btn btn-warning btn-sm mt-4">

                    View Pending

                    <i class="fa-solid fa-arrow-right ms-1"></i>

                </a>

            </div>

        </div>

    </div>


    <!-- COMPLETED ORDERS -->

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card h-100">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted mb-2">
                            Completed Orders
                        </p>

                        <h2 class="mb-0">
                            <?= $completedOrders ?>
                        </h2>

                    </div>

                    <div
                        class="dashboard-icon"
                        style="background:#dcfce7; color:#16a34a;">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                </div>

                <a
                    href="orders.php?status=Delivered"
                    class="btn btn-success btn-sm mt-4">

                    View Completed

                    <i class="fa-solid fa-arrow-right ms-1"></i>

                </a>

            </div>

        </div>

    </div>


    <!-- SALES -->

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card h-100">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted mb-2">
                            Total Sales
                        </p>

                        <h2 class="mb-0 fs-4">

                            Rs.
                            <?= number_format($totalSales, 2) ?>

                        </h2>

                    </div>

                    <div
                        class="dashboard-icon"
                        style="background:#dcfce7; color:#16a34a;">

                        <i class="fa-solid fa-money-bill-wave"></i>

                    </div>

                </div>

                <a
                    href="orders.php"
                    class="btn btn-success btn-sm mt-4">

                    View Sales

                    <i class="fa-solid fa-arrow-right ms-1"></i>

                </a>

            </div>

        </div>

    </div>


</div>


<!-- RECENT ORDERS -->

<div class="card mt-4">

    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h5 class="mb-1 fw-bold">

                    <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>

                    Recent Orders

                </h5>

                <small class="text-muted">
                    Latest orders placed by customers
                </small>

            </div>

            <a
                href="orders.php"
                class="btn btn-outline-primary btn-sm">

                View All

                <i class="fa-solid fa-arrow-right ms-1"></i>

            </a>

        </div>


        <div class="table-responsive">

            <table class="table align-middle">

                <thead>

                    <tr>

                        <th>Order ID</th>

                        <th>Customer</th>

                        <th>Total</th>

                        <th>Status</th>

                        <th>Date</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (mysqli_num_rows($recentOrdersResult) > 0) { ?>

                        <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)) { ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?= $order['id'] ?>
                                    </strong>

                                </td>

                                <td>

                                    <?= htmlspecialchars($order['customer_name']) ?>

                                </td>

                                <td>

                                    <strong>
                                        Rs. <?= number_format($order['total'], 2) ?>
                                    </strong>

                                </td>

                                <td>

                                    <?php

                                    $status = $order['status'];

                                    if ($status == 'Pending') {

                                        $badgeClass = 'bg-warning text-dark';

                                    } elseif ($status == 'Delivered') {

                                        $badgeClass = 'bg-success';

                                    } elseif ($status == 'Cancelled') {

                                        $badgeClass = 'bg-danger';

                                    } else {

                                        $badgeClass = 'bg-primary';

                                    }

                                    ?>

                                    <span class="badge <?= $badgeClass ?> status-badge">

                                        <?= htmlspecialchars($status) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($order['created_at'])
                                    ) ?>

                                </td>

                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>

                            <td
                                colspan="5"
                                class="text-center text-muted py-4">

                                <i class="fa-solid fa-box-open fa-2x mb-2"></i>

                                <br>

                                No orders found.

                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php

include "includes/footer.php";

mysqli_close($connect);

?>