<?php

/* =========================================================
   ADMIN - PRODUCT MANAGEMENT
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

function checkCsrf() {
    if (
        empty($_POST["csrf_token"]) ||
        empty($_SESSION["admin_csrf"]) ||
        !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"])
    ) {
        $_SESSION["flash_error"] = "Invalid form submission.";
        header("Location: products.php");
        exit;
    }
}


/* =========================================================
   HELPERS
========================================================= */

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

function productImage($image)
{
    if (empty($image)) return null;

    $filePath = __DIR__ . "/../Assets/Images/product/" . $image;

    if (file_exists($filePath)) {
        return "../Assets/Images/product/" . rawurlencode(basename($image));
    }

    return null;
}


/* =========================================================
   HANDLE: TOGGLE STATUS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["toggle_status"])
) {

    checkCsrf();

    $productId = (int) $_POST["toggle_status"];

    if ($productId > 0) {

        $stmt = mysqli_prepare(
            $connect,
            "SELECT status FROM products WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $product = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if ($product) {

            $newStatus = $product["status"] === "Active" ? "Inactive" : "Active";

            $upd = mysqli_prepare(
                $connect,
                "UPDATE products SET status = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($upd, "si", $newStatus, $productId);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $_SESSION["flash_success"] =
                "Product status updated to " . $newStatus . ".";
        }
    }

    /* Preserve filters when redirecting */
    $redirect = "products.php";
    $qs = [];

    if (!empty($_POST["preserve_search"])) $qs["q"]      = $_POST["preserve_search"];
    if (!empty($_POST["preserve_cat"]))    $qs["cat"]    = $_POST["preserve_cat"];
    if (!empty($_POST["preserve_status"])) $qs["status"] = $_POST["preserve_status"];

    if ($qs) $redirect .= "?" . http_build_query($qs);

    header("Location: " . $redirect);
    exit;
}


/* =========================================================
   FILTERS
========================================================= */

$search       = trim($_GET["q"]      ?? "");
$filterCat    = (int) ($_GET["cat"]  ?? 0);
$filterStatus = trim($_GET["status"] ?? "all");


/* =========================================================
   STATS
========================================================= */

$stats = [
    "total"       => (int) mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS c FROM products"))["c"],
    "active"      => (int) mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS c FROM products WHERE status = 'Active'"))["c"],
    "inactive"    => (int) mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS c FROM products WHERE status = 'Inactive'"))["c"],
    "out_of_stock"=> (int) mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS c FROM products WHERE stock_quantity <= 0"))["c"],
];


/* =========================================================
   CATEGORIES FOR FILTER
========================================================= */

$categories = [];

$cres = mysqli_query($connect, "SELECT id, name FROM categories ORDER BY name ASC");
if ($cres) {
    while ($c = mysqli_fetch_assoc($cres)) $categories[] = $c;
}


/* =========================================================
   FETCH PRODUCTS
========================================================= */

$products = [];

$where  = [];
$params = [];
$types  = "";

if ($search !== "") {
    $where[]  = "(products.name LIKE ? OR categories.name LIKE ?)";
    $like     = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}

if ($filterCat > 0) {
    $where[]  = "products.category_id = ?";
    $params[] = $filterCat;
    $types   .= "i";
}

