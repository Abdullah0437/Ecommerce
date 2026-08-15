<?php

require_once "../includes/auth.php";
$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}





// Total Products
$productQuery = "SELECT COUNT(*) AS total FROM products";
$productResult = mysqli_query($connect, $productQuery);
$productData = mysqli_fetch_assoc($productResult);

$totalProducts = $productData['total'];


// Total Categories
$categoryQuery = "SELECT COUNT(*) AS total FROM categories";
$categoryResult = mysqli_query($connect, $categoryQuery);
$categoryData = mysqli_fetch_assoc($categoryResult);

$totalCategories = $categoryData['total'];


// Total Users
$userQuery = "SELECT COUNT(*) AS total FROM users";
$userResult = mysqli_query($connect, $userQuery);
$userData = mysqli_fetch_assoc($userResult);

$totalUsers = $userData['total'];


// Total Orders
$orderQuery = "SELECT COUNT(*) AS total FROM orders";
$orderResult = mysqli_query($connect, $orderQuery);
$orderData = mysqli_fetch_assoc($orderResult);

$totalOrders = $orderData['total'];


// Pending Orders
$pendingQuery = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'Pending'
";

$pendingResult = mysqli_query($connect, $pendingQuery);
$pendingData = mysqli_fetch_assoc($pendingResult);

$pendingOrders = $pendingData['total'];


// Completed Orders
$completedQuery = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'Delivered'
";

$completedResult = mysqli_query($connect, $completedQuery);
$completedData = mysqli_fetch_assoc($completedResult);

$completedOrders = $completedData['total'];


// Total Sales
$salesQuery = "
    SELECT COALESCE(SUM(total), 0) AS total_sales
    FROM orders
    WHERE status != 'Cancelled'
";

$salesResult = mysqli_query($connect, $salesQuery);
$salesData = mysqli_fetch_assoc($salesResult);

$totalSales = $salesData['total_sales'];

?>
<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <title>Admin Dashboard</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />
</head>

<body>

    <!-- Navbar -->

    <nav class="navbar navbar-dark bg-dark">

        <div class="container-fluid">

            <span class="navbar-brand">
                Ecommerce Admin
            </span>

            <div class="d-flex align-items-center">

                <span class="text-white me-3">

                    Welcome,
                    <?= htmlspecialchars($_SESSION['admin_name']) ?>

                </span>

                <a
                    href="logout.php"
                    class="btn btn-danger">

                    Logout

                </a>

            </div>

        </div>

    </nav>


    <!-- Dashboard -->

    <div class="container mt-5">

        <h2 class="mb-4">
            Admin Dashboard
        </h2>


        <!-- Statistics Cards -->

        <div class="row g-4">


            <!-- Products -->

            <div class="col-md-4 col-lg-3">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">
                            Total Products
                        </h5>

                        <h2>
                            <?= $totalProducts ?>
                        </h2>

                        <a
                            href="products.php"
                            class="btn btn-primary btn-sm">

                            View Products

                        </a>

                    </div>

                </div>

            </div>


            <!-- Categories -->

            <div class="col-md-4 col-lg-3">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">
                            Total Categories
                        </h5>

                        <h2>
                            <?= $totalCategories ?>
                        </h2>

                        <a
                            href="categories.php"
                            class="btn btn-primary btn-sm">

                            View Categories

                        </a>

                    </div>

                </div>

            </div>


            <!-- Users -->

            <div class="col-md-4 col-lg-3">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">
                            Total Users
                        </h5>

                        <h2>
                            <?= $totalUsers ?>
                        </h2>

                        <a
                            href="users.php"
                            class="btn btn-primary btn-sm">

                            View Users

                        </a>

                    </div>

                </div>

            </div>


            <!-- Orders -->

            <div class="col-md-4 col-lg-3">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">
                            Total Orders
                        </h5>

                        <h2>
                            <?= $totalOrders ?>
                        </h2>

                        <a
                            href="orders.php"
                            class="btn btn-primary btn-sm">

                            View Orders

                        </a>

                    </div>

                </div>

            </div>


            <!-- Pending Orders -->

            <div class="col-md-4 col-lg-3">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">
                            Pending Orders
                        </h5>

                        <h2>
                            <?= $pendingOrders ?>
                        </h2>

                        <a
                            href="orders.php?status=Pending"
                            class="btn btn-warning btn-sm">

                            View Pending

                        </a>

                    </div>

                </div>

            </div>


            <!-- Completed Orders -->

            <div class="col-md-4 col-lg-3">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">
                            Completed Orders
                        </h5>

                        <h2>
                            <?= $completedOrders ?>
                        </h2>

                        <a
                            href="orders.php?status=Delivered"
                            class="btn btn-success btn-sm">

                            View Completed

                        </a>

                    </div>

                </div>

            </div>


            <!-- Total Sales -->

            <div class="col-md-4 col-lg-3">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">
                            Total Sales
                        </h5>

                        <h2>
                            Rs. <?= number_format($totalSales, 2) ?>
                        </h2>

                        <a
                            href="orders.php"
                            class="btn btn-success btn-sm">

                            View Sales

                        </a>

                    </div>

                </div>

            </div>


        </div>

    </div>
</body>

</html>