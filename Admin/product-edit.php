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
   CHECK PRODUCT ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = (int)$_GET['id'];


/* =========================
   FETCH PRODUCT
========================= */

$query = "
    SELECT *
    FROM products
    WHERE id = ?
";

$stmt = mysqli_prepare(
    $connect,
    $query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $product_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: products.php");
    exit;
}


/* =========================
   FETCH CATEGORIES
========================= */

$categoryQuery = "
    SELECT id, name
    FROM categories
    ORDER BY name ASC
";

$categoryResult = mysqli_query(
    $connect,
    $categoryQuery
);


/* =========================
   ERROR VARIABLES
========================= */

$nameErr = "";
$categoryErr = "";
$priceErr = "";
$stockErr = "";
$imageErr = "";


/* =========================
   UPDATE PRODUCT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name'] ?? "");
    $category_id = $_POST['category_id'] ?? "";
    $description = trim($_POST['description'] ?? "");
    $price = $_POST['price'] ?? "";
    $stock = $_POST['stock_quantity'] ?? "";
    $status = $_POST['status'] ?? "";


    /* PRODUCT NAME */

    if ($name == "") {

        $nameErr = "Product name is required.";

    }


    /* CATEGORY */

    if (
        $category_id == "" ||
        !is_numeric($category_id)
    ) {

        $categoryErr = "Category is required.";

    }


    /* PRICE */

    if ($price == "") {

        $priceErr = "Price is required.";

    } elseif (
        !is_numeric($price) ||
        $price <= 0
    ) {

        $priceErr = "Enter a valid price.";

    }


    /* STOCK */

    if ($stock == "") {

        $stockErr = "Stock is required.";

    } elseif (
        !is_numeric($stock) ||
        $stock < 0
    ) {

        $stockErr =
            "Stock must be a whole number and cannot be negative.";

    }


    /* IMAGE */

    $image = $product['image'];

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] != 4
    ) {

        if ($_FILES['image']['error'] != 0) {

            $imageErr = "Image upload failed.";

        } else {

            $imageSize = $_FILES['image']['size'];
            $imageTmp = $_FILES['image']['tmp_name'];


            if ($imageSize > 500000) {

                $imageErr =
                    "Image size must be less than 500KB.";

            } elseif (
                getimagesize($imageTmp) === false
            ) {

                $imageErr =
                    "Please select a valid image.";

            } else {

                $imageType =
                    mime_content_type($imageTmp);


                if (
                    $imageType != "image/jpeg" &&
                    $imageType != "image/png"
                ) {

                    $imageErr =
                        "Only JPG and PNG images are allowed.";

                } else {

                    if ($imageType == "image/jpeg") {

                        $extension = "jpg";

                    } else {

                        $extension = "png";

                    }

                    $image =
                        uniqid() . "." . $extension;
                }
            }
        }
    }


    /* =========================
       VALIDATION COMPLETE
    ========================= */

    if (
        $nameErr == "" &&
        $categoryErr == "" &&
        $priceErr == "" &&
        $stockErr == "" &&
        $imageErr == ""
    ) {


        /* UPLOAD NEW IMAGE */

        if ($image != $product['image']) {

            $imageFolder = "../Images/";

            if (!move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $imageFolder . $image
            )) {

                $imageErr =
                    "Image could not be uploaded.";

            }

        }


        /* UPDATE DATABASE */

        if ($imageErr == "") {

            $update = "
                UPDATE products
                SET
                    category_id = ?,
                    NAME = ?,
                    description = ?,
                    price = ?,
                    stock_quantity = ?,
                    image = ?,
                    STATUS = ?
                WHERE id = ?
            ";


            $updateStmt = mysqli_prepare(
                $connect,
                $update
            );


            mysqli_stmt_bind_param(
                $updateStmt,
                "issdissi",
                $category_id,
                $name,
                $description,
                $price,
                $stock,
                $image,
                $status,
                $product_id
            );


            if (mysqli_stmt_execute($updateStmt)) {


                /* DELETE OLD IMAGE */

                if (
                    $image != $product['image'] &&
                    !empty($product['image'])
                ) {

                    $oldImage =
                        "../Images/" .
                        $product['image'];

                    if (file_exists($oldImage)) {

                        unlink($oldImage);

                    }

                }


                header(
                    "Location: products.php?message=product_updated"
                );

                exit;

            }

        }

    }

}


include "includes/header.php";

?>


<!-- =========================
     PAGE HEADER
========================= -->

