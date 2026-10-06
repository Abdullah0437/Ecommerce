<?php

/* =========================================================
   ADMIN - EDIT PRODUCT
   Furnishop Admin Panel
========================================================= */

require_once __DIR__ . "/../Includes/auth.php";

if (!isset($connect)) {
    $host = "localhost"; $username = "root"; $password = ""; $database = "ecommerce";
    $connect = mysqli_connect($host, $username, $password, $database);
    if (!$connect) die("Database Connection Failed: " . mysqli_connect_error());
}


/* =========================================================
   CSRF
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
   CHECK PRODUCT ID
========================================================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: products.php");
    exit;
}

$productId = (int) $_GET["id"];


/* =========================================================
   FETCH PRODUCT
========================================================= */

$product = null;

$stmt = mysqli_prepare($connect, "SELECT * FROM products WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$product) {
    header("Location: products.php");
    exit;
}


/* =========================================================
   FETCH CATEGORIES
========================================================= */

$categories = [];

$cres = mysqli_query($connect, "SELECT id, name FROM categories ORDER BY name ASC");
if ($cres) {
    while ($c = mysqli_fetch_assoc($cres)) $categories[] = $c;
}


/* =========================================================
   VARIABLES
   NOTE: your products table uses uppercase NAME / STATUS columns
========================================================= */

$errors = [
    "name"           => "",
    "category_id"    => "",
    "price"          => "",
    "stock_quantity" => "",
    "image"          => "",
    "status"         => "",
];

$name          = $product["NAME"]      ?? "";
$categoryId    = $product["category_id"] ?? "";
$description   = $product["description"] ?? "";
$price         = $product["price"]        ?? "";
$stockQuantity = $product["stock_quantity"] ?? "";
$status        = $product["STATUS"]     ?? "Active";


/* =========================================================
   HANDLE SUBMIT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF */
    if (
        empty($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"])
    ) {
        $errors["name"] = "Invalid session. Please refresh and try again.";
    }

    $name          = trim($_POST["name"]           ?? "");
    $categoryId    = trim($_POST["category_id"]    ?? "");
    $description   = trim($_POST["description"]    ?? "");
    $price         = trim($_POST["price"]          ?? "");
    $stockQuantity = trim($_POST["stock_quantity"] ?? "");
    $status        = trim($_POST["status"]         ?? "");

    /* NAME */
    if ($name === "") {
        $errors["name"] = "Product name is required.";
    } elseif (strlen($name) > 150) {
        $errors["name"] = "Product name cannot exceed 150 characters.";
    }

    /* CATEGORY */
    if ($categoryId === "" || !ctype_digit((string) $categoryId)) {
        $errors["category_id"] = "Please select a valid category.";
    } else {
        $chk = mysqli_prepare($connect, "SELECT id FROM categories WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($chk, "i", $categoryId);
        mysqli_stmt_execute($chk);
        $chkRes = mysqli_stmt_get_result($chk);

        if (!$chkRes || mysqli_num_rows($chkRes) !== 1) {
            $errors["category_id"] = "Selected category does not exist.";
        }
        mysqli_stmt_close($chk);
    }

    /* PRICE */
    if ($price === "") {
        $errors["price"] = "Price is required.";
    } elseif (!is_numeric($price) || (float) $price <= 0) {
        $errors["price"] = "Enter a valid price greater than 0.";
    }

    /* STOCK */
    if ($stockQuantity === "") {
        $errors["stock_quantity"] = "Stock is required.";
    } elseif (!ctype_digit($stockQuantity)) {
        $errors["stock_quantity"] = "Stock must be a whole number.";
    }

    /* STATUS */
    if (!in_array($status, ["Active", "Inactive"], true)) {
        $errors["status"] = "Invalid status.";
    }

    /* IMAGE (optional) */
    $newImageName = null;
    $targetFile   = null;

    $hasNewImage = isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE;

    if ($hasNewImage) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

            $errors["image"] = "Image upload failed.";

        } else {

            $tmpName = $_FILES["image"]["tmp_name"];
            $size    = $_FILES["image"]["size"];

            $info = @getimagesize($tmpName);

            if ($info === false) {
                $errors["image"] = "Please select a valid image.";
            } elseif ($size > 500 * 1024) {
                $errors["image"] = "Image size must be less than 500 KB.";
            } elseif (!in_array($info["mime"], ["image/jpeg", "image/png"], true)) {
                $errors["image"] = "Only JPG and PNG images are allowed.";
            } else {

                $ext = ($info["mime"] === "image/jpeg") ? "jpg" : "png";
                $newImageName = uniqid("product_", true) . "." . $ext;

                /* DB stores just the filename */
                $targetFile = __DIR__ . "/../Assets/Images/product/" . $newImageName;
            }
        }
    }

    /* INSERT */
    $hasErrors = false;
    foreach ($errors as $err) {
        if ($err !== "") { $hasErrors = true; break; }
    }

    if (!$hasErrors) {

        /* Upload new image first (if any) */
        $uploadedNew = false;

        if ($hasNewImage && $newImageName !== null && $targetFile !== null) {

            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $errors["image"]  = "Image could not be uploaded.";
                $hasErrors = true;
            } else {
                $uploadedNew = true;
            }
        }

        if (!$hasErrors) {

            /* Final image value */
            $finalImage = $uploadedNew ? $newImageName : ($product["image"] ?? "");

            /* NOTE: column names NAME and STATUS are uppercase in this DB */
            $sql = "UPDATE products SET
                        category_id    = ?,
                        NAME           = ?,
                        description    = ?,
                        price          = ?,
                        stock_quantity = ?,
                        image          = ?,
                        STATUS         = ?
                    WHERE id = ?";

            $upd = mysqli_prepare($connect, $sql);

            if (!$upd) {
                if ($uploadedNew && file_exists($targetFile)) unlink($targetFile);
                $errors["name"] = "Something went wrong. Please try again.";
            } else {

                $categoryId    = (int)   $categoryId;
                $price         = (float) $price;
                $stockQuantity = (int)   $stockQuantity;

                mysqli_stmt_bind_param(
                    $upd,
                    "issdissi",
                    $categoryId,
                    $name,
                    $description,
                    $price,
                    $stockQuantity,
                    $finalImage,
                    $status,
                    $productId
                );

                if (mysqli_stmt_execute($upd)) {

                    /* Delete old image only if new one replaced it */
                    if ($uploadedNew && !empty($product["image"])) {

                        $oldPath = __DIR__ . "/../Assets/Images/product/" . $product["image"];

                        if (
                            file_exists($oldPath) &&
                            $product["image"] !== $finalImage
                        ) {
                            unlink($oldPath);
                        }
                    }

                    $_SESSION["flash_success"] = "Product updated successfully.";
                    header("Location: products.php");
                    exit;

                } else {

                    if ($uploadedNew && file_exists($targetFile)) unlink($targetFile);
                    $errors["name"] = "Failed to update product.";
                }

                mysqli_stmt_close($upd);
            }
        }
    }
}


