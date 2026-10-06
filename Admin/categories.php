<?php

/* =========================================================
   ADMIN - CATEGORY MANAGEMENT
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
   FLASH
========================================================= */

$flash_success = $_SESSION["flash_success"] ?? null;
$flash_error   = $_SESSION["flash_error"]   ?? null;

unset($_SESSION["flash_success"], $_SESSION["flash_error"]);


/* =========================================================
   HELPERS
========================================================= */

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

function normaliseStatus($status) {
    return ($status === "Active") ? "Active" : "Inactive";
}

function checkCsrf() {
    if (
        empty($_POST["csrf_token"]) ||
        empty($_SESSION["admin_csrf"]) ||
        !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"])
    ) {
        $_SESSION["flash_error"] = "Invalid form submission. Please try again.";
        header("Location: categories.php");
        exit;
    }
}


/* =========================================================
   HANDLE: ADD CATEGORY
========================================================= */

if (isset($_POST["add_category"])) {

    checkCsrf();

    $name        = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $status      = normaliseStatus($_POST["status"] ?? "");

    if ($name === "") {
        $_SESSION["flash_error"] = "Category name is required.";
    } else {

        $check = mysqli_prepare(
            $connect,
            "SELECT id FROM categories WHERE name = ?"
        );
        mysqli_stmt_bind_param($check, "s", $name);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $_SESSION["flash_error"] = "Category name already exists.";

        } else {

            $stmt = mysqli_prepare(
                $connect,
                "INSERT INTO categories (name, description, status) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "sss", $name, $description, $status);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["flash_success"] = "Category added successfully.";
            } else {
                $_SESSION["flash_error"] = "Failed to add category.";
            }
            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }

    header("Location: categories.php");
    exit;
}


/* =========================================================
   HANDLE: UPDATE CATEGORY
========================================================= */

if (isset($_POST["update_category"])) {

    checkCsrf();

    $id          = (int) ($_POST["id"] ?? 0);
    $name        = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $status      = normaliseStatus($_POST["status"] ?? "");

    if ($id <= 0 || $name === "") {
        $_SESSION["flash_error"] = "Category name is required.";
        header("Location: categories.php");
        exit;
    }

    $check = mysqli_prepare(
        $connect,
        "SELECT id FROM categories WHERE name = ? AND id != ?"
    );
    mysqli_stmt_bind_param($check, "si", $name, $id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {

        $_SESSION["flash_error"] = "Another category with this name already exists.";

    } else {

        $stmt = mysqli_prepare(
            $connect,
            "UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $status, $id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION["flash_success"] = "Category updated successfully.";
        } else {
            $_SESSION["flash_error"] = "Failed to update category.";
        }
        mysqli_stmt_close($stmt);
    }

    mysqli_stmt_close($check);

    header("Location: categories.php");
    exit;
}


/* =========================================================
   HANDLE: DEACTIVATE / ACTIVATE (POST for safety)
========================================================= */

if (isset($_POST["deactivate_category"])) {

    checkCsrf();

    $id = (int) ($_POST["id"] ?? 0);

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $connect,
            "UPDATE categories SET status = 'Inactive' WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION["flash_success"] = "Category deactivated successfully.";
        } else {
            $_SESSION["flash_error"] = "Failed to deactivate category.";
        }
        mysqli_stmt_close($stmt);
    }

    header("Location: categories.php");
    exit;
}

if (isset($_POST["activate_category"])) {

    checkCsrf();

    $id = (int) ($_POST["id"] ?? 0);

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $connect,
            "UPDATE categories SET status = 'Active' WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION["flash_success"] = "Category activated successfully.";
        } else {
            $_SESSION["flash_error"] = "Failed to activate category.";
        }
        mysqli_stmt_close($stmt);
    }

    header("Location: categories.php");
    exit;
}


/* =========================================================
   LOAD EDIT CATEGORY (if requested)
========================================================= */

$editCategory = null;