<div class="page-header">

    <div
        class="d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="page-title">

                <i class="fa-solid fa-pen-to-square text-primary me-2"></i>

                Edit Product

            </h2>

            <p class="text-muted mb-0 mt-2">

                Update product information, price, stock and status.

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
     EDIT CARD
========================= -->

<div class="card">

    <div class="card-body p-4 p-md-5">


        <div class="mb-4">

            <h5 class="fw-bold mb-1">

                <i class="fa-solid fa-box-open text-primary me-2"></i>

                Product Information

            </h5>

            <p class="text-muted mb-0">

                Make changes to the product details below.

            </p>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data">


            <!-- PRODUCT NAME -->

            <div class="mb-4">

                <label class="form-label">

                    Product Name

                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="Enter product name"
                    value="<?= htmlspecialchars(
                        $_POST['name'] ??
                        $product['NAME']
                    ) ?>">

                <?php if ($nameErr != ""): ?>

                    <div class="text-danger small mt-1">

                        <i class="fa-solid fa-circle-exclamation me-1"></i>

                        <?= htmlspecialchars($nameErr) ?>

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
                        $category =
                        mysqli_fetch_assoc($categoryResult)
                    ): ?>

                        <option
                            value="<?= $category['id'] ?>"
                            <?= (
                                (
                                    $_POST['category_id'] ??
                                    $product['category_id']
                                ) == $category['id']
                            )
                                ? 'selected'
                                : '' ?>>

                            <?= htmlspecialchars(
                                $category['name']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <?php if ($categoryErr != ""): ?>

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
                    rows="5"
                    class="form-control text-start"
                    placeholder="Enter product description"><?= htmlspecialchars(
                        $_POST['description'] ??
                        $product['description']
                    ) ?></textarea>

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
                            value="<?= htmlspecialchars(
                                $_POST['price'] ??
                                $product['price']
                            ) ?>">

                    </div>


                    <?php if ($priceErr != ""): ?>

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
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $_POST['stock_quantity'] ??
                            $product['stock_quantity']
                        ) ?>">

                    <?php if ($stockErr != ""): ?>

                        <div class="text-danger small mt-1">

                            <i class="fa-solid fa-circle-exclamation me-1"></i>

                            <?= htmlspecialchars($stockErr) ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- CURRENT IMAGE -->

            <div class="mb-4">

                <label class="form-label">

                    Current Image

                </label>


                <div
                    class="border rounded-3 p-3 d-flex align-items-center gap-3">

                    <?php

                    $imagePath =
                        "../Images/" .
                        $product['image'];

                    ?>


                    <?php if (
                        !empty($product['image']) &&
                        file_exists($imagePath)
                    ): ?>

                        <img
                            src="<?= htmlspecialchars(
                                $imagePath
                            ) ?>"
                            alt="Product Image"
                            class="product-image">


                        <div>

                            <div class="fw-semibold">

                                Current Product Image

                            </div>

                            <small class="text-muted">

                                Upload a new image below to replace it.

                            </small>

                        </div>


                    <?php else: ?>

                        <div class="no-image">

                            <i class="fa-solid fa-image"></i>

                        </div>


                        <div>

                            <div class="fw-semibold">

                                No Image

                            </div>

                            <small class="text-muted">

                                This product does not have an image.

                            </small>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- CHANGE IMAGE -->

            <div class="mb-4">

                <label class="form-label">

                    Change Image

                </label>

                <input
                    type="file"
                    name="image"
                    class="form-control"
                    accept=".jpg,.jpeg,.png">


                <div class="form-text">

                    JPG or PNG only. Maximum size: 500KB.

                </div>


                <?php if ($imageErr != ""): ?>

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

                    <option
                        value="Active"
                        <?= (
                            (
                                $_POST['status'] ??
                                $product['STATUS']
                            ) == "Active"
                        )
                            ? "selected"
                            : "" ?>>

                        Active

                    </option>


                    <option
                        value="Inactive"
                        <?= (
                            (
                                $_POST['status'] ??
                                $product['STATUS']
                            ) == "Inactive"
                        )
                            ? "selected"
                            : "" ?>>

                        Inactive

                    </option>

                </select>

            </div>


            <!-- BUTTONS -->

            <div
                class="border-top pt-4 d-flex justify-content-end gap-2 flex-wrap">

                <a
                    href="products.php"
                    class="btn btn-outline-secondary">

                    <i class="fa-solid fa-xmark me-1"></i>

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn btn-primary">

                    <i class="fa-solid fa-floppy-disk me-1"></i>

                    Update Product

                </button>

            </div>


        </form>

    </div>

</div>


<?php

include "includes/footer.php";

mysqli_close($connect);

?>