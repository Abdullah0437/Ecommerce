<?php

/* =========================================================
   ADMIN - STOCK MANAGEMENT
   Furnishop Admin Panel
========================================================= */

require_once __DIR__ . "/../Includes/auth.php";

if (!isset($connect)) {
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "ecommerce";
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

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}


/* =========================================================
   HANDLE STOCK UPDATE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_stock"])) {

    /* CSRF */
    if (
        empty($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"])
    ) {
        $_SESSION["flash_error"] = "Invalid form submission.";
        header("Location: stock.php");
        exit;
    }

    $productId = (int) ($_POST["product_id"] ?? 0);
    $stock     = $_POST["stock_quantity"] ?? "";

    if ($productId <= 0) {
        $_SESSION["flash_error"] = "Invalid product.";
    } elseif ($stock === "") {
        $_SESSION["flash_error"] = "Stock quantity is required.";
    } elseif (!ctype_digit((string) $stock)) {
        $_SESSION["flash_error"] = "Stock must be a whole number.";
    } else {

        $stock = (int) $stock;

        $upd = mysqli_prepare(
            $connect,
            "UPDATE products SET stock_quantity = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($upd, "ii", $stock, $productId);

        if (mysqli_stmt_execute($upd)) {
            $_SESSION["flash_success"] = "Stock updated successfully.";
        } else {
            $_SESSION["flash_error"] = "Stock could not be updated.";
        }
        mysqli_stmt_close($upd);
    }

    header("Location: stock.php");
    exit;
}


/* =========================================================
   FETCH PRODUCTS
   NOTE: products table uses uppercase NAME and STATUS columns
========================================================= */

$products = [];

$sql = "
    SELECT
        p.id,
        p.NAME           AS name,
        p.price,
        p.stock_quantity,
        p.image,
        p.STATUS         AS status,
        c.name           AS category_name
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
";

$res = mysqli_query($connect, $sql);

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $products[] = $row;
}


/* =========================================================
   STOCK SUMMARY
========================================================= */

$totalProducts = count($products);
$inStock       = 0;
$lowStock      = 0;
$outOfStock    = 0;

foreach ($products as $p) {
    $q = (int) $p["stock_quantity"];

    if ($q <= 0)         $outOfStock++;
    elseif ($q <= 5)     $lowStock++;
    else                 $inStock++;
}


/* =========================================================
   PAGE META + HEADER
========================================================= */

$pageTitle      = "Stock";
$pageHeading    = "Stock Management";
$pageSubheading = "Monitor and update product stock quantities";

require_once __DIR__ . "/Includes/header.php";
?>


<!-- FLASH -->

<?php if (!empty($_SESSION["flash_success"])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-circle-check me-1"></i>
        <?php echo e($_SESSION["flash_success"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION["flash_success"]); ?>
<?php endif; ?>

<?php if (!empty($_SESSION["flash_error"])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-circle-exclamation me-1"></i>
        <?php echo e($_SESSION["flash_error"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION["flash_error"]); ?>
<?php endif; ?>


<!-- BACK BUTTON -->

<div class="mb-4">
    <a href="products.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Products
    </a>
</div>


<!-- STATS -->

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-primary">
                <i class="fa-solid fa-box"></i>
            </div>
            <div class="stat-value"><?php echo $totalProducts; ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-value"><?php echo $inStock; ?></div>
            <div class="stat-label">In Stock</div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="stat-value"><?php echo $lowStock; ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-danger">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="stat-value"><?php echo $outOfStock; ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>

</div>


<!-- STOCK TABLE -->

<div class="panel">

    <div class="panel-header">
        <h3>
            <i class="fa-solid fa-warehouse"></i>
            Product Stock
        </h3>
        <span class="badge-soft-primary">
            <?php echo $totalProducts; ?> products
        </span>
    </div>

    <?php if (!empty($products)): ?>

        <div class="table-responsive">

            <table class="admin-table align-middle">

                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                        <th style="width:220px;">Update Stock</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($products as $row): ?>

                        <?php
                        $imageFile = __DIR__ . "/../Assets/Images/product/" . ($row["image"] ?? "");
                        $imageUrl  = "../Assets/Images/product/" . rawurlencode(basename((string) ($row["image"] ?? "")));
                        $qty       = (int) $row["stock_quantity"];
                        ?>

                        <tr>

                            <td>
                                <span style="font-weight:600;color:#475569;">
                                    #<?php echo (int) $row["id"]; ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">

                                    <?php if (!empty($row["image"]) && file_exists($imageFile)): ?>
                                        <img
                                            src="<?php echo e($imageUrl); ?>"
                                            alt="<?php echo e($row["name"]); ?>"
                                            style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;background:#f8fafc;">
                                    <?php else: ?>
                                        <div style="width:48px;height:48px;border-radius:10px;background:#f1f5f9;border:1px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;color:#94a3b8;">
                                            <i class="fa-solid fa-image"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div style="font-weight:600;color:#0f172a;line-height:1.3;">
                                            <?php echo e($row["name"]); ?>
                                        </div>
                                        <small style="color:#94a3b8;font-size:12px;">
                                            Product #<?php echo (int) $row["id"]; ?>
                                        </small>
                                    </div>

                                </div>
                            </td>

                            <td>
                                <span class="badge-soft-primary">
                                    <?php echo e($row["category_name"]); ?>
                                </span>
                            </td>

                            <td>
                                <strong>Rs. <?php echo number_format((float) $row["price"], 2); ?></strong>
                            </td>

                            <td>
                                <?php if ($qty <= 0): ?>
                                    <span class="badge-soft-secondary" style="background:#fee2e2;color:#b91c1c;">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> Out of Stock
                                    </span>
                                <?php elseif ($qty <= 5): ?>
                                    <span class="badge-soft-secondary" style="background:#fef3c7;color:#b45309;">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        <?php echo $qty; ?> Low
                                    </span>
                                <?php else: ?>
                                    <span class="badge-soft-success">
                                        <i class="fa-solid fa-check me-1"></i>
                                        <?php echo $qty; ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($row["status"] === "Active"): ?>
                                    <span class="badge-soft-success">
                                        <i class="fa-solid fa-circle-check me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge-soft-secondary">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <form method="post" class="d-flex gap-2 align-items-center">

                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $row["id"]; ?>">

                                    <input
                                        type="number"
                                        name="stock_quantity"
                                        class="form-control form-control-sm"
                                        style="max-width:110px;"
                                        value="<?php echo $qty; ?>"
                                        min="0"
                                        step="1"
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

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h5>No products found</h5>
            <p class="text-muted mb-3">
                Add products first to manage their stock.
            </p>
            <a href="product-add.php" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> Add Product
            </a>
        </div>

    <?php endif; ?>

</div>


<?php require_once __DIR__ . "/Includes/footer.php"; ?>