if ($filterStatus === "Active" || $filterStatus === "Inactive") {
    $where[]  = "products.status = ?";
    $params[] = $filterStatus;
    $types   .= "s";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        products.id,
        products.name,
        products.price,
        products.stock_quantity,
        products.image,
        products.status,
        categories.name AS category_name
    FROM products
    INNER JOIN categories ON products.category_id = categories.id
    $whereSql
    ORDER BY products.id DESC
";

$stmt = mysqli_prepare($connect, $sql);

if ($stmt) {

    if ($types !== "") {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $products[] = $row;
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   PAGE META + HEADER
========================================================= */

$pageTitle      = "Products";
$pageHeading    = "Product Management";
$pageSubheading = "Manage products, prices, stock and availability";

require_once __DIR__ . "/Includes/header.php";
?>


<!-- =========================================================
     FLASH
========================================================= -->

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


<!-- =========================================================
     STATS
========================================================= -->

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-primary">
                <i class="fa-solid fa-box"></i>
            </div>
            <div class="stat-value"><?php echo $stats["total"]; ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-value"><?php echo $stats["active"]; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-danger">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="stat-value"><?php echo $stats["inactive"]; ?></div>
            <div class="stat-label">Inactive</div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="stat-value"><?php echo $stats["out_of_stock"]; ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>

</div>


<!-- =========================================================
     ACTION BAR
========================================================= -->

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">

    <a href="product-add.php" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Add New Product
    </a>

    <div class="d-flex gap-2">
        <a href="categories.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-layer-group me-1"></i> Categories
        </a>
        <a href="stock.php" class="btn btn-outline-warning">
            <i class="fa-solid fa-warehouse me-1"></i> Stock
        </a>
    </div>

</div>


<!-- =========================================================
     FILTER BAR
========================================================= -->

<form method="get" class="filter-bar">

    <div class="row g-2 align-items-center">

        <div class="col-md-5">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search by product or category name..."
                value="<?php echo e($search); ?>">
        </div>

        <div class="col-md-3">
            <select name="cat" class="form-select">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option
                        value="<?php echo (int) $c["id"]; ?>"
                        <?php echo $filterCat === (int) $c["id"] ? "selected" : ""; ?>>
                        <?php echo e($c["name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="all"       <?php echo $filterStatus === "all"       ? "selected" : ""; ?>>All Statuses</option>
                <option value="Active"    <?php echo $filterStatus === "Active"    ? "selected" : ""; ?>>Active</option>
                <option value="Inactive"  <?php echo $filterStatus === "Inactive"  ? "selected" : ""; ?>>Inactive</option>
            </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">
                <i class="fa-solid fa-filter me-1"></i> Filter
            </button>
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-rotate"></i>
            </a>
        </div>

    </div>

</form>


<!-- =========================================================
     PRODUCTS TABLE
========================================================= -->

<div class="panel">

    <div class="panel-header">
        <h3>
            <i class="fa-solid fa-list"></i>
            All Products
        </h3>
        <span class="badge-soft-primary">
            <?php echo count($products); ?> shown
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
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="text-center" style="width:150px;">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($products as $row): ?>

                        <?php $imgSrc = productImage($row["image"] ?? ""); ?>

                        <tr>

                            <td>
                                <span style="font-weight:600;color:#475569;">
                                    #<?php echo (int) $row["id"]; ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">

                                    <?php if ($imgSrc): ?>
                                        <img
                                            src="<?php echo e($imgSrc); ?>"
                                            alt="<?php echo e($row["name"]); ?>"
                                            style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;background:#f8fafc;"
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                        <div style="width:48px;height:48px;border-radius:10px;background:#f1f5f9;border:1px dashed #cbd5e1;display:none;align-items:center;justify-content:center;color:#94a3b8;">
                                            <i class="fa-solid fa-image"></i>
                                        </div>
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
                                <strong>
                                    Rs. <?php echo number_format((float) $row["price"], 2); ?>
                                </strong>
                            </td>

                            <td>
                                <?php if ((int) $row["stock_quantity"] <= 0): ?>
                                    <span class="badge-soft-secondary" style="background:#fee2e2;color:#b91c1c;">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> Out of Stock
                                    </span>
                                <?php elseif ((int) $row["stock_quantity"] <= 5): ?>
                                    <span class="badge-soft-secondary" style="background:#fef3c7;color:#b45309;">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        <?php echo (int) $row["stock_quantity"]; ?> Low
                                    </span>
                                <?php else: ?>
                                    <span class="badge-soft-success">
                                        <i class="fa-solid fa-check me-1"></i>
                                        <?php echo (int) $row["stock_quantity"]; ?>
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

                            <td class="text-center">

                                <div class="d-inline-flex gap-2">

                                    <!-- Edit -->
                                    <a
                                        href="product-edit.php?id=<?php echo (int) $row["id"]; ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>

                                    <!-- Toggle status -->
                                    <form method="post" class="d-inline" onsubmit="return confirm('<?php echo $row["status"] === "Active" ? "Deactivate" : "Activate"; ?> this product?');">

                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                        <input type="hidden" name="toggle_status" value="<?php echo (int) $row["id"]; ?>">
                                        <input type="hidden" name="preserve_search" value="<?php echo e($search); ?>">
                                        <input type="hidden" name="preserve_cat"    value="<?php echo (int) $filterCat; ?>">
                                        <input type="hidden" name="preserve_status" value="<?php echo e($filterStatus); ?>">

                                        <?php if ($row["status"] === "Active"): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        <?php endif; ?>

                                    </form>

                                </div>

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
                <?php if ($search !== "" || $filterCat > 0 || $filterStatus !== "all"): ?>
                    Try clearing the filters or search term.
                <?php else: ?>
                    Add your first product using the button above.
                <?php endif; ?>
            </p>
            <?php if ($search !== "" || $filterCat > 0 || $filterStatus !== "all"): ?>
                <a href="products.php" class="btn btn-primary">
                    <i class="fa-solid fa-rotate me-1"></i> Clear Filters
                </a>
            <?php else: ?>
                <a href="product-add.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i> Add First Product
                </a>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>


<?php require_once __DIR__ . "/Includes/footer.php"; ?>