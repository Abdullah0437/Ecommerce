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
// SEARCH VALUE
// ==================================================

$search = "";

if (isset($_POST['search'])) {

    $search = trim($_POST['search']);
}


if (isset($_GET['search'])) {

    $search = trim($_GET['search']);
}


// ==================================================
// ACTIVATE / DEACTIVATE PRODUCT
// ==================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST['toggle_status'])
) {

    $product_id = (int) $_POST['toggle_status'];


    // ----------------------------------------------
    // GET CURRENT PRODUCT STATUS
    // ----------------------------------------------

    $statusQuery = "
        SELECT status
        FROM products
        WHERE id = ?
    ";

    $statusStmt = mysqli_prepare(
        $connect,
        $statusQuery
    );

    mysqli_stmt_bind_param(
        $statusStmt,
        "i",
        $product_id
    );

    mysqli_stmt_execute($statusStmt);

    $statusResult = mysqli_stmt_get_result(
        $statusStmt
    );

    $product = mysqli_fetch_assoc(
        $statusResult
    );


    // ----------------------------------------------
    // CHANGE STATUS
    // ----------------------------------------------

    if ($product) {

        if ($product['status'] === 'Active') {

            $newStatus = 'Inactive';
        } else {

            $newStatus = 'Active';
        }


        // ------------------------------------------
        // UPDATE PRODUCT STATUS
        // ------------------------------------------

        $updateQuery = "
            UPDATE products
            SET status = ?
            WHERE id = ?
        ";

        $updateStmt = mysqli_prepare(
            $connect,
            $updateQuery
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            "si",
            $newStatus,
            $product_id
        );

        mysqli_stmt_execute(
            $updateStmt
        );


        // ------------------------------------------
        // REDIRECT AFTER POST
        // ------------------------------------------

        if ($search !== "") {

            header(
                "Location: products.php?message=status_updated&search="
                    . urlencode($search)
            );
        } else {

            header(
                "Location: products.php?message=status_updated"
            );
        }

        exit;
    }
}


// ==================================================
// FETCH PRODUCTS
// ==================================================

if ($search !== "") {


    // ----------------------------------------------
    // SEARCH PRODUCTS
    // ----------------------------------------------

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

        WHERE
            products.name LIKE ?
            OR categories.name LIKE ?

        ORDER BY products.id DESC
    ";


    $stmt = mysqli_prepare(
        $connect,
        $query
    );


    $searchValue = "%" . $search . "%";


    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $searchValue,
        $searchValue
    );


    mysqli_stmt_execute(
        $stmt
    );


    $result = mysqli_stmt_get_result(
        $stmt
    );
} else {


    // ----------------------------------------------
    // FETCH ALL PRODUCTS
    // ----------------------------------------------

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


    $result = mysqli_query(
        $connect,
        $query
    );
}

?>


