<?php

/* =========================================================
   ADMIN - ADD PRODUCT
   Furnishop Admin Panel
========================================================= */

require_once __DIR__ . "/../Includes/auth.php";

if (!isset($connect)) {
    $host = "localhost"; $username = "root"; $password = ""; $database = "ecommerce";
    $connect = mysqli_connect($host, $username, $password, $database);
    if (!$connect) die("Database Connection Failed: " . mysqli_connect_error());
}


/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["admin_csrf"])) {
    $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION["admin_csrf"];


/* =========================================================
   HELPERS
========================================================= */

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}


/* =========================================================
   VARIABLES
========================================================= */

$errors = [
    "product_name"   => "",
    "category_id"    => "",
    "price"          => "",
    "stock_quantity" => "",
    "image"          => "",
    "status"         => "",
];

$productName   = "";
$categoryId    = "";
$description   = "";
$price         = "";
$stockQuantity = "";
$status        = "Active";


/* =========================================================
   FETCH ACTIVE CATEGORIES
========================================================= */

$categories = [];

$cres = mysqli_query(
    $connect,
    "SELECT id, name FROM categories WHERE status = 'Active' ORDER BY name ASC"
);

if ($cres) {
    while ($c = mysqli_fetch_assoc($cres)) {
        $categories[] = $c;
    }
}


/* =========================================================
   FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ---- CSRF ---- */
    if (
        empty($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"])
    ) {
        $errors["product_name"] = "Invalid session. Please refresh and try again.";
    }

    /* ---- INPUT ---- */
    $productName   = trim($_POST["product_name"]   ?? "");
    $categoryId    = trim($_POST["category_id"]    ?? "");
    $description   = trim($_POST["description"]    ?? "");
    $price         = trim($_POST["price"]          ?? "");
    $stockQuantity = trim($_POST["stock_quantity"] ?? "");
    $status        = trim($_POST["status"]         ?? "");

    /* ---- PRODUCT NAME ---- */
    if ($productName === "") {
        $errors["product_name"] = "Product name is required.";
    } elseif (strlen($productName) > 150) {
        $errors["product_name"] = "Product name cannot exceed 150 characters.";
    }

    /* ---- CATEGORY ---- */
    if ($categoryId === "") {
        $errors["category_id"] = "Please select a category.";
    } elseif (!ctype_digit((string) $categoryId)) {
        $errors["category_id"] = "Invalid category.";
    } else {
        $catCheck = mysqli_prepare(
            $connect,
            "SELECT id FROM categories WHERE id = ? AND status = 'Active' LIMIT 1"
        );
        mysqli_stmt_bind_param($catCheck, "i", $categoryId);
        mysqli_stmt_execute($catCheck);
        $catRes = mysqli_stmt_get_result($catCheck);

        if (!$catRes || mysqli_num_rows($catRes) !== 1) {
            $errors["category_id"] = "Selected category is invalid or inactive.";
        }
        mysqli_stmt_close($catCheck);
    }

    /* ---- PRICE ---- */
    if ($price === "") {
        $errors["price"] = "Price is required.";
    } elseif (!is_numeric($price)) {
        $errors["price"] = "Price must be a valid number.";
    } elseif ((float) $price <= 0) {
        $errors["price"] = "Price must be greater than 0.";
    }

    /* ---- STOCK ---- */
    if ($stockQuantity === "") {
        $errors["stock_quantity"] = "Stock quantity is required.";
    } elseif (!ctype_digit($stockQuantity)) {
        $errors["stock_quantity"] = "Stock quantity must be a whole number.";
    }

    /* ---- STATUS ---- */
    if ($status === "") {
        $errors["status"] = "Please select a status.";
    } elseif (!in_array($status, ["Active", "Inactive"], true)) {
        $errors["status"] = "Invalid status.";
    }

    /* ---- IMAGE ---- */
    $targetDir   = __DIR__ . "/../Assets/Images/product/";
    $imageDbPath = "";
    $targetFile  = "";

    if (
        !isset($_FILES["product_image"]) ||
        $_FILES["product_image"]["error"] === UPLOAD_ERR_NO_FILE
    ) {
        $errors["image"] = "Please choose a product image.";
    } elseif ($_FILES["product_image"]["error"] !== UPLOAD_ERR_OK) {
        $errors["image"] = "There was an error uploading the image.";
    } else {

        $maxSize = 500 * 1024;

        $imageName     = $_FILES["product_image"]["name"];
        $imageTmpName  = $_FILES["product_image"]["tmp_name"];
        $imageSize     = $_FILES["product_image"]["size"];
        $imageFileType = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

        $getImage = @getimagesize($imageTmpName);

        if ($getImage === false) {
            $errors["image"] = "File is not a valid image.";
        } elseif ($imageSize > $maxSize) {
            $errors["image"] = "Image size must not exceed 500 KB.";
        } elseif (!in_array($imageFileType, ["jpg", "jpeg", "png"], true)) {
            $errors["image"] = "Only JPG, JPEG and PNG images are allowed.";
        } else {

            $newFileName = uniqid("product_", true) . "." . $imageFileType;
            $targetFile  = $targetDir . $newFileName;

            /* Store just the filename — matches product-edit.php */
            $imageDbPath = $newFileName;
        }
    }


    /* ---- INSERT ---- */
    $hasErrors = false;
    foreach ($errors as $err) {
        if ($err !== "") { $hasErrors = true; break; }
    }

    if (!$hasErrors) {

        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetFile)) {

            /* NOTE: your DB uses uppercase NAME and STATUS columns */
            $insert = mysqli_prepare(
                $connect,
                "INSERT INTO products
                    (category_id, NAME, description, price, stock_quantity, image, STATUS)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if ($insert) {

                $categoryId    = (int)   $categoryId;
                $price         = (float) $price;
                $stockQuantity = (int)   $stockQuantity;

                mysqli_stmt_bind_param(
                    $insert,
                    "issdiss",
                    $categoryId,
                    $productName,
                    $description,
                    $price,
                    $stockQuantity,
                    $imageDbPath,
                    $status
                );

                if (mysqli_stmt_execute($insert)) {

                    $_SESSION["flash_success"] = "Product added successfully.";
                    header("Location: products.php");
                    exit;

                } else {

                    if (file_exists($targetFile)) unlink($targetFile);
                    $errors["product_name"] = "Failed to add product. Please try again.";
                }

                mysqli_stmt_close($insert);

            } else {

                if (file_exists($targetFile)) unlink($targetFile);
                $errors["product_name"] = "Something went wrong. Please try again.";
            }

        } else {
            $errors["image"] = "File upload failed.";
        }
    }
}


