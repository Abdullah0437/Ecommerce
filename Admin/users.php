<?php

/* =========================================================
   ADMIN - USER MANAGEMENT
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

function checkCsrf() {
    if (
        empty($_POST["csrf_token"]) ||
        empty($_SESSION["admin_csrf"]) ||
        !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"])
    ) {
        $_SESSION["flash_error"] = "Invalid form submission.";
        header("Location: users.php");
        exit;
    }
}


/* =========================================================
   HANDLE: TOGGLE USER STATUS
   NOTE: users.STATUS column is uppercase in this DB
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["toggle_status"])
) {
    checkCsrf();

    $userId = (int) $_POST["toggle_status"];

    if ($userId > 0) {

        /* Alias STATUS → status so the PHP key is lowercase */
        $stmt = mysqli_prepare(
            $connect,
            "SELECT STATUS AS status FROM users WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if ($user) {

            $newStatus = (strtolower($user["status"]) === "active")
                ? "Inactive"
                : "Active";

            $upd = mysqli_prepare(
                $connect,
                "UPDATE users SET STATUS = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($upd, "si", $newStatus, $userId);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $_SESSION["flash_success"] = "User status updated to " . $newStatus . ".";
        }
    }

    /* Preserve filters */
    $redirect = "users.php";
    $qs = [];
    if (!empty($_POST["preserve_search"])) $qs["q"]      = $_POST["preserve_search"];
    if (!empty($_POST["preserve_status"])) $qs["status"] = $_POST["preserve_status"];

    if ($qs) $redirect .= "?" . http_build_query($qs);

    header("Location: " . $redirect);
    exit;
}


/* =========================================================
   FILTERS
========================================================= */

$search       = trim($_GET["q"] ?? "");
$filterStatus = trim($_GET["status"] ?? "all");


/* =========================================================
   STATS
   MySQL column names are case-insensitive, so `status` works
========================================================= */

$stats = [
    "total"    => (int) mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS c FROM users"))["c"],
    "active"   => (int) mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS c FROM users WHERE STATUS = 'Active'"))["c"],
    "inactive" => (int) mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS c FROM users WHERE STATUS = 'Inactive'"))["c"],
];

$stats["with_orders"] = (int) mysqli_fetch_assoc(
    mysqli_query($connect, "SELECT COUNT(DISTINCT user_id) AS c FROM orders")
)["c"];


/* =========================================================
   FETCH USERS
   NOTE: alias NAME → name and STATUS → status so PHP keys are lowercase
========================================================= */

$users = [];

$where  = [];
$params = [];
$types  = "";

if ($search !== "") {
    $where[]  = "(NAME LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like     = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= "sss";
}