<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <title>Product Management</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />


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

        .product-card {
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .search-box {
            width: 600px;
            max-width: 100%;
        }
    </style>
</head>


<body>
    <div class="container-fluid py-4">


        <!--  PAGE HEADER -->

        <div class="page-header">

            <div
                class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h2 class="page-title">

                        <i class="fa-solid fa-box-open me-2"></i>

                        Product Management

                    </h2>


                    <p class="text-muted mb-0 mt-1">

                        Manage products, prices, stock and status.

                    </p>

                </div>


                <a
                    href="product-add.php"
                    class="btn btn-primary">

                    <i class="fa-solid fa-plus me-1"></i>

                    Add New Product

                </a>

            </div>

        </div>



        <!-- ==========================================
         SUCCESS MESSAGE
    =========================================== -->

        <?php if (
            isset($_GET['message']) &&
            $_GET['message'] === 'status_updated'
        ): ?>

            <div
                class="alert alert-success alert-dismissible fade show"
                role="alert">

                <i class="fa-solid fa-circle-check me-2"></i>

                Product status updated successfully.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>

            </div>

        <?php endif; ?>



        <!--  PRODUCT CARD -->

        <div class="card product-card">

            <div class="card-body">


                <!-- SEARCH + TITLE -->

                <div
                    class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                    <h5 class="mb-0">

                        All Products

                    </h5>


                    <form
                        method="POST"
                        class="d-flex search-box">

                        <input
                            type="text"
                            name="search"
                            class="form-control me-2"
                            placeholder="Search product or category..."
                            value="<?= htmlspecialchars($search) ?>" />


                        <button
                            type="submit"
                            class="btn btn-dark">

                            <i class="fa-solid fa-search"></i>

                        </button>


                        <?php if ($search !== ""): ?>

                            <a
                                href="products.php"
                                class="btn btn-outline-secondary ms-2">

                                <i class="fa-solid fa-xmark"></i>

                            </a>

                        <?php endif; ?>

                    </form>

                </div>



                <!-- ==========================================
                 PRODUCT TABLE
            =========================================== -->

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Image</th>

                                <th>Product Name</th>

                                <th>Category</th>

                                <th>Price</th>

                                <th>Stock</th>

                                <th>Status</th>

                                <th>Actions</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (mysqli_num_rows($result) > 0): ?>


                                <?php while ($row = mysqli_fetch_assoc($result)): ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <?= $row['id'] ?>

                                        </td>



                                        <!-- IMAGE -->

                                        <td>

                                            <?php $imagePath = "../Images/" . $row['image'];  ?>


                                            <?php if (!empty($row['image']) && file_exists($imagePath)): ?>

                                                <img
                                                    src="<?= htmlspecialchars($imagePath) ?>"
                                                    alt="<?= htmlspecialchars($row['name']) ?>"
                                                    class="product-image" />

                                            <?php else: ?>

                                                <div class="no-image">
                                                    No Image
                                                </div>

                                            <?php endif; ?>

                                        </td>



                                        <!-- PRODUCT NAME -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $row['name']
                                                ) ?>

                                            </strong>

                                        </td>



                                        <!-- CATEGORY -->

                                        <td>

                                            <span class="badge bg-info p-3">

                                                <?= htmlspecialchars(
                                                    $row['category_name']
                                                ) ?>

                                            </span>

                                        </td>



                                        <!-- PRICE -->

                                        <td>

                                            <strong>
                                                Rs.
                                                <?= number_format($row['price'], 2) ?>
                                            </strong>

                                        </td>



                                        <!-- STOCK -->

                                        <td>

                                            <?php if (
                                                $row['stock_quantity'] > 0
                                            ): ?>

                                                <span
                                                    class="text-success fw-semibold">

                                                    <?= $row['stock_quantity'] ?>

                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="text-danger fw-semibold">

                                                    Out of Stock

                                                </span>

                                            <?php endif; ?>

                                        </td>



                                        <!-- STATUS -->

                                        <td>

                                            <?php if (
                                                $row['status'] === 'Active'
                                            ): ?>

                                                <span
                                                    class="badge bg-success status-badge">

                                                    Active

                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="badge bg-danger status-badge">

                                                    Inactive

                                                </span>

                                            <?php endif; ?>

                                        </td>



                                        <td class="action-buttons">

                                            <!-- EDIT -->

                                            <a
                                                href="product-edit.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Edit Product">

                                                <i class="fa-solid fa-pen"></i>

                                            </a>


                                            <!-- ACTIVATE / DEACTIVATE -->

                                            <?php if ($row['status'] === 'Active'): ?>

                                                <!-- DEACTIVATE -->

                                                <form
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to deactivate this product?');">

                                                    <!-- Product ID -->
                                                    <input
                                                        type="hidden"
                                                        name="toggle_status"
                                                        value="<?= $row['id'] ?>">

                                                    <!-- Keep current search -->
                                                    <input
                                                        type="hidden"
                                                        name="search"
                                                        value="<?= htmlspecialchars($search) ?>">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Deactivate Product">

                                                        <i class="fa-solid fa-ban"></i>

                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <!-- ACTIVATE -->

                                                <form
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to activate this product?');">

                                                    <!-- Product ID -->
                                                    <input
                                                        type="hidden"
                                                        name="toggle_status"
                                                        value="<?= $row['id'] ?>">

                                                    <!-- Keep current search -->
                                                    <input
                                                        type="hidden"
                                                        name="search"
                                                        value="<?= htmlspecialchars($search) ?>">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-success"
                                                        title="Activate Product">

                                                        <i class="fa-solid fa-check"></i>

                                                    </button>

                                                </form>

                                            <?php endif; ?>

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

                                            <?php if ($search !== ""): ?>

                                                No products match your search.

                                            <?php else: ?>

                                                No products have been added yet.

                                            <?php endif; ?>

                                        </p>


                                        <?php if ($search === ""): ?>

                                            <a
                                                href="product-add.php"
                                                class="btn btn-primary">

                                                <i
                                                    class="fa-solid fa-plus me-1"></i>

                                                Add First Product

                                            </a>

                                        <?php endif; ?>

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
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

</body>

</html>

<?php

mysqli_close($connect);

?>