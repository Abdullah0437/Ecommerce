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

<style>
    /* ===== Page Header ===== */
    .page-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e9eef5;
        border-radius: 18px;
        padding: 26px 28px;
        margin-bottom: 22px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    }

    .page-title {
        font-weight: 700;
        font-size: 1.5rem;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .page-title i {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* ===== Card ===== */
    .form-card {
        border: 1px solid #e9eef5;
        border-radius: 18px;
        box-shadow: 0 2px 14px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    /* ===== Section Headers ===== */
    .section-title {
        font-weight: 700;
        color: #0f172a;
        font-size: 1.05rem;
        margin: 0;
    }

    .section-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #eff6ff, #e0e7ff);
        color: #4f46e5;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* ===== Form Labels ===== */
    .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.88rem;
        margin-bottom: 7px;
    }

    /* ===== Inputs ===== */
    .form-control,
    .form-select {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        padding: 11px 14px;
        font-size: 0.92rem;
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    textarea.form-control {
        min-height: 130px;
        resize: vertical;
    }

    .input-group-text {
        border-radius: 10px 0 0 10px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        font-size: 0.88rem;
    }

    .input-group .form-control {
        border-radius: 0 10px 10px 0;
    }

    /* ===== Error Message ===== */
    .field-error {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #dc2626;
        font-size: 0.8rem;
        margin-top: 6px;
        font-weight: 500;
    }

    /* ===== Image Preview ===== */
    .image-preview-wrap {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        margin-top: 12px;
    }

    .image-preview {
        width: 76px;
        height: 76px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #e9eef5;
        background: #fff;
        flex-shrink: 0;
    }

    .image-preview-placeholder {
        width: 76px;
        height: 76px;
        border-radius: 12px;
        background: #fff;
        border: 1px dashed #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        flex-shrink: 0;
    }

    /* ===== Form Footer ===== */
    .form-actions {
        background: #f8fafc;
        border-top: 1px solid #e9eef5;
        padding: 18px 24px;
        border-radius: 0 0 18px 18px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .form-actions .btn {
        border-radius: 10px;
        padding: 10px 22px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* ===== Alerts ===== */
    .alert {
        border: none;
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 0.9rem;
    }

    .alert-success {
        background: #ecfdf5;
        color: #065f46;
        border-left: 4px solid #10b981;
    }

    /* ===== Divider ===== */
    .form-divider {
        border-top: 1px solid #f1f5f9;
        margin: 28px 0;
    }
</style>


<!-- =========================
     PAGE HEADER
========================= -->

<div class="page-header">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="page-title">

                <i class="fa-solid fa-circle-plus me-2"></i>

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

<div class="card form-card">

    <div class="card-body p-4 p-md-5">

        <!-- HEADER -->

        <div class="d-flex align-items-center gap-3 mb-4">

            <div class="section-icon">

                <i class="fa-solid fa-box-open"></i>

            </div>

            <div>

                <h5 class="section-title">

                    Product Information

                </h5>

                <small class="text-muted">

                    Enter the details of the new product below.

                </small>

            </div>

        </div>


        <form
            method="POST"
            action=""
            enctype="multipart/form-data">


            <!-- =========================
                 BASIC DETAILS
            ========================= -->

            <h6 class="text-uppercase text-muted fw-bold mb-3"
                style="font-size: 0.72rem; letter-spacing: 0.08em;">

                Basic Details

            </h6>


            <!-- PRODUCT NAME -->

            <div class="mb-4">

                <label class="form-label">

                    Product Name <span class="text-danger">*</span>

                </label>

                <input
                    type="text"
                    name="product_name"
                    class="form-control"
                    placeholder="e.g. Wireless Bluetooth Headphones"
                    value="<?= htmlspecialchars($productName) ?>">

                <?php if ($productNameErr !== ""): ?>

                    <div class="field-error">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= htmlspecialchars($productNameErr) ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- CATEGORY -->

            <div class="mb-4">

                <label class="form-label">

                    Category <span class="text-danger">*</span>

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

                    <div class="field-error">

                        <i class="fa-solid fa-circle-exclamation"></i>

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
                    placeholder="Describe the product features, specifications, etc."><?= htmlspecialchars($description) ?></textarea>

            </div>


            <div class="form-divider"></div>


            <!-- =========================
                 PRICING & STOCK
            ========================= -->

            <h6 class="text-uppercase text-muted fw-bold mb-3"
                style="font-size: 0.72rem; letter-spacing: 0.08em;">

                Pricing &amp; Stock

            </h6>


            <div class="row">

                <div class="col-md-6 mb-4">

                    <label class="form-label">

                        Price <span class="text-danger">*</span>

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

                        <div class="field-error">

                            <i class="fa-solid fa-circle-exclamation"></i>

                            <?= htmlspecialchars($priceErr) ?>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="col-md-6 mb-4">

                    <label class="form-label">

                        Stock Quantity <span class="text-danger">*</span>

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

                        <div class="field-error">

                            <i class="fa-solid fa-circle-exclamation"></i>

                            <?= htmlspecialchars($stockErr) ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <div class="form-divider"></div>


            <!-- =========================
                 MEDIA & STATUS
            ========================= -->

            <h6 class="text-uppercase text-muted fw-bold mb-3"
                style="font-size: 0.72rem; letter-spacing: 0.08em;">

                Media &amp; Availability

            </h6>


            <!-- IMAGE -->

            <div class="mb-4">

                <label class="form-label">

                    Product Image <span class="text-danger">*</span>

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


                <!-- Live Preview -->

                <div class="image-preview-wrap" id="previewWrap">

                    <div class="image-preview-placeholder" id="previewPlaceholder">

                        <i class="fa-solid fa-image fa-lg"></i>

                    </div>

                    <img
                        src=""
                        alt="Preview"
                        class="image-preview d-none"
                        id="imagePreview">

                    <div>

                        <div class="fw-semibold text-dark"
                             style="font-size: 0.88rem;">

                            Image Preview

                        </div>

                        <small class="text-muted">

                            The selected image will appear here.

                        </small>

                    </div>

                </div>


                <?php if ($imageErr !== ""): ?>

                    <div class="field-error">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= htmlspecialchars($imageErr) ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- STATUS -->

            <div class="mb-2">

                <label class="form-label">

                    Status <span class="text-danger">*</span>

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

                    <div class="field-error">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= htmlspecialchars($statusErr) ?>

                    </div>

                <?php endif; ?>

            </div>


        </form>

    </div>


    <!-- =========================
         ACTION BUTTONS
    ========================= -->

    <div class="form-actions">

        <a
            href="products.php"
            class="btn btn-outline-secondary">

            <i class="fa-solid fa-xmark me-1"></i>

            Cancel

        </a>

        <button
            type="submit"
            form=""
            name="save_product"
            class="btn btn-primary"
            onclick="document.querySelector('form').submit();">

            <i class="fa-solid fa-floppy-disk me-1"></i>

            Save Product

        </button>

    </div>

</div>


<script>
    /* ===== Live image preview ===== */
    (function () {
        var input = document.getElementById('product_image');
        var preview = document.getElementById('imagePreview');
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


<?php

include "includes/footer.php";

mysqli_close($connect);

?>