if (isset($_GET["edit"])) {

    $id = (int) $_GET["edit"];

    if ($id > 0) {
        $stmt = mysqli_prepare($connect, "SELECT * FROM categories WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $editCategory = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }
}


/* =========================================================
   STATS + FILTERS
========================================================= */

$filterStatus = trim($_GET["status"] ?? "all");
$search       = trim($_GET["q"] ?? "");

$statsQ = mysqli_query($connect, "SELECT status FROM categories");
$totalCategories = 0;
$activeCount     = 0;
$inactiveCount   = 0;

if ($statsQ) {
    while ($r = mysqli_fetch_assoc($statsQ)) {
        $totalCategories++;
        if ($r["status"] === "Active") $activeCount++;
        else $inactiveCount++;
    }
}

$where = []; $params = []; $types = "";

if ($filterStatus === "Active" || $filterStatus === "Inactive") {
    $where[]  = "status = ?";
    $params[] = $filterStatus;
    $types   .= "s";
}

if ($search !== "") {
    $where[]  = "(name LIKE ? OR description LIKE ?)";
    $like     = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$categories = [];
$sql = "SELECT * FROM categories $whereSql ORDER BY id ASC";

$stmt = mysqli_prepare($connect, $sql);
if ($stmt) {
    if ($types !== "") {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $categories[] = $row;
    }
    mysqli_stmt_close($stmt);
}


/* =========================================================
   PAGE VARIABLES + HEADER
========================================================= */

$pageTitle      = "Categories";
$pageHeading    = "Category Management";
$pageSubheading = "Add, edit and manage product categories";

require_once __DIR__ . "/Includes/header.php";
?>


<!-- =========================================================
     FLASH MESSAGES
========================================================= -->

<?php if ($flash_success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-circle-check me-1"></i>
        <?php echo e($flash_success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($flash_error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-circle-exclamation me-1"></i>
        <?php echo e($flash_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>


<!-- =========================================================
     STATS
========================================================= -->

<div class="row g-3 mb-4">

    <div class="col-lg-4 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-primary">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="stat-value"><?php echo (int) $totalCategories; ?></div>
            <div class="stat-label">Total Categories</div>
        </div>
    </div>

    <div class="col-lg-4 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-value"><?php echo (int) $activeCount; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>

    <div class="col-lg-4 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-danger">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="stat-value"><?php echo (int) $inactiveCount; ?></div>
            <div class="stat-label">Inactive</div>
        </div>
    </div>

</div>


<!-- =========================================================
     ADD / EDIT FORM
========================================================= -->

<div class="panel">

    <div class="panel-header">
        <h3>
            <i class="fa-solid <?php echo $editCategory ? 'fa-pen' : 'fa-plus'; ?>"></i>
            <?php echo $editCategory ? "Edit Category" : "Add New Category"; ?>
        </h3>

        <?php if ($editCategory): ?>
            <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-xmark me-1"></i> Cancel Edit
            </a>
        <?php endif; ?>
    </div>

    <div class="panel-body">

        <form method="post">

            <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">

            <?php if ($editCategory): ?>
                <input type="hidden" name="id" value="<?php echo (int) $editCategory["id"]; ?>">
            <?php endif; ?>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Category Name *</label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="e.g. Sofas, Beds, Chairs"
                        value="<?php echo $editCategory ? e($editCategory["name"]) : ""; ?>"
                        required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?php echo ($editCategory && $editCategory["status"] === "Active") ? "selected" : ""; ?>>
                            Active
                        </option>
                        <option value="Inactive" <?php echo ($editCategory && $editCategory["status"] === "Inactive") ? "selected" : ""; ?>>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">Description</label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="3"
                        placeholder="Short description of this category..."><?php echo $editCategory ? e($editCategory["description"]) : ""; ?></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <?php if ($editCategory): ?>
                        <button type="submit" name="update_category" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Update Category
                        </button>
                    <?php else: ?>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fa-solid fa-plus me-1"></i> Add Category
                        </button>
                    <?php endif; ?>
                </div>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     FILTER BAR
========================================================= -->

<form method="get" class="filter-bar">

    <div class="row g-2 align-items-center">

        <div class="col-md-6">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search by name or description..."
                value="<?php echo e($search); ?>">
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="all" <?php echo $filterStatus === "all" || $filterStatus === "" ? "selected" : ""; ?>>All Statuses</option>
                <option value="Active" <?php echo $filterStatus === "Active" ? "selected" : ""; ?>>Active</option>
                <option value="Inactive" <?php echo $filterStatus === "Inactive" ? "selected" : ""; ?>>Inactive</option>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">
                <i class="fa-solid fa-filter me-1"></i> Filter
            </button>
            <a href="categories.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-rotate"></i>
            </a>
        </div>

    </div>

</form>


<!-- =========================================================
     CATEGORIES TABLE
========================================================= -->

<div class="panel">

    <div class="panel-header">
        <h3>
            <i class="fa-solid fa-list"></i>
            All Categories
        </h3>
        <span class="badge-soft-primary">
            <?php echo count($categories); ?> shown
        </span>
    </div>

    <?php if (!empty($categories)): ?>

        <div class="table-responsive">

            <table class="admin-table align-middle">

                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-center" style="width:180px;">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($categories as $c): ?>

                        <tr>

                            <td>#<?php echo (int) $c["id"]; ?></td>

                            <td>
                                <strong><?php echo e($c["name"]); ?></strong>
                            </td>

                            <td style="color:#6b7280;">
                                <?php echo e($c["description"]); ?>
                            </td>

                            <td>
                                <?php if ($c["status"] === "Active"): ?>
                                    <span class="badge-soft-success">
                                        <i class="fa-solid fa-circle-check me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge-soft-secondary">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td style="color:#6b7280;font-size:13px;">
                                <?php
                                echo !empty($c["created_at"])
                                    ? e(date("d M Y", strtotime($c["created_at"])))
                                    : "-";
                                ?>
                            </td>

                            <td class="text-center">

                                <div class="d-inline-flex gap-2">

                                    <!-- Edit -->
                                    <a
                                        href="?edit=<?php echo (int) $c["id"]; ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>

                                    <!-- Activate / Deactivate -->
                                    <?php if ($c["status"] === "Active"): ?>

                                        <form method="post" class="d-inline" onsubmit="return confirm('Deactivate this category?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $c["id"]; ?>">
                                            <button type="submit" name="deactivate_category" class="btn btn-sm btn-outline-danger" title="Deactivate">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $c["id"]; ?>">
                                            <button type="submit" name="activate_category" class="btn btn-sm btn-outline-success" title="Activate">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>

                                    <?php endif; ?>

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
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h5>No categories found</h5>
            <p class="text-muted mb-3">
                <?php if ($search !== "" || $filterStatus !== "all"): ?>
                    Try clearing the filters or search term.
                <?php else: ?>
                    Add your first category using the form above.
                <?php endif; ?>
            </p>
            <?php if ($search !== "" || $filterStatus !== "all"): ?>
                <a href="categories.php" class="btn btn-primary">
                    <i class="fa-solid fa-rotate me-1"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>


<?php require_once __DIR__ . "/Includes/footer.php"; ?>