/* =========================================================
   PAGE META + HEADER
========================================================= */

$pageTitle      = "Add Product";
$pageHeading    = "Add New Product";
$pageSubheading = "Add a new product to your store inventory";

require_once __DIR__ . "/Includes/header.php";
?>


<!-- =========================================================
     BACK BUTTON
========================================================= -->

<div class="mb-4">
    <a href="products.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Products
    </a>
</div>


<!-- =========================================================
     FORM
========================================================= -->

<form method="post" enctype="multipart/form-data" novalidate>

    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">

    <div class="panel">

        <div class="panel-header">
            <h3>
                <i class="fa-solid fa-box-open"></i>
                Product Information
            </h3>
        </div>

        <div class="panel-body">

            <h6 class="text-uppercase text-muted fw-bold mb-3"
                style="font-size: 0.72rem; letter-spacing: 0.08em;">
                Basic Details
            </h6>

            <div class="row g-3">

                <div class="col-md-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Product Name <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        name="product_name"
                        class="form-control <?php echo $errors["product_name"] ? "is-invalid" : ""; ?>"
                        placeholder="e.g. Wireless Bluetooth Headphones"
                        value="<?php echo e($productName); ?>"
                        required>
                    <?php if ($errors["product_name"]): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <?php echo e($errors["product_name"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Category <span class="text-danger">*</span>
                    </label>
                    <select
                        name="category_id"
                        class="form-select <?php echo $errors["category_id"] ? "is-invalid" : ""; ?>"
                        required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option
                                value="<?php echo (int) $cat["id"]; ?>"
                                <?php echo (string) $categoryId === (string) $cat["id"] ? "selected" : ""; ?>>
                                <?php echo e($cat["name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($errors["category_id"]): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <?php echo e($errors["category_id"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select
                        name="status"
                        class="form-select <?php echo $errors["status"] ? "is-invalid" : ""; ?>"
                        required>
                        <option value="">Select Status</option>
                        <option value="Active"   <?php echo $status === "Active"   ? "selected" : ""; ?>>Active</option>
                        <option value="Inactive" <?php echo $status === "Inactive" ? "selected" : ""; ?>>Inactive</option>
                    </select>
                    <?php if ($errors["status"]): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <?php echo e($errors["status"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Description
                    </label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="5"
                        placeholder="Describe the product features, specifications, etc."><?php echo e($description); ?></textarea>
                </div>

            </div>

            <hr class="my-4">

            <h6 class="text-uppercase text-muted fw-bold mb-3"
                style="font-size: 0.72rem; letter-spacing: 0.08em;">
                Pricing &amp; Stock
            </h6>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Price <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">Rs.</span>
                        <input
                            type="number"
                            name="price"
                            step="0.01"
                            min="0"
                            class="form-control <?php echo $errors["price"] ? "is-invalid" : ""; ?>"
                            placeholder="0.00"
                            value="<?php echo e($price); ?>"
                            required>
                    </div>
                    <?php if ($errors["price"]): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <?php echo e($errors["price"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Stock Quantity <span class="text-danger">*</span>
                    </label>
                    <input
                        type="number"
                        name="stock_quantity"
                        min="0"
                        step="1"
                        class="form-control <?php echo $errors["stock_quantity"] ? "is-invalid" : ""; ?>"
                        placeholder="Enter quantity"
                        value="<?php echo e($stockQuantity); ?>"
                        required>
                    <?php if ($errors["stock_quantity"]): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <?php echo e($errors["stock_quantity"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <hr class="my-4">

            <h6 class="text-uppercase text-muted fw-bold mb-3"
                style="font-size: 0.72rem; letter-spacing: 0.08em;">
                Product Image
            </h6>

            <div class="row g-3">

                <div class="col-md-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Product Image <span class="text-danger">*</span>
                    </label>
                    <input
                        type="file"
                        name="product_image"
                        id="product_image"
                        class="form-control <?php echo $errors["image"] ? "is-invalid" : ""; ?>"
                        accept=".jpg,.jpeg,.png"
                        required>
                    <small class="text-muted d-block mt-2">
                        JPG, JPEG or PNG. Maximum size: 500 KB.
                    </small>

                    <div class="d-flex align-items-center gap-3 mt-3 p-3"
                         style="border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;">

                        <div id="previewPlaceholder"
                             style="width:76px;height:76px;border-radius:12px;background:#fff;border:1px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;color:#94a3b8;flex-shrink:0;">
                            <i class="fa-solid fa-image fa-lg"></i>
                        </div>

                        <img
                            src=""
                            alt="Preview"
                            id="imagePreview"
                            class="d-none"
                            style="width:76px;height:76px;border-radius:12px;object-fit:cover;border:1px solid #e9eef5;background:#fff;flex-shrink:0;">

                        <div>
                            <div class="fw-semibold" style="font-size:13.5px;color:#0f172a;">
                                Image Preview
                            </div>
                            <small class="text-muted">
                                The selected image will appear here.
                            </small>
                        </div>

                    </div>

                    <?php if ($errors["image"]): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <?php echo e($errors["image"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

        <div class="panel-header" style="border-top:1px solid #f1f5f9;border-bottom:none;background:#f8fafc;justify-content:flex-end;">

            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-xmark me-1"></i> Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Save Product
            </button>

        </div>

    </div>

</form>


<script>
    /* ===== Live image preview ===== */
    (function () {
        var input       = document.getElementById('product_image');
        var preview     = document.getElementById('imagePreview');
        var placeholder = document.getElementById('previewPlaceholder');

        if (!input || !preview || !placeholder) return;

        input.addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (!file) {
                preview.classList.add('d-none');
                placeholder.classList.remove('d-none');
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                placeholder.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    })();
</script>


<?php require_once __DIR__ . "/Includes/footer.php"; ?>