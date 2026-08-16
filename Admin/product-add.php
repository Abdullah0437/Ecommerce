<?php

session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Admin authentication

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch active categories

$categoryQuery = "SELECT id,name 
                  FROM categories 
                  WHERE status = 'Active' 
                  ORDER BY name ASC";

$categoryResult = mysqli_query($connect, $categoryQuery);

if (!$categoryResult) {
    die("Category Query Failed: " . mysqli_error($connect));
}

$productNameErr = "";
$categoryErr = "";
$priceErr = "";
$stockErr = "";
$imageErr = "";
$statusErr = "";

$success = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form values
    $productName = trim($_POST['product_name'] ?? "");
    $categoryId = $_POST['category_id'] ?? "";
    $description = trim($_POST['description'] ?? "");
    $price = $_POST['price'] ?? "";
    $stockQuantity = $_POST['stock_quantity'] ?? "";
    $status = $_POST['status'] ?? "";


    // Product Name Validation
    if ($productName === "") {
        $productNameErr = "Product name is required.";
    }


    // Category Validation
    if ($categoryId === "") {
        $categoryErr = "Please select a category.";
    }


    // Price Validation
    if ($price === "") {
        $priceErr = "Price is required.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $priceErr = "Price must be greater than 0.";
    }


    // Stock Validation
    if ($stockQuantity === "") {
        $stockErr = "Stock quantity is required.";
    } elseif (!is_numeric($stockQuantity) || $stockQuantity < 0) {
        $stockErr = "Stock cannot be negative.";
    }


    // Status Validation
    if ($status === "") {
        $statusErr = "Please select a status.";
    } elseif ($status !== "Active" && $status !== "Inactive") {
        $statusErr = "Invalid status.";
    }


    // Image Validation
    $target_dir = "../Images/";
    $target_file = "";

    if (!isset($_FILES["product_image"]) || $_FILES["product_image"]["error"] === UPLOAD_ERR_NO_FILE) {

        $imageErr = "Please choose a product image.";
    } elseif ($_FILES["product_image"]["error"] !== UPLOAD_ERR_OK) {

        $imageErr = "There was an error uploading the image.";
    } else {

        $maxsize = 2 * 1024 * 1024;

        $imageFileType = strtolower(
            pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION)
        );

        $getImage = getimagesize($_FILES["product_image"]["tmp_name"]);

        if ($getImage === false) {

            $imageErr = "File is not a valid image.";
        } elseif ($_FILES["product_image"]["size"] > $maxsize) {

            $imageErr = "File must be less than 2 MB.";
        } elseif (
            $imageFileType !== "jpg" &&
            $imageFileType !== "jpeg" &&
            $imageFileType !== "png" &&
            $imageFileType !== "gif"
        ) {

            $imageErr = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {

            $filename = uniqid() . "." . $imageFileType;
            $target_file = $target_dir . $filename;
        }
    }


    // If there are no errors
    if (
        $productNameErr === "" &&
        $categoryErr === "" &&
        $priceErr === "" &&
        $stockErr === "" &&
        $imageErr === "" &&
        $statusErr === ""
    ) {
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $product_image = $target_file;
        } else {
            $imageErr = "File upload failed.";
        }
        // Insert product
        $query = "INSERT INTO products
                      (category_id, name, description, price,
                       stock_quantity, image, status)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($connect, $query);

        mysqli_stmt_bind_param(
            $stmt,
            "issdiss",
            $categoryId,
            $productName,
            $description,
            $price,
            $stockQuantity,
            $target_file,
            $status
        );


        if (mysqli_stmt_execute($stmt)) {

            $success = "Product added successfully.";
        } else {

            $success = "Failed to add product.";
        }


        mysqli_stmt_close($stmt);
    } else {

        $imageErr = "Failed to save the uploaded image.";
    }
}

?>

<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <title>Add the Product</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>

