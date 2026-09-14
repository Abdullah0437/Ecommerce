
<?php

session_start();

require_once __DIR__ . "/../Config/database.php";


/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   USER ID
========================================================= */

$user_id = (int) $_SESSION["user_id"];


/* =========================================================
   FETCH USER ORDERS
========================================================= */

$orders = [];

$sql = "
    SELECT
        id,
        customer_name,
        email,
        phone,
        address,
        city,
        country,
        subtotal,
        shipping,
        total,
        payment_method,
        status,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
";


$stmt = mysqli_prepare($connect, $sql);


if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);


    while ($row = mysqli_fetch_assoc($result)) {

        $orders[] = $row;

    }


    mysqli_stmt_close($stmt);
}


/* =========================================================
   ORDER STATUS CLASS
========================================================= */

function orderStatusClass($status)
{
    $status = strtolower(trim($status));


    switch ($status) {

        case "pending":
            return "bg-warning text-dark";

        case "processing":
            return "bg-info text-dark";

        case "shipped":
            return "bg-primary";

        case "completed":
            return "bg-success";

        case "delivered":
            return "bg-success";

        case "cancelled":
            return "bg-danger";

        case "canceled":
            return "bg-danger";

        default:
            return "bg-secondary";
    }
}


/* =========================================================
   ORDER STATUS TEXT
========================================================= */

