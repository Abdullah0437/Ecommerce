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



<div class="page-header mb-4">

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


        <div>

            <a
                href="product-add.php"
                class="btn btn-primary">

                <i class="fa-solid fa-plus me-1"></i>

                Add New Product

            </a>


            <a
                href="stock.php"
                class="btn btn-warning">

                <i class="fa-solid fa-boxes-stacked me-1"></i>

                Stock Management

            </a>

        </div>

    </div>

</div>




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



<div class="card product-card shadow-sm">

    <div class="card-body">


       

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
                    value="<?= htmlspecialchars($search) ?>">


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


                              

                                <td>

                                    <?= $row['id'] ?>

                                </td>


                                

                                <td>

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

                                            No Image

                                        </div>

                                    <?php endif; ?>

                                </td>


                           

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $row['name']
                                        ) ?>

                                    </strong>

                                </td>


                           

                                <td>

                                    <span class="badge bg-info p-2">

                                        <?= htmlspecialchars(
                                            $row['category_name']
                                        ) ?>

                                    </span>

                                </td>



                                <td>

                                    <strong>

                                        Rs.
                                        <?= number_format(
                                            $row['price'],
                                            2
                                        ) ?>

                                    </strong>

                                </td>


                              

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


                                   

                                    <a
                                        href="product-edit.php?id=<?= $row['id'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit Product">

                                        <i class="fa-solid fa-pen"></i>

                                    </a>


                                    

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

                                                <i class="fa-solid fa-ban"></i>

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
                                colspan="8"
                                class="text-center py-5">

                                <i
                                    class="fa-solid fa-box-open fa-3x text-muted mb-3">
                                </i>


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