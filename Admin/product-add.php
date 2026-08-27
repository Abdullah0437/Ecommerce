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


/* =========================
   ADMIN AUTHENTICATION
========================= */

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}


/* =========================
   ERROR VARIABLES
========================= */

$productNameErr = "";
$categoryErr = "";
$priceErr = "";
$stockErr = "";
$imageErr = "";
$statusErr = "";

$success = "";


/* =========================
   FORM VALUES
   These variables preserve
   entered data after errors.
========================= */

$productName = "";
$categoryId = "";
$description = "";
$price = "";
$stockQuantity = "";
$status = "";


/* =========================
   FETCH ACTIVE CATEGORIES
========================= */

$categoryQuery = "
    SELECT id, name
    FROM categories
    WHERE status = 'Active'
    ORDER BY name ASC
";

$categoryResult = mysqli_query(
    $connect,
    $categoryQuery
);

if (!$categoryResult) {
    die("Category Query Failed: " . mysqli_error($connect));
}


/* =========================
   FORM SUBMISSION
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* =========================
       GET FORM DATA
    ========================= */

    $productName = trim($_POST['product_name'] ?? "");
    $categoryId = $_POST['category_id'] ?? "";
    $description = trim($_POST['description'] ?? "");
    $price = trim($_POST['price'] ?? "");
    $stockQuantity = trim($_POST['stock_quantity'] ?? "");
    $status = $_POST['status'] ?? "";


    /* =========================
       PRODUCT NAME VALIDATION
    ========================= */

    if ($productName === "") {

        $productNameErr = "Product name is required.";

    } elseif (strlen($productName) > 100) {

        $productNameErr = "Product name cannot exceed 100 characters.";

    }


    /* =========================
       CATEGORY VALIDATION
    ========================= */

    if ($categoryId === "") {

        $categoryErr = "Please select a category.";

    } elseif (!ctype_digit((string)$categoryId)) {

        $categoryErr = "Invalid category.";

    } else {

        $categoryCheckQuery = "
            SELECT id
            FROM categories
            WHERE id = ?
            AND status = 'Active'
            LIMIT 1
        ";

        $categoryStmt = mysqli_prepare(
            $connect,
            $categoryCheckQuery
        );

        mysqli_stmt_bind_param(
            $categoryStmt,
            "i",
            $categoryId
        );

        mysqli_stmt_execute($categoryStmt);

        $categoryCheckResult = mysqli_stmt_get_result(
            $categoryStmt
        );

        if (mysqli_num_rows($categoryCheckResult) !== 1) {

            $categoryErr = "Selected category is invalid.";

        }

        mysqli_stmt_close($categoryStmt);
    }


    /* =========================
       PRICE VALIDATION
    ========================= */

    if ($price === "") {

        $priceErr = "Price is required.";

    } elseif (!is_numeric($price)) {

        $priceErr = "Price must be a valid number.";

    } elseif ($price <= 0) {

        $priceErr = "Price must be greater than 0.";

    }


    /* =========================
       STOCK VALIDATION
    ========================= */

    if ($stockQuantity === "") {

        $stockErr = "Stock quantity is required.";

    } elseif (!ctype_digit($stockQuantity)) {

        $stockErr = "Stock quantity must be a whole number.";

    } elseif ((int)$stockQuantity < 0) {

        $stockErr = "Stock cannot be negative.";

    }


    /* =========================
       STATUS VALIDATION
    ========================= */

    if ($status === "") {

        $statusErr = "Please select a status.";

    } elseif (
        $status !== "Active" &&
        $status !== "Inactive"
    ) {

        $statusErr = "Invalid status.";

    }


    /* =========================
       IMAGE VALIDATION
    ========================= */

    $targetDirectory = "../Images/";
    $imagePath = "";

    if (
        !isset($_FILES["product_image"]) ||
        $_FILES["product_image"]["error"] === UPLOAD_ERR_NO_FILE
    ) {

        $imageErr = "Please choose a product image.";

    } elseif (
        $_FILES["product_image"]["error"] !== UPLOAD_ERR_OK
    ) {

        $imageErr = "There was an error uploading the image.";

    } else {

        $maxSize = 500 * 1024;

        $imageName = $_FILES["product_image"]["name"];
        $imageTmpName = $_FILES["product_image"]["tmp_name"];
        $imageSize = $_FILES["product_image"]["size"];

        $imageFileType = strtolower(
            pathinfo(
                $imageName,
                PATHINFO_EXTENSION
            )
        );


        /* Check real image */

        $getImage = getimagesize(
            $imageTmpName
        );


        if ($getImage === false) {

            $imageErr = "File is not a valid image.";

        } elseif ($imageSize > $maxSize) {

            $imageErr = "Image size must not exceed 500 KB.";

        } elseif (
            $imageFileType !== "jpg" &&
            $imageFileType !== "jpeg" &&
            $imageFileType !== "png"
        ) {

            $imageErr =
                "Only JPG, JPEG and PNG images are allowed.";

        } else {

            /*
             * Create a unique filename.
             */

            $newFileName =
                uniqid("product_", true) .
                "." .
                $imageFileType;


            /*
             * Physical location where
             * the image will be uploaded.
             */

            $targetFile =
                $targetDirectory .
                $newFileName;


            /*
             * Path stored in database.
             * This path can be used by
             * customer pages.
             */

            $imagePath =
                "Images/" .
                $newFileName;
        }
    }


    /* =========================
       INSERT PRODUCT
    ========================= */

    if (
        $productNameErr === "" &&
        $categoryErr === "" &&
        $priceErr === "" &&
        $stockErr === "" &&
        $imageErr === "" &&
        $statusErr === ""
    ) {

        /*
         * Upload image first.
         */

        if (
            move_uploaded_file(
                $_FILES["product_image"]["tmp_name"],
                $targetFile
            )
        ) {

            $insertQuery = "
                INSERT INTO products
                (
                    category_id,
                    name,
                    description,
                    price,
                    stock_quantity,
                    image,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";


            $stmt = mysqli_prepare(
                $connect,
                $insertQuery
            );


            if ($stmt) {

                $categoryId = (int)$categoryId;
                $price = (float)$price;
                $stockQuantity = (int)$stockQuantity;


                mysqli_stmt_bind_param(
                    $stmt,
                    "issdiss",
                    $categoryId,
                    $productName,
                    $description,
                    $price,
                    $stockQuantity,
                    $imagePath,
                    $status
                );


                if (mysqli_stmt_execute($stmt)) {

                    $success =
                        "Product added successfully.";


                    /*
                     * Clear form after
                     * successful insertion.
                     */

                    $productName = "";
                    $categoryId = "";
                    $description = "";
                    $price = "";
                    $stockQuantity = "";
                    $status = "";


                } else {

                    /*
                     * If database insertion
                     * fails, delete the uploaded
                     * image so there is no
                     * unnecessary file.
                     */

                    if (file_exists($targetFile)) {
                        unlink($targetFile);
                    }

                    $success =
                        "Failed to add product. Please try again.";
                }


                mysqli_stmt_close($stmt);


            } else {

                /*
                 * Delete uploaded image
                 * if statement preparation fails.
                 */

                if (file_exists($targetFile)) {
                    unlink($targetFile);
                }

                $success =
                    "Something went wrong. Please try again.";
            }


        } else {

            $imageErr =
                "File upload failed.";
        }
    }
}


