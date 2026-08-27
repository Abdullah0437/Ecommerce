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




$search = "";

if (isset($_POST['search'])) {

    $search = trim($_POST['search']);
}

if (isset($_GET['search'])) {

    $search = trim($_GET['search']);
}




if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['toggle_status'])
) {

    $product_id = (int) $_POST['toggle_status'];




    $statusQuery = "
        SELECT status
        FROM products
        WHERE id = ?
    ";

    $statusStmt = mysqli_prepare($connect, $statusQuery);

    mysqli_stmt_bind_param(
        $statusStmt,
        "i",
        $product_id
    );

    mysqli_stmt_execute($statusStmt);

    $statusResult = mysqli_stmt_get_result($statusStmt);

    $product = mysqli_fetch_assoc($statusResult);


   
    if ($product) {

        if ($product['status'] === 'Active') {

            $newStatus = 'Inactive';

        } else {

            $newStatus = 'Active';
        }


       

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

        mysqli_stmt_execute($updateStmt);


       

        if (!empty($search)) {

            header(
                "Location: products.php?message=status_updated&search="
                . urlencode($search)
            );

        } else {

            header(
                "Location: products.php?message=status_updated"
            );
        }

        exit();
    }
}




if ($search !== "") {

   

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


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);

} else {

  

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

<?php include "includes/header.php"; ?>

<!-- PAGE HEADER -->

<div class="page-header">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="page-title">

                <i class="fa-solid fa-box-open text-primary me-2"></i>

                Product Management

            </h2>

            <p class="text-muted mb-0 mt-2">

                Manage products, prices, stock and availability.

            </p>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a
                href="stock.php"
                class="btn btn-outline-warning">

                <i class="fa-solid fa-boxes-stacked me-1"></i>

                Stock

            </a>

            <a
                href="product-add.php"
                class="btn btn-primary">

                <i class="fa-solid fa-plus me-1"></i>

                Add Product

            </a>

        </div>

    </div>

</div>


<!-- SUCCESS MESSAGE -->

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
            data-bs-dismiss="alert">
        </button>

    </div>

<?php endif; ?>


<!-- PRODUCTS CARD -->

<div class="card product-card">

    <div class="card-body p-4">


        <!-- CARD HEADER -->

        <div
            class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <div>

                <h5 class="fw-bold mb-1">

                    All Products

                </h5>

                <small class="text-muted">

                    View and manage your store products

                </small>

            </div>


            <!-- SEARCH -->

            <form
                method="POST"
                class="d-flex search-box">

                <div class="input-group">

                    <span class="input-group-text bg-white">

                        <i class="fa-solid fa-magnifying-glass text-muted"></i>

                    </span>

                    <input
                        type="text"
                        name="search"
                        class="form-control border-start-0"
                        placeholder="Search product or category..."
                        value="<?= htmlspecialchars($search) ?>">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        Search

                    </button>

                </div>


                <?php if ($search !== ""): ?>

                    <a
                        href="products.php"
                        class="btn btn-outline-secondary ms-2">

                        <i class="fa-solid fa-xmark"></i>

                    </a>

                <?php endif; ?>

            </form>

        </div>


        <!-- SEARCH RESULT -->

        <?php if ($search !== ""): ?>

            <div class="alert alert-info py-2">

                <i class="fa-solid fa-filter me-2"></i>

                Showing results for:

                <strong>
                    <?= htmlspecialchars($search) ?>
                </strong>

            </div>

        <?php endif; ?>


        <!-- TABLE -->

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Product</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>Stock</th>

                        <th>Status</th>

                        <th class="text-center">Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (mysqli_num_rows($result) > 0): ?>


                    <?php while ($row = mysqli_fetch_assoc($result)): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <span class="text-muted fw-semibold">

                                    #<?= $row['id'] ?>

                                </span>

                            </td>


                            <!-- PRODUCT -->

                            <td>

                                <div class="d-flex align-items-center gap-3">


                                    <?php

                                    $imagePath =
                                        "../Images/" . $row['image'];

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

                                            <i class="fa-solid fa-image"></i>

                                        </div>

                                    <?php endif; ?>


                                    <div>

                                        <div class="fw-semibold">

                                            <?= htmlspecialchars(
                                                $row['name']
                                            ) ?>

                                        </div>

                                        <small class="text-muted">

                                            Product #<?= $row['id'] ?>

                                        </small>

                                    </div>


                                </div>

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                <span class="badge bg-info text-dark status-badge">

                                    <?= htmlspecialchars(
                                        $row['category_name']
                                    ) ?>

                                </span>

                            </td>


                            <!-- PRICE -->

                            <td>

                                <strong>

                                    Rs.
                                    <?= number_format(
                                        $row['price'],
                                        2
                                    ) ?>

                                </strong>

                            </td>


                            <!-- STOCK -->

                            <td>

                                <?php if ($row['stock_quantity'] > 0): ?>

                                    <?php if ($row['stock_quantity'] <= 5): ?>

                                        <span class="badge bg-warning text-dark status-badge">

                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>

                                            <?= $row['stock_quantity'] ?>

                                            Low Stock

                                        </span>

                                    <?php else: ?>

                                        <span class="text-success fw-semibold">

                                            <i class="fa-solid fa-check me-1"></i>

                                            <?= $row['stock_quantity'] ?>

                                        </span>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <span class="badge bg-danger status-badge">

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

                                        <i class="fa-solid fa-circle-check me-1"></i>

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge bg-danger status-badge">

                                        <i class="fa-solid fa-circle-xmark me-1"></i>

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACTIONS -->

                            <td class="text-center action-buttons">


                                <!-- EDIT -->

                                <a
                                    href="product-edit.php?id=<?= $row['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Edit Product">

                                    <i class="fa-solid fa-pen"></i>

                                </a>


                                <!-- STATUS -->

                                <?php if (
                                    $row['status'] === 'Active'
                                ): ?>

                                    <form
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to deactivate this product?');">

                                        <input
                                            type="hidden"
                                            name="toggle_status"
                                            value="<?= $row['id'] ?>">

                                        <input
                                            type="hidden"
                                            name="search"
                                            value="<?= htmlspecialchars($search) ?>">

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Deactivate Product">

                                            <i class="fa-solid fa-trash"></i>

                                        </button>

                                    </form>

                                <?php else: ?>

                                    <form
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to activate this product?');">

                                        <input
                                            type="hidden"
                                            name="toggle_status"
                                            value="<?= $row['id'] ?>">

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
                            colspan="7"
                            class="text-center py-5">

                            <div class="mb-3">

                                <i
                                    class="fa-solid fa-box-open fa-3x text-muted">
                                </i>

                            </div>

                            <h5 class="fw-bold">

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
                                        class="fa-solid fa-plus me-1">
                                    </i>

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


<?php include "includes/footer.php"; ?>


<?php

mysqli_close($connect);

?>