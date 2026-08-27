<?php

session_start();

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


// ==================================================
// ADMIN AUTHENTICATION
// ==================================================

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}


// ==================================================
// VARIABLES
// ==================================================

$stockErr = "";
$message = "";


// ==================================================
// UPDATE STOCK
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_stock'])) {

    $product_id = $_POST['product_id'] ?? "";
    $stock = $_POST['stock_quantity'] ?? "";


    if ($product_id == "" || !is_numeric($product_id)) {

        $stockErr = "Invalid product.";

    } elseif ($stock == "") {

        $stockErr = "Stock quantity is required.";

    } elseif (!is_numeric($stock) || $stock < 0 || floor($stock) != $stock) {

        $stockErr = "Stock must be a whole number and cannot be negative.";

    } else {

        $product_id = (int)$product_id;
        $stock = (int)$stock;


        $updateQuery = "
            UPDATE products
            SET stock_quantity = ?
            WHERE id = ?
        ";

        $updateStmt = mysqli_prepare($connect, $updateQuery);

        mysqli_stmt_bind_param(
            $updateStmt,
            "ii",
            $stock,
            $product_id
        );


        if (mysqli_stmt_execute($updateStmt)) {

            $message = "Stock updated successfully.";

        } else {

            $stockErr = "Stock could not be updated.";

        }

        mysqli_stmt_close($updateStmt);
    }
}


// ==================================================
// FETCH PRODUCTS
// ==================================================

$query = "
    SELECT
        products.id,
        products.name,
        products.price,
        products.stock_quantity,
        products.image,
        products.status,
        categories.name AS category_name

    FROM products

    INNER JOIN categories
        ON products.category_id = categories.id

    ORDER BY products.id DESC
";

$result = mysqli_query($connect, $query);


// ==================================================
// STOCK SUMMARY
// ==================================================

$totalProducts = 0;
$inStock = 0;
$lowStock = 0;
$outOfStock = 0;

if ($result && mysqli_num_rows($result) > 0) {

    mysqli_data_seek($result, 0);

    while ($stockRow = mysqli_fetch_assoc($result)) {

        $totalProducts++;

        if ($stockRow['stock_quantity'] == 0) {

            $outOfStock++;

        } elseif ($stockRow['stock_quantity'] <= 5) {

            $lowStock++;

        } else {

            $inStock++;
        }
    }

    mysqli_data_seek($result, 0);
}


include "includes/header.php";

?>


<!doctype html>

<html lang="en" data-bs-theme="light">