function formatOrderStatus($status)
{
    $status = strtolower(trim($status));


    switch ($status) {

        case "pending":
            return "Pending";

        case "processing":
            return "Processing";

        case "shipped":
            return "Shipped";

        case "completed":
            return "Completed";

        case "delivered":
            return "Delivered";

        case "cancelled":
            return "Cancelled";

        case "canceled":
            return "Cancelled";

        default:
            return ucfirst($status);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>My Orders - Furnishop</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         MAIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        body {
            font-family: 'Poppins', sans-serif;
        }


        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .breadcrumb-item + .breadcrumb-item::before {
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
           ORDERS SECTION
        ===================================================== */

        .orders-section {
            padding: 60px 0;
        }


        .orders-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 30px;

            box-shadow:
                0 5px 25px rgba(0, 0, 0, 0.08);
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


        /* =====================================================
           TABLE
        ===================================================== */

        .orders-table {
            width: 100%;
            margin-bottom: 0;
        }


        .orders-table thead th {
            background: #f8f9fa;

            font-size: 14px;
            font-weight: 600;

            padding: 15px;

            border-bottom: 1px solid #e5e5e5;

            white-space: nowrap;
        }


        .orders-table tbody td {
            padding: 18px 15px;

            vertical-align: middle;

            font-size: 14px;

            border-bottom: 1px solid #eeeeee;
        }


        .orders-table tbody tr:last-child td {
            border-bottom: none;
        }


        /* =====================================================
           ORDER NUMBER
        ===================================================== */

        .order-number {
            font-weight: 600;
        }


        /* =====================================================
           DATE
        ===================================================== */

        .order-date {
            color: #777;
        }


        /* =====================================================
           TOTAL
        ===================================================== */

        .order-total {
            font-weight: 600;
        }


        /* =====================================================
           PAYMENT METHOD
        ===================================================== */

        .payment-method {
            text-transform: capitalize;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status-badge {
            padding: 7px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 500;
        }


        /* =====================================================
           VIEW BUTTON
        ===================================================== */

        .view-order-btn {
            border-radius: 5px;

            padding: 7px 14px;

            font-size: 13px;
        }


        /* =====================================================
           EMPTY ORDERS
        ===================================================== */

        .empty-orders {
            text-align: center;

            padding: 60px 20px;
        }


        .empty-orders-icon {
            font-size: 55px;

            color: #adb5bd;

            margin-bottom: 20px;
        }


        .empty-orders h4 {
            font-size: 22px;

            font-weight: 600;

            margin-bottom: 10px;
        }


        .empty-orders p {
            color: #777;

            margin-bottom: 25px;
        }


        .shop-btn {
            padding: 10px 25px;

            border-radius: 5px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 768px) {

            .orders-section {
                padding: 40px 0;
            }


            .orders-card {
                padding: 20px 15px;
            }


            .orders-title {
                font-size: 21px;
            }


            .table-responsive {
                overflow-x: auto;
            }


            .orders-table {
                min-width: 850px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">

    <div class="container">

        <nav class="navbar navbar-expand-lg navbar-light">


            <!-- LOGO -->

            <a
                class="navbar-brand"
                href="index.php"
            >

                <strong>
                    Furnishop
                </strong>

            </a>


            <!-- MOBILE BUTTON -->

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >

                <span class="navbar-toggler-icon"></span>

            </button>


            <!-- NAVIGATION -->

            <div
                class="collapse navbar-collapse"
                id="navbarNav"
            >

                <ul class="navbar-nav ms-auto">


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="index.php"
                        >
                            Home
                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="products.php"
                        >
                            Products
                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="cart.php"
                        >
                            Cart
                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link active"
                            href="orders.php"
                        >
                            My Orders
                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="account.php"
                        >
                            Account
                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="logout.php"
                        >
                            Logout
                        </a>

                    </li>


                </ul>

            </div>

        </nav>

    </div>

</header>



<!-- =========================================================
     BREADCRUMB
========================================================= -->

<div class="breadcrumb-main">

    <div class="container">

        <div class="breadcrumb-container">


            <h2 class="page-title">
                My Orders
            </h2>


            <ul class="breadcrumb">


                <li class="breadcrumb-item">

                    <a href="index.php">
                        Home
                    </a>

                </li>


                <li class="breadcrumb-item active">

                    My Orders

                </li>


            </ul>


        </div>

    </div>

</div>



<!-- =========================================================
     ORDERS SECTION
========================================================= -->

<section class="orders-section">

    <div class="container">


        <div class="orders-card">


            <!-- =================================================
                 TITLE
            ================================================== -->

            <h2 class="orders-title">
                My Orders
            </h2>


            <p class="orders-subtitle">
                View your order history and track your orders.
            </p>



            <!-- =================================================
                 SUCCESS MESSAGE
            ================================================== -->

            <?php if (isset($_GET["success"])): ?>

                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >

                    <i class="fa-solid fa-circle-check me-2"></i>

                    Your order has been placed successfully.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>



            <?php if (count($orders) > 0): ?>


                <!-- =================================================
                     ORDERS TABLE
                ================================================== -->

                <div class="table-responsive">


                    <table class="table orders-table">


                        <thead>

                            <tr>


                                <th>
                                    Order #
                                </th>


                                <th>
                                    Date
                                </th>


                                <th>
                                    Total
                                </th>


                                <th>
                                    Payment
                                </th>


                                <th>
                                    Status
                                </th>


                                <th class="text-center">
                                    Action
                                </th>


                            </tr>

                        </thead>



                        <tbody>


                            <?php foreach ($orders as $order): ?>


                                <tr>


                                    <!-- ORDER ID -->

                                    <td>

                                        <span class="order-number">

                                            #<?php

                                            echo (int) $order["id"];

                                            ?>

                                        </span>

                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <span class="order-date">

                                            <?php

                                            if (
                                                !empty(
                                                    $order["created_at"]
                                                )
                                            ) {

                                                echo date(
                                                    "d M Y, h:i A",
                                                    strtotime(
                                                        $order["created_at"]
                                                    )
                                                );

                                            } else {

                                                echo "-";

                                            }

                                            ?>

                                        </span>

                                    </td>



                                    <!-- TOTAL -->

                                    <td>

                                        <span class="order-total">

                                            Rs.
                                            <?php

                                            echo number_format(
                                                (float) $order["total"],
                                                2
                                            );

                                            ?>

                                        </span>

                                    </td>



                                    <!-- PAYMENT METHOD -->

                                    <td>

                                        <span class="payment-method">

                                            <?php

                                            echo htmlspecialchars(
                                                $order[
                                                    "payment_method"
                                                ] ?? "N/A",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );

                                            ?>

                                        </span>

                                    </td>



                                    <!-- STATUS -->

                                    <td>


                                        <span
                                            class="
                                                badge
                                                status-badge
                                                <?php

                                                echo orderStatusClass(
                                                    $order["status"] ?? ""
                                                );

                                                ?>
                                            "
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                formatOrderStatus(
                                                    $order["status"] ?? ""
                                                ),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );

                                            ?>

                                        </span>


                                    </td>



                                    <!-- ACTION -->

                                    <td class="text-center">


                                        <a
                                            href="order-details.php?order_id=<?php echo (int) $order["id"]; ?>"
                                            class="btn btn-outline-dark view-order-btn"
                                        >

                                            <i
                                                class="fa-solid fa-eye me-1"
                                            ></i>

                                            View Details

                                        </a>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <!-- =================================================
                     NO ORDERS
                ================================================== -->

                <div class="empty-orders">


                    <div class="empty-orders-icon">

                        <i class="fa-solid fa-box-open"></i>

                    </div>


                    <h4>
                        No Orders Found
                    </h4>


                    <p>
                        You haven't placed any orders yet.
                    </p>


                    <a
                        href="products.php"
                        class="btn btn-dark shop-btn"
                    >

                        <i
                            class="fa-solid fa-cart-shopping me-2"
                        ></i>

                        Start Shopping

                    </a>


                </div>


            <?php endif; ?>


        </div>

    </div>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="footer">

    <div class="container">


        <div class="row">


            <!-- ABOUT -->

            <div class="col-lg-4 col-md-6 mb-4">

                <h5>
                    Furnishop
                </h5>


                <p>
                    Your trusted online furniture store.
                    Shop quality furniture at affordable prices.
                </p>

            </div>



            <!-- QUICK LINKS -->

            <div class="col-lg-2 col-md-6 mb-4">

                <h5>
                    Quick Links
                </h5>


                <ul class="list-unstyled">


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
                        <a href="orders.php">
                            My Orders
                        </a>
                    </li>


                </ul>

            </div>



            <!-- ACCOUNT -->

            <div class="col-lg-3 col-md-6 mb-4">

                <h5>
                    Account
                </h5>


                <ul class="list-unstyled">


                    <li>
                        <a href="account.php">
                            My Account
                        </a>
                    </li>


                    <li>
                        <a href="orders.php">
                            My Orders
                        </a>
                    </li>


                    <li>
                        <a href="logout.php">
                            Logout
                        </a>
                    </li>


                </ul>

            </div>



            <!-- CONTACT -->

            <div class="col-lg-3 col-md-6 mb-4">

                <h5>
                    Contact
                </h5>


                <p>

                    <i
                        class="fa-solid fa-envelope me-2"
                    ></i>

                    support@example.com

                </p>


                <p>

                    <i
                        class="fa-solid fa-phone me-2"
                    ></i>

                    +92 300 0000000

                </p>

            </div>


        </div>



        <hr>



        <div class="text-center">


            <p class="mb-0">

                © <?php echo date("Y"); ?> Furnishop.
                All Rights Reserved.

            </p>


        </div>


    </div>

</footer>



<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>

