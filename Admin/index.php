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




$productQuery = "SELECT COUNT(*) AS total FROM products";

$productResult = mysqli_query(
    $connect,
    $productQuery
);

$productData = mysqli_fetch_assoc(
    $productResult
);

$totalProducts = $productData['total'];



$categoryQuery = "SELECT COUNT(*) AS total FROM categories";

$categoryResult = mysqli_query(
    $connect,
    $categoryQuery
);

$categoryData = mysqli_fetch_assoc(
    $categoryResult
);

$totalCategories = $categoryData['total'];




$userQuery = "SELECT COUNT(*) AS total FROM users";

$userResult = mysqli_query(
    $connect,
    $userQuery
);

$userData = mysqli_fetch_assoc(
    $userResult
);

$totalUsers = $userData['total'];



$orderQuery = "SELECT COUNT(*) AS total FROM orders";

$orderResult = mysqli_query(
    $connect,
    $orderQuery
);

$orderData = mysqli_fetch_assoc(
    $orderResult
);

$totalOrders = $orderData['total'];



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




include "includes/header.php";

?>



<div class="page-header">

    <h2 class="page-title">

        <i class="fa-solid fa-gauge me-2"></i>

        Dashboard

    </h2>

    <p class="text-muted mb-0 mt-1">

        Overview of your ecommerce website.

    </p>

</div>




<div class="row g-4">


   

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h6 class="text-muted">

                            Total Products

                        </h6>

                        <h2 class="fw-bold">

                            <?= $totalProducts ?>

                        </h2>

                    </div>

                    <div>

                        <i class="fa-solid fa-box fa-2x text-primary"></i>

                    </div>

                </div>


                <a
                    href="products.php"
                    class="btn btn-primary btn-sm mt-2">

                    View Products

                </a>

            </div>

        </div>

    </div>




    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h6 class="text-muted">

                            Total Categories

                        </h6>

                        <h2 class="fw-bold">

                            <?= $totalCategories ?>

                        </h2>

                    </div>

                    <div>

                        <i class="fa-solid fa-layer-group fa-2x text-info"></i>

                    </div>

                </div>


                <a
                    href="categories.php"
                    class="btn btn-info btn-sm mt-2">

                    View Categories

                </a>

            </div>

        </div>

    </div>


    

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h6 class="text-muted">

                            Total Users

                        </h6>

                        <h2 class="fw-bold">

                            <?= $totalUsers ?>

                        </h2>

                    </div>

                    <div>

                        <i class="fa-solid fa-users fa-2x text-success"></i>

                    </div>

                </div>


                <a
                    href="users.php"
                    class="btn btn-success btn-sm mt-2">

                    View Users

                </a>

            </div>

        </div>

    </div>


  

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h6 class="text-muted">

                            Total Orders

                        </h6>

                        <h2 class="fw-bold">

                            <?= $totalOrders ?>

                        </h2>

                    </div>

                    <div>

                        <i class="fa-solid fa-cart-shopping fa-2x text-warning"></i>

                    </div>

                </div>


                <a
                    href="orders.php"
                    class="btn btn-warning btn-sm mt-2">

                    View Orders

                </a>

            </div>

        </div>

    </div>


  

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h6 class="text-muted">

                            Pending Orders

                        </h6>

                        <h2 class="fw-bold">

                            <?= $pendingOrders ?>

                        </h2>

                    </div>

                    <div>

                        <i class="fa-solid fa-clock fa-2x text-warning"></i>

                    </div>

                </div>


                <a
                    href="orders.php?status=Pending"
                    class="btn btn-warning btn-sm mt-2">

                    View Pending

                </a>

            </div>

        </div>

    </div>


   

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h6 class="text-muted">

                            Completed Orders

                        </h6>

                        <h2 class="fw-bold">

                            <?= $completedOrders ?>

                        </h2>

                    </div>

                    <div>

                        <i class="fa-solid fa-circle-check fa-2x text-success"></i>

                    </div>

                </div>


                <a
                    href="orders.php?status=Delivered"
                    class="btn btn-success btn-sm mt-2">

                    View Completed

                </a>

            </div>

        </div>

    </div>


  

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-card shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h6 class="text-muted">

                            Total Sales

                        </h6>

                        <h2 class="fw-bold">

                            Rs. <?= number_format($totalSales, 2) ?>

                        </h2>

                    </div>

                    <div>

                        <i class="fa-solid fa-money-bill-wave fa-2x text-success"></i>

                    </div>

                </div>


                <a
                    href="orders.php"
                    class="btn btn-success btn-sm mt-2">

                    View Sales

                </a>

            </div>

        </div>

    </div>


</div>


<?php

include "includes/footer.php";

mysqli_close($connect);

?>