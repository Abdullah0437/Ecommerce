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



if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}




$stockErr = "";
$message = "";




if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_stock'])) {

    $product_id = $_POST['product_id'] ?? "";
    $stock = $_POST['stock_quantity'] ?? "";

    

    if ($product_id == "" || !is_numeric($product_id)) {

        $stockErr = "Invalid product.";
    }

   

    elseif ($stock == "") {

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
        .page-header {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            font-weight: 600;
            margin: 0;
        }

        .stock-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .no-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background-color: #eeeeee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 12px;
        }

        .table th {
            white-space: nowrap;
            background-color: #f8f9fa;
        }

        .table td {
            vertical-align: middle;
        }

        .stock-input {
            width: 110px;
        }

        .stock-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>

</head>


<body>


    <div class="container-fluid py-4">


       

        <div class="page-header">

            <div
                class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h2 class="page-title">

                        <i class="fa-solid fa-boxes-stacked me-2"></i>

                        Stock Management

                    </h2>

                    <p class="text-muted mb-0 mt-1">

                        Manage product stock quantities.

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



      

        <?php if ($message != ""): ?>

            <div
                class="alert alert-success alert-dismissible fade show"
                role="alert">

                <i class="fa-solid fa-circle-check me-2"></i>

                <?= htmlspecialchars($message) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>

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
                    data-bs-dismiss="alert"></button>

            </div>

        <?php endif; ?>



       

        <div class="card stock-card">

            <div class="card-body">


                <div class="d-flex justify-content-between align-items-center mb-4">

                    <h5 class="mb-0">

                        <i class="fa-solid fa-warehouse me-2"></i>

                        Product Stock

                    </h5>

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


                            <?php if (mysqli_num_rows($result) > 0): ?>


                                <?php while ($row = mysqli_fetch_assoc($result)): ?>


                                    <tr>


                                      

                                        <td>

                                            <?= $row['id'] ?>

                                        </td>



                                     

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



                                       

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars($row['name']) ?>

                                            </strong>

                                        </td>



                                      

                                        <td>

                                            <span class="badge bg-info p-2">

                                                <?= htmlspecialchars($row['category_name']) ?>

                                            </span>

                                        </td>



                                     

                                        <td>

                                            <strong>

                                                Rs.
                                                <?= number_format($row['price'], 2) ?>

                                            </strong>

                                        </td>



                                       

                                        <td>

                                            <?php if ($row['stock_quantity'] > 0): ?>

                                                <span class="text-success fw-semibold">

                                                    <?= $row['stock_quantity'] ?>

                                                    Available

                                                </span>

                                            <?php else: ?>

                                                <span class="text-danger fw-semibold">

                                                    Out of Stock

                                                </span>

                                            <?php endif; ?>

                                        </td>



                                      

                                        <td>

                                            <?php if ($row['status'] === 'Active'): ?>

                                                <span class="badge bg-success">

                                                    Active

                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-danger">

                                                    Inactive

                                                </span>

                                            <?php endif; ?>

                                        </td>



                                       

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
                                                    required>


                                                <button
                                                    type="submit"
                                                    name="update_stock"
                                                    value="1"
                                                    class="btn btn-sm btn-primary"
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
                                            class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>


                                        <h5>

                                            No Products Found

                                        </h5>


                                        <p class="text-muted">

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


    </div>



   

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>


<?php

mysqli_close($connect);

?>