if ($filterStatus === "Active" || $filterStatus === "Inactive") {
    $where[]  = "STATUS = ?";
    $params[] = $filterStatus;
    $types   .= "s";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        u.id,
        u.NAME         AS name,
        u.email,
        u.phone,
        u.city,
        u.country,
        u.STATUS       AS status,
        u.created_at,
        (
            SELECT COUNT(*)
            FROM orders o
            WHERE o.user_id = u.id
        ) AS order_count,
        (
            SELECT COALESCE(SUM(total), 0)
            FROM orders o
            WHERE o.user_id = u.id
              AND o.status != 'Cancelled'
        ) AS total_spent
    FROM users u
    $whereSql
    ORDER BY u.id DESC
";

$stmt = mysqli_prepare($connect, $sql);

if ($stmt) {
    if ($types !== "") {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $users[] = $row;
    }
    mysqli_stmt_close($stmt);
}


/* =========================================================
   PAGE META + HEADER
========================================================= */

$pageTitle      = "Users";
$pageHeading    = "Customer Management";
$pageSubheading = "View and manage registered customers";

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


<!-- STATS -->

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-sm-6">
        <div class="stat-box">
            <div class="stat-icon icon-primary">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-value"><?php echo $stats["total"]; ?></div>
            <div class="stat-label">Total Customers</div>
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
            <div class="stat-icon icon-info">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div class="stat-value"><?php echo $stats["with_orders"]; ?></div>
            <div class="stat-label">With Orders</div>
        </div>
    </div>

</div>


<!-- FILTER BAR -->

<form method="get" class="filter-bar">

    <div class="row g-2 align-items-center">

        <div class="col-md-6">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search by name, email, or phone..."
                value="<?php echo e($search); ?>">
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="all"       <?php echo $filterStatus === "all"       ? "selected" : ""; ?>>All Statuses</option>
                <option value="Active"    <?php echo $filterStatus === "Active"    ? "selected" : ""; ?>>Active</option>
                <option value="Inactive"  <?php echo $filterStatus === "Inactive"  ? "selected" : ""; ?>>Inactive</option>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">
                <i class="fa-solid fa-filter me-1"></i> Filter
            </button>
            <a href="users.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-rotate"></i>
            </a>
        </div>

    </div>

</form>


<!-- USERS TABLE -->

<div class="panel">

    <div class="panel-header">
        <h3>
            <i class="fa-solid fa-users"></i>
            All Customers
        </h3>
        <span class="badge-soft-primary">
            <?php echo count($users); ?> shown
        </span>
    </div>

    <?php if (!empty($users)): ?>

        <div class="table-responsive">

            <table class="admin-table align-middle">

                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-center" style="width:120px;">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($users as $u): ?>

                        <?php $initial = strtoupper(substr($u["name"] ?? "U", 0, 1)); ?>

                        <tr>

                            <td>
                                <span style="font-weight:600;color:#475569;">
                                    #<?php echo (int) $u["id"]; ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">

                                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px;flex-shrink:0;">
                                        <?php echo e($initial); ?>
                                    </div>

                                    <div>
                                        <div style="font-weight:600;color:#0f172a;line-height:1.3;">
                                            <?php echo e($u["name"]); ?>
                                        </div>
                                        <small style="color:#94a3b8;font-size:12px;">
                                            Customer #<?php echo (int) $u["id"]; ?>
                                        </small>
                                    </div>

                                </div>
                            </td>

                            <td>
                                <div style="font-size:13px;color:#334155;">
                                    <?php echo e($u["email"]); ?>
                                </div>
                                <div style="font-size:12px;color:#94a3b8;">
                                    <?php echo e($u["phone"]); ?>
                                </div>
                            </td>

                            <td style="font-size:13px;color:#6b7280;">
                                <?php
                                $loc = trim(($u["city"] ?? "") . (($u["city"] && $u["country"]) ? ", " : "") . ($u["country"] ?? ""));
                                echo e($loc !== "" ? $loc : "-");
                                ?>
                            </td>

                            <td>
                                <span class="badge-soft-primary">
                                    <?php echo (int) $u["order_count"]; ?>
                                </span>
                            </td>

                            <td>
                                <strong>
                                    Rs. <?php echo number_format((float) $u["total_spent"], 2); ?>
                                </strong>
                            </td>

                            <td>
                                <?php if (strtolower($u["status"]) === "active"): ?>
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
                                echo !empty($u["created_at"])
                                    ? e(date("d M Y", strtotime($u["created_at"])))
                                    : "-";
                                ?>
                            </td>

                            <td class="text-center">

                                <div class="d-inline-flex gap-2">

                                    <!-- View Orders -->
                                    <a
                                        href="orders.php?q=<?php echo urlencode($u["email"]); ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="View Orders">
                                        <i class="fa-solid fa-receipt"></i>
                                    </a>

                                    <!-- Toggle -->
                                    <form method="post" class="d-inline" onsubmit="return confirm('<?php echo strtolower($u["status"]) === "active" ? "Deactivate" : "Activate"; ?> this user?');">

                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                        <input type="hidden" name="toggle_status" value="<?php echo (int) $u["id"]; ?>">
                                        <input type="hidden" name="preserve_search" value="<?php echo e($search); ?>">
                                        <input type="hidden" name="preserve_status" value="<?php echo e($filterStatus); ?>">

                                        <?php if (strtolower($u["status"]) === "active"): ?>
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
                <i class="fa-solid fa-users"></i>
            </div>
            <h5>No customers found</h5>
            <p class="text-muted mb-3">
                <?php if ($search !== "" || $filterStatus !== "all"): ?>
                    Try clearing the filters or search term.
                <?php else: ?>
                    No users have registered yet.
                <?php endif; ?>
            </p>
            <?php if ($search !== "" || $filterStatus !== "all"): ?>
                <a href="users.php" class="btn btn-primary">
                    <i class="fa-solid fa-rotate me-1"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>


<?php require_once __DIR__ . "/Includes/footer.php"; ?>