include "includes/header.php";

?>


<!-- =========================
     PAGE HEADER
========================= -->

<div class="page-header">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="page-title">

                <i class="fa-solid fa-circle-plus text-primary me-2"></i>

                Add New Product

            </h2>

            <p class="text-muted mb-0 mt-2">

                Add a new product to your store inventory.

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


<!-- =========================
     SUCCESS MESSAGE
========================= -->

<?php if ($success !== ""): ?>

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert">

        <i class="fa-solid fa-circle-check me-2"></i>

        <?= htmlspecialchars($success) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>

<?php endif; ?>


<!-- =========================
     PRODUCT FORM
========================= -->

<div class="card">

    <div class="card-body p-4 p-md-5">

        <div class="mb-4">

            <h5 class="fw-bold mb-1">

                <i class="fa-solid fa-box-open text-primary me-2"></i>

                Product Information

            </h5>

            <p class="text-muted mb-0">

                Enter the details of the new product below.

            </p>

        </div>


        <form
            method="POST"
            action=""
            enctype="multipart/form-data">


            <!-- PRODUCT NAME -->

            <div class="mb-4">

                <label class="form-label">

                    Product Name

                </label>

                <input
                    type="text"
                    name="product_name"
                    class="form-control"
                    placeholder="Enter product name"
                    value="<?= htmlspecialchars($productName) ?>">

                <?php if ($productNameErr !== ""): ?>

                    <div class="text-danger small mt-1">

                        <i class="fa-solid fa-circle-exclamation me-1"></i>

                        <?= htmlspecialchars($productNameErr) ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- CATEGORY -->

            <div class="mb-4">

                <label class="form-label">

                    Category

                </label>

                <select
                    name="category_id"
                    class="form-select">

                    <option value="">
                        Select Category
                    </option>

                    <?php while (
                        $category = mysqli_fetch_assoc($categoryResult)
                    ): ?>

                        <option
                            value="<?= htmlspecialchars($category['id']) ?>"
                            <?= (
                                (string)$categoryId ===
                                (string)$category['id']
                            ) ? 'selected' : '' ?>>

                            <?= htmlspecialchars(
                                $category['name']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

                <?php if ($categoryErr !== ""): ?>

                    <div class="text-danger small mt-1">

                        <i class="fa-solid fa-circle-exclamation me-1"></i>

                        <?= htmlspecialchars($categoryErr) ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- DESCRIPTION -->

            <div class="mb-4">

                <label class="form-label">

                    Description

                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="5"
                    placeholder="Enter product description"><?= htmlspecialchars($description) ?></textarea>

            </div>


            <!-- PRICE + STOCK -->

            <div class="row">

                <div class="col-md-6 mb-4">

                    <label class="form-label">

                        Price

                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            Rs.

                        </span>

                        <input
                            type="number"
                            name="price"
                            step="0.01"
                            min="0"
                            class="form-control"
                            placeholder="0.00"
                            value="<?= htmlspecialchars($price) ?>">

                    </div>

                    <?php if ($priceErr !== ""): ?>

                        <div class="text-danger small mt-1">

                            <i class="fa-solid fa-circle-exclamation me-1"></i>

                            <?= htmlspecialchars($priceErr) ?>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="col-md-6 mb-4">

                    <label class="form-label">

                        Stock Quantity

                    </label>

                    <input
                        type="number"
                        name="stock_quantity"
                        min="0"
                        step="1"
                        class="form-control"
                        placeholder="Enter quantity"
                        value="<?= htmlspecialchars($stockQuantity) ?>">

                    <?php if ($stockErr !== ""): ?>

                        <div class="text-danger small mt-1">

                            <i class="fa-solid fa-circle-exclamation me-1"></i>

                            <?= htmlspecialchars($stockErr) ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- IMAGE -->

            <div class="mb-4">

                <label class="form-label">

                    Product Image

                </label>

                <input
                    type="file"
                    name="product_image"
                    id="product_image"
                    class="form-control"
                    accept=".jpg,.jpeg,.png">

                <div class="form-text">

                    JPG, JPEG or PNG. Maximum size: 500 KB.

                </div>

                <?php if ($imageErr !== ""): ?>

                    <div class="text-danger small mt-1">

                        <i class="fa-solid fa-circle-exclamation me-1"></i>

                        <?= htmlspecialchars($imageErr) ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- STATUS -->

            <div class="mb-4">

                <label class="form-label">

                    Status

                </label>

                <select
                    name="status"
                    class="form-select">

                    <option value="">
                        Select Status
                    </option>

                    <option
                        value="Active"
                        <?= (
                            $status === "Active"
                        ) ? "selected" : "" ?>>

                        Active

                    </option>

                    <option
                        value="Inactive"
                        <?= (
                            $status === "Inactive"
                        ) ? "selected" : "" ?>>

                        Inactive

                    </option>

                </select>

                <?php if ($statusErr !== ""): ?>

                    <div class="text-danger small mt-1">

                        <i class="fa-solid fa-circle-exclamation me-1"></i>

                        <?= htmlspecialchars($statusErr) ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- BUTTONS -->

            <div
                class="d-flex justify-content-end gap-2 pt-3 border-top">

                <a
                    href="products.php"
                    class="btn btn-outline-secondary">

                    <i class="fa-solid fa-xmark me-1"></i>

                    Cancel

                </a>

                <button
                    type="submit"
                    name="save_product"
                    class="btn btn-primary">

                    <i class="fa-solid fa-floppy-disk me-1"></i>

                    Save Product

                </button>

            </div>


        </form>

    </div>

</div>


<?php

include "includes/footer.php";

mysqli_close($connect);

?>