/* =========================================================
   PAGE META + HEADER
========================================================= */

$pageTitle      = "Edit Product";
$pageHeading    = "Edit Product #" . $productId;
$pageSubheading = "Update product information, price, stock and status";

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
                <i class="fa-solid fa-pen-to-square"></i>
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
                        name="name"
                        class="form-control <?php echo $errors["name"] ? "is-invalid" : ""; ?>"
                        placeholder="Enter product name"
                        value="<?php echo e($name); ?>"
                        required>
                    <?php if ($errors["name"]): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <?php echo e($errors["name"]); ?>
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

                <div class="col-md-6">

                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Current Image
                    </label>

                    <div class="d-flex align-items-center gap-3 p-3"
                         style="border:1px solid #e9eef5;border-radius:12px;background:#f8fafc;">

                        <?php
                        $currentImageFile = __DIR__ . "/../Assets/Images/product/" . ($product["image"] ?? "");
                        $currentImageUrl  = "../Assets/Images/product/" . rawurlencode(basename((string) ($product["image"] ?? "")));

                        if (!empty($product["image"]) && file_exists($currentImageFile)):
                        ?>
                            <img
                                src="<?php echo e($currentImageUrl); ?>"
                                alt="Product Image"
                                style="width:76px;height:76px;border-radius:12px;object-fit:cover;border:1px solid #e9eef5;background:#fff;flex-shrink:0;">
                            <div>
                                <div class="fw-semibold" style="font-size:13px;">
                                    Current Image
                                </div>
                                <small class="text-muted">
                                    Upload a new image below to replace it.
                                </small>
                            </div>
                        <?php else: ?>
                            <div style="width:76px;height:76px;border-radius:12px;background:#fff;border:1px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;color:#94a3b8;flex-shrink:0;">
                                <i class="fa-solid fa-image fa-lg"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size:13px;">
                                    No Image
                                </div>
                                <small class="text-muted">
                                    Upload an image below.
                                </small>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

                <div class="col-md-6">

                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Replace Image (optional)
                    </label>

                    <input
                        type="file"
                        name="image"
                        id="image"
                        class="form-control <?php echo $errors["image"] ? "is-invalid" : ""; ?>"
                        accept=".jpg,.jpeg,.png">

                    <small class="text-muted d-block mt-2">
                        JPG or PNG only. Maximum size: 500 KB.
                    </small>

                    <?php if ($errors["image"]): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <?php echo e($errors["image"]); ?>
                        </div>
                    <?php endif; ?>

                    <img
                        src=""
                        alt="New preview"
                        id="preview"
                        class="d-none mt-3"
                        style="width:76px;height:76px;border-radius:12px;object-fit:cover;border:1px solid #e9eef5;background:#fff;">

                </div>

            </div>

        </div>

        <div class="panel-header" style="border-top:1px solid #f1f5f9;border-bottom:none;background:#f8fafc;justify-content:flex-end;">

            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-xmark me-1"></i> Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Update Product
            </button>

        </div>

    </div>

</form>


<script>
    /* Live preview of the newly chosen image */
    (function () {
        var input   = document.getElementById("image");
        var preview = document.getElementById("preview");

        if (!input || !preview) return;

        input.addEventListener("change", function () {
            var file = this.files && this.files[0];
            if (!file) {
                preview.classList.add("d-none");
                return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) {
                preview.src = ev.target.result;
                preview.classList.remove("d-none");
            };
            reader.readAsDataURL(file);
        });
    })();
</script>


<?php require_once __DIR__ . "/Includes/footer.php"; ?>