<body>



    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-lg-9 col-xl-8">

                <!-- Page Header -->
                <div class="mb-4">
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-box-seam"></i>
                        Add New Product
                    </h2>

                    <p class="text-muted mb-0">
                        Add a new product to your store inventory.
                    </p>
                </div>


                <!-- Product Form Card -->
                <div class="card border-3  shadow-sm">

                    <!-- Card Header -->
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 ">
                            <b>Product Information</b>
                        </h5>
                    </div>


                    <div class="card-body p-4">

                        <?php if ($success !== "") { ?>

                            <div class="alert alert-success">
                                <?= htmlspecialchars($success) ?>
                            </div>

                        <?php } ?>

                        <form method="POST"
                            enctype="multipart/form-data">


                            <!-- Product Name -->
                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Product Name

                                </label>

                                <input
                                    type="text"
                                    name="product_name"
                                    class="form-control "
                                    placeholder="Enter product name">

                                <?php if ($productNameErr !== "") { ?>
                                    <div class="text-danger small mt-1">
                                        <?= htmlspecialchars($productNameErr) ?>
                                    </div>
                                <?php } ?>

                            </div>




                            <!-- Category -->
                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Category

                                </label>

                                <select
                                    name="category_id"
                                    class="form-select ">

                                    <option value="">
                                        Select Category
                                    </option>

                                    <?php while ($category = mysqli_fetch_assoc($categoryResult)) { ?>

                                        <option value="<?= $category['id'] ?>">
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>

                                    <?php } ?>

                                </select>

                                <?php if ($categoryErr !== "") { ?>
                                    <div class="text-danger small mt-1">
                                        <?= htmlspecialchars($categoryErr) ?>
                                    </div>
                                <?php } ?>
                            </div>




                            <!-- Description -->
                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Description
                                </label>

                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Enter product description"></textarea>

                            </div>




                            <!-- Price & Stock -->
                            <div class="row">

                                <!-- Price -->
                                <div class="col-md-6 mb-4">

                                    <label class="form-label fw-semibold">
                                        Price
                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">
                                            Rs.
                                        </span>

                                        <input
                                            type="number"
                                            name="price"
                                            class="form-control"
                                            placeholder="0.00">

                                    </div>

                                    <?php if ($priceErr !== "") { ?>
                                        <div class="text-danger small mt-1">
                                            <?= htmlspecialchars($priceErr) ?>
                                        </div>
                                    <?php } ?>
                                </div>




                                <!-- Stock -->
                                <div class="col-md-6 mb-4">

                                    <label class="form-label fw-semibold">
                                        Stock Quantity
                                    </label>

                                    <input
                                        type="number"
                                        name="stock_quantity"
                                        class="form-control "
                                        placeholder="Enter quantity">

                                    <?php if ($stockErr !== "") { ?>
                                        <div class="text-danger small mt-1">
                                            <?= htmlspecialchars($stockErr) ?>
                                        </div>
                                    <?php } ?>
                                </div>



                            </div>


                            <!-- Image -->
                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Product Image
                                </label>

                                <input
                                    type="file"
                                    name="product_image"
                                    id="product_image"
                                    class="form-control"
                                    accept="image/*">

                                <?php if ($imageErr !== "") { ?>
                                    <div class="text-danger small mt-1">
                                        <?= htmlspecialchars($imageErr) ?>
                                    </div>
                                <?php } ?>
                            </div>



                            <!-- Status -->
                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Status
                                </label>

                                <select
                                    name="status"
                                    class="form-select">

                                    <option value="">
                                        Select Status
                                    </option>

                                    <option value="Active">
                                        Active
                                    </option>

                                    <option value="Inactive">
                                        Inactive
                                    </option>

                                </select>


                                <?php if ($statusErr !== "") { ?>
                                    <div class="text-danger small mt-1">
                                        <?= htmlspecialchars($statusErr) ?>
                                    </div>
                                <?php } ?>

                            </div>







                            <!-- Buttons -->
                            <div class="d-flex justify-content-end gap-2">

                                <a
                                    href="products.php"
                                    class="btn btn-outline-secondary border px-4">

                                    Cancel

                                </a>


                                <button
                                    type="submit"
                                    name="save_product"
                                    class="btn btn-outline-success px-4">

                                    Save Product

                                </button>

                            </div>


                        </form>

                    </div>

                </div>




            </div>

        </div>

    </div>























</body>

</html>