<head>

    <title>Stock Management</title>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <style>

        body {
            background-color: #f5f7fa;
        }


        .page-header {
            background-color: #ffffff;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }


        .page-title {
            font-weight: 600;
            margin: 0;
        }


        .summary-card {
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }


        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }


        .summary-number {
            font-size: 25px;
            font-weight: 700;
            margin-bottom: 2px;
        }


        .summary-title {
            color: #6c757d;
            font-size: 14px;
        }


        .stock-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }


        .stock-card-header {
            background-color: #ffffff;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }


        .stock-card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }


        .product-image {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }


        .no-image {
            width: 55px;
            height: 55px;
            border-radius: 8px;
            background-color: #f1f3f5;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #868e96;
            font-size: 11px;
        }


        .table {
            margin-bottom: 0;
        }


        .table th {
            white-space: nowrap;
            background-color: #f8f9fa;
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            padding: 14px 12px;
        }


        .table td {
            vertical-align: middle;
            padding: 14px 12px;
        }


        .table tbody tr:hover {
            background-color: #f8f9fa;
        }


        .category-badge {
            font-weight: 500;
        }


        .stock-badge {
            min-width: 110px;
            display: inline-block;
            text-align: center;
            padding: 7px 10px;
            border-radius: 20px;
            font-size: 12px;
        }


        .stock-form {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 170px;
        }


        .stock-input {
            width: 100px;
        }


        .update-btn {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .alert {
            border-radius: 10px;
        }


        .empty-icon {
            font-size: 45px;
        }


        @media (max-width: 768px) {

            .page-header {
                padding: 18px;
            }

            .page-title {
                font-size: 21px;
            }

            .summary-card {
                padding: 16px;
            }

            .table th,
            .table td {
                font-size: 13px;
            }

        }

    </style>

</head>


<body>


<div class="container-fluid py-4">


    <!-- ==========================================
         PAGE HEADER
    =========================================== -->

    <div class="page-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <h2 class="page-title">

                    <i class="fa-solid fa-boxes-stacked me-2"></i>

                    Stock Management

                </h2>

                <p class="text-muted mb-0 mt-2">

                    Monitor and update product stock quantities.

                </p>

            </div>


            <a
                href="products.php"
                class="btn btn-outline-secondary">

                <i class="fa-solid fa-arrow-left me-1"></i>

                Back to Products

            </a>

        </div>

    </div>


    <!-- ==========================================
         ALERTS
    =========================================== -->

    <?php if ($message != ""): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert">

            <i class="fa-solid fa-circle-check me-2"></i>

            <?= htmlspecialchars($message) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if ($stockErr != ""): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert">

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            <?= htmlspecialchars($stockErr) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- ==========================================
         STOCK SUMMARY
    =========================================== -->

    <div class="row g-3 mb-4">


        <!-- TOTAL PRODUCTS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="summary-card">

                <div class="d-flex align-items-center">

                    <div class="summary-icon bg-primary-subtle text-primary">

                        <i class="fa-solid fa-box"></i>

                    </div>

                    <div class="ms-3">

                        <div class="summary-number">

                            <?= $totalProducts ?>

                        </div>

                        <div class="summary-title">

                            Total Products

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- IN STOCK -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="summary-card">

                <div class="d-flex align-items-center">

                    <div class="summary-icon bg-success-subtle text-success">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div class="ms-3">

                        <div class="summary-number">

                            <?= $inStock ?>

                        </div>

                        <div class="summary-title">

                            In Stock

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- LOW STOCK -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="summary-card">

                <div class="d-flex align-items-center">

                    <div class="summary-icon bg-warning-subtle text-warning">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>

                    <div class="ms-3">

                        <div class="summary-number">

                            <?= $lowStock ?>

                        </div>

                        <div class="summary-title">

                            Low Stock

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- OUT OF STOCK -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="summary-card">

                <div class="d-flex align-items-center">

                    <div class="summary-icon bg-danger-subtle text-danger">

                        <i class="fa-solid fa-circle-xmark"></i>

                    </div>

                    <div class="ms-3">

                        <div class="summary-number">

                            <?= $outOfStock ?>

                        </div>

                        <div class="summary-title">

                            Out of Stock

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ==========================================
         PRODUCT STOCK TABLE
    =========================================== -->

    <div class="card stock-card">


        <div class="stock-card-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div>

                    <h5 class="stock-card-title">

                        <i class="fa-solid fa-warehouse me-2"></i>

                        Product Stock

                    </h5>

                    <small class="text-muted">

                        Update stock quantities directly from this table.

                    </small>

                </div>

                <span class="badge text-bg-light border">

                    <?= $totalProducts ?> Products

                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle">


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Image</th>

                        <th>Product Name</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>Current Stock</th>

                        <th>Status</th>

                        <th>Update Stock</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($result && mysqli_num_rows($result) > 0): ?>


                    <?php while ($row = mysqli_fetch_assoc($result)): ?>


                        <tr>


                            <!-- ID -->

                            <td class="fw-semibold">

                                <?= $row['id'] ?>

                            </td>


                            <!-- IMAGE -->

                            <td>

                                <?php

                                $imagePath = "../Images/" . $row['image'];

                                ?>


                                <?php if (
                                    !empty($row['image']) &&
                                    file_exists($imagePath)
                                ): ?>

                                    <img
                                        src="<?= htmlspecialchars($imagePath) ?>"
                                        alt="<?= htmlspecialchars($row['name']) ?>"
                                        class="product-image">

                                <?php else: ?>

                                    <div class="no-image">

                                        No Image

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- PRODUCT NAME -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars($row['name']) ?>

                                </strong>

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                <span class="badge bg-info-subtle text-info-emphasis category-badge">

                                    <?= htmlspecialchars($row['category_name']) ?>

                                </span>

                            </td>


                            <!-- PRICE -->

                            <td>

                                <span class="fw-semibold">

                                    Rs.
                                    <?= number_format($row['price'], 2) ?>

                                </span>

                            </td>


                            <!-- CURRENT STOCK -->

                            <td>


                                <?php if ($row['stock_quantity'] == 0): ?>

                                    <span class="badge text-bg-danger stock-badge">

                                        <i class="fa-solid fa-circle-xmark me-1"></i>

                                        Out of Stock

                                    </span>


                                <?php elseif ($row['stock_quantity'] <= 5): ?>

                                    <span class="badge text-bg-warning stock-badge">

                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>

                                        <?= $row['stock_quantity'] ?>

                                        Low Stock

                                    </span>


                                <?php else: ?>

                                    <span class="badge text-bg-success stock-badge">

                                        <i class="fa-solid fa-circle-check me-1"></i>

                                        <?= $row['stock_quantity'] ?>

                                        In Stock

                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- PRODUCT STATUS -->

                            <td>

                                <?php if ($row['status'] === 'Active'): ?>

                                    <span class="badge text-bg-success">

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="badge text-bg-secondary">

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- UPDATE STOCK -->

                            <td>

                                <form
                                    method="POST"
                                    class="stock-form">

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= $row['id'] ?>">


                                    <input
                                        type="number"
                                        name="stock_quantity"
                                        class="form-control form-control-sm stock-input"
                                        value="<?= $row['stock_quantity'] ?>"
                                        min="0"
                                        step="1"
                                        required>


                                    <button
                                        type="submit"
                                        name="update_stock"
                                        value="1"
                                        class="btn btn-primary btn-sm update-btn"
                                        title="Update Stock">

                                        <i class="fa-solid fa-floppy-disk"></i>

                                    </button>

                                </form>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5">

                            <i
                                class="fa-solid fa-box-open text-muted empty-icon mb-3">
                            </i>

                            <h5>

                                No Products Found

                            </h5>

                            <p class="text-muted mb-0">

                                No products are available for stock management.

                            </p>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>


<?php

include "includes/footer.php";

mysqli_close($connect);

?>