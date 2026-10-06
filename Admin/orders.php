<?php

/* =========================================================
   ADMIN - ORDER MANAGEMENT
   Furnishop Admin Panel
========================================================= */

require_once __DIR__ . "/../Includes/auth.php";

require_once __DIR__ . "/../Config/database.php";
$csrf = admin_csrf_token();


/* =========================================================
   FLASH MESSAGES
========================================================= */

$flash_success = $_SESSION["flash_success"] ?? null;
$flash_error   = $_SESSION["flash_error"]   ?? null;

unset($_SESSION["flash_success"], $_SESSION["flash_error"]);


/* =========================================================
   QUICK STATUS UPDATE (inline)
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_status"])
) {
    if (!admin_csrf_valid($_POST["csrf_token"] ?? null)) {
        $_SESSION["flash_error"] = "Invalid security token. Please try again.";
        header("Location: orders.php");
        exit;
    }

    $updateOrderId = (int) ($_POST["order_id"] ?? 0);
    $newStatus     = trim($_POST["status"] ?? "");

    [$ok, $msg] = admin_update_order_status($connect, $updateOrderId, $newStatus);

    $_SESSION[$ok ? "flash_success" : "flash_error"] = $msg;

    header("Location: orders.php");
    exit;
}


/* =========================================================
   FILTERS
========================================================= */

$filterStatus = trim($_GET["status"] ?? "");
$search       = trim($_GET["q"] ?? "");

$where  = [];
$params = [];
$types  = "";

if ($filterStatus !== "" && $filterStatus !== "all") {
    $where[]  = "status = ?";
    $params[] = $filterStatus;
    $types   .= "s";
}

if ($search !== "") {
    $where[]  = "(customer_name LIKE ? OR email LIKE ? OR phone LIKE ? OR id = ?)";
    $like     = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = (int) $search;
    $types   .= "sssi";
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";


/* =========================================================
   FETCH ORDERS
========================================================= */

$orders = [];

$sql = "
    SELECT
        o.id,
        o.user_id,
        o.customer_name,
        o.email,
        o.phone,
        o.address,
        o.city,
        o.country,
        o.subtotal,
        o.shipping,
        o.total,
        o.payment_method,
        o.status,
        o.created_at,
        (
            SELECT COUNT(*)
            FROM order_items oi
            WHERE oi.order_id = o.id
        ) AS item_count
    FROM orders o
    $whereSql
    ORDER BY o.created_at DESC, o.id DESC
";

$stmt = mysqli_prepare($connect, $sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars(mysqli_error($connect)));
}

if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

mysqli_stmt_close($stmt);


/* =========================================================
   STATS
========================================================= */

$totalOrders     = 0;
$pendingCount    = 0;
$deliveredCount  = 0;
$cancelledCount  = 0;
$totalRevenue    = 0.0;

$statsQ = mysqli_query(
    $connect,
    "SELECT status, total FROM orders"
);

if ($statsQ) {
    while ($r = mysqli_fetch_assoc($statsQ)) {
        $totalOrders++;

        $st = strtolower(trim($r["status"] ?? ""));

        if ($st === "pending")                     $pendingCount++;
        if ($st === "delivered" || $st === "completed") $deliveredCount++;
        if ($st === "cancelled" || $st === "canceled")  $cancelledCount++;

        if ($st !== "cancelled" && $st !== "canceled") {
            $totalRevenue += (float) $r["total"];
        }
    }
}


/* =========================================================
   HELPERS
========================================================= */

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

function orderStatusClass($status)
{
    switch (strtolower(trim($status))) {
        case "pending":    return "bg-warning text-dark";
        case "confirmed":  return "bg-success";
        case "processing": return "bg-info text-dark";
        case "shipped":    return "bg-primary";
        case "completed":
        case "delivered":  return "bg-success";
        case "cancelled":
        case "canceled":   return "bg-danger";
        default:           return "bg-secondary";
    }
}

function orderStatusIcon($status)
{
    switch (strtolower(trim($status))) {
        case "pending":    return "fa-solid fa-clock";
        case "confirmed":  return "fa-solid fa-circle-check";
        case "processing": return "fa-solid fa-gears";
        case "shipped":    return "fa-solid fa-truck-fast";
        case "completed":
        case "delivered":  return "fa-solid fa-box-open";
        case "cancelled":
        case "canceled":   return "fa-solid fa-circle-xmark";
        default:           return "fa-solid fa-circle-info";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Orders — Furnishop Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6fb;
            margin: 0;
        }

        /* ============================
           LAYOUT
        ============================ */

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #cbd5e1;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            padding: 24px 0;
            overflow-y: auto;
            z-index: 100;
        }

        .admin-sidebar .brand {
            padding: 0 24px 24px;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 16px;
        }

        .admin-sidebar .brand i {
            color: #818cf8;
            margin-right: 8px;
        }

        .admin-sidebar .nav-link {
            color: #cbd5e1;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .admin-sidebar .nav-link:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.05);
        }

        .admin-sidebar .nav-link.active {
            color: #ffffff;
            background: rgba(129, 140, 248, 0.12);
            border-left-color: #818cf8;
        }

        .admin-sidebar .nav-link i {
            width: 18px;
            text-align: center;
        }

        .admin-sidebar .nav-section {
            padding: 18px 24px 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            font-weight: 600;
        }

        .admin-main {
            margin-left: 260px;
            flex: 1;
            min-width: 0;
        }

        /* ============================
           TOPBAR
        ============================ */

        .admin-topbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .admin-topbar .page-heading {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .admin-topbar .page-heading small {
            display: block;
            color: #6b7280;
            font-size: 13px;
            font-weight: 400;
            margin-top: 2px;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .admin-user .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        /* ============================
           CONTENT
        ============================ */

        .admin-content {
            padding: 28px 32px 60px;
        }

        /* ============================
           STAT CARDS
        ============================ */

        .stat-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e9ecef;
            height: 100%;
            transition: all 0.2s ease;
        }

        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .stat-box .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .stat-box .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
        }

        .stat-box .stat-label {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        .icon-primary { background: #eef2ff; color: #4f46e5; }
        .icon-warning { background: #fef3c7; color: #b45309; }
        .icon-success { background: #d1fae5; color: #047857; }
        .icon-info    { background: #dbeafe; color: #1d4ed8; }

        /* ============================
           FILTER BAR
        ============================ */

        .filter-bar {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            border-radius: 8px;
            font-size: 14px;
        }

        /* ============================
           TABLE
        ============================ */

        .orders-wrap {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            overflow: hidden;
        }

        .orders-table {
            margin-bottom: 0;
        }

        .orders-table thead th {
            background: #f8fafc;
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .orders-table tbody td {
            padding: 16px;
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
        }

        .orders-table tbody tr:last-child td {
            border-bottom: none;
        }

        .orders-table tbody tr:hover {
            background: #fafbff;
        }

        .order-id-link {
            font-weight: 600;
            color: #4f46e5;
            text-decoration: none;
        }

        .order-id-link:hover {
            text-decoration: underline;
        }

        .customer-name {
            font-weight: 500;
        }

        .customer-email {
            font-size: 12.5px;
            color: #6b7280;
            display: block;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .action-btn {
            border-radius: 6px;
            font-size: 13px;
            padding: 6px 12px;
            transition: all 0.15s ease;
        }

        /* Inline status dropdown */
        .status-form {
            display: inline-flex;
            gap: 6px;
            align-items: center;
        }

        .status-form select {
            border-radius: 6px;
            font-size: 13px;
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
            max-width: 130px;
        }

        .status-form button {
            border: none;
            background: #4f46e5;
            color: #fff;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            transition: background 0.15s;
        }

        .status-form button:hover {
            background: #4338ca;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state-icon {
            font-size: 60px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        /* ============================
           RESPONSIVE
        ============================ */

        @media (max-width: 991px) {

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-topbar {
                padding: 14px 20px;
            }

            .admin-content {
                padding: 20px;
            }

            .mobile-toggle {
                display: inline-flex !important;
            }
        }

        .mobile-toggle {
            display: none;
            background: transparent;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 18px;
            color: #0f172a;
        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- =========================================================
         SIDEBAR
    ========================================================= -->

    <aside class="admin-sidebar" id="adminSidebar">

        <div class="brand">
            <i class="fa-solid fa-couch"></i> Furnishop
        </div>

        <ul class="nav flex-column">

            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>

            <li class="nav-section">Catalog</li>

            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fa-solid fa-layer-group"></i> Categories
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fa-solid fa-box"></i> Products
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="stock.php">
                    <i class="fa-solid fa-warehouse"></i> Stock
                </a>
            </li>

            <li class="nav-section">Sales</li>

            <li class="nav-item">
                <a class="nav-link active" href="orders.php">
                    <i class="fa-solid fa-receipt"></i> Orders
                </a>
            </li>

            <li class="nav-section">Users</li>

            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fa-solid fa-users"></i> Customers
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <i class="fa-solid fa-user-gear"></i> My Profile
                </a>
            </li>

            <li class="nav-section">&nbsp;</li>

            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </li>

        </ul>

    </aside>


    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="admin-main">

        <div class="admin-topbar">

            <div class="d-flex align-items-center gap-3">

                <button class="mobile-toggle" id="sidebarToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <h1 class="page-heading">
                    Orders
                    <small>Manage and track all customer orders</small>
                </h1>

            </div>

            <div class="admin-user">

                <div class="avatar">
                    <?php
                    echo strtoupper(
                        substr($_SESSION["admin_name"] ?? "A", 0, 1)
                    );
                    ?>
                </div>

                <div>
                    <div class="fw-semibold" style="font-size:13px;">
                        <?php echo e($_SESSION["admin_name"] ?? "Admin"); ?>
                    </div>
                    <div style="font-size:11.5px;color:#6b7280;">
                        Administrator
                    </div>
                </div>

            </div>

        </div>


        <div class="admin-content">

            <!-- FLASH -->

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


            <!-- STATS -->

            <div class="row g-3 mb-4">

                <div class="col-lg-3 col-sm-6">
                    <div class="stat-box">
                        <div class="stat-icon icon-primary">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div class="stat-value"><?php echo (int) $totalOrders; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="stat-box">
                        <div class="stat-icon icon-warning">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo (int) $pendingCount; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="stat-box">
                        <div class="stat-icon icon-success">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <div class="stat-value"><?php echo (int) $deliveredCount; ?></div>
                        <div class="stat-label">Delivered</div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="stat-box">
                        <div class="stat-icon icon-info">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <div class="stat-value">
                            Rs. <?php echo number_format($totalRevenue, 0); ?>
                        </div>
                        <div class="stat-label">Revenue</div>
                    </div>
                </div>

            </div>


            <!-- FILTER -->

            <form method="get" class="filter-bar">

                <div class="row g-2 align-items-center">

                    <div class="col-md-6">
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Search by name, email, phone, or order ID..."
                            value="<?php echo e($search); ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="all" <?php echo $filterStatus === "" || $filterStatus === "all" ? "selected" : ""; ?>>
                                All Statuses
                            </option>
                            <?php
                            $statusList = [
                                "Pending",
                                "Confirmed",
                                "Processing",
                                "Shipped",
                                "Delivered",
                                "Cancelled",
                            ];
                            foreach ($statusList as $st):
                            ?>
                                <option
                                    value="<?php echo e($st); ?>"
                                    <?php echo $filterStatus === $st ? "selected" : ""; ?>
                                >
                                    <?php echo e($st); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1">
                            <i class="fa-solid fa-filter me-1"></i> Filter
                        </button>
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-rotate"></i>
                        </a>
                    </div>

                </div>

            </form>


            <!-- ORDERS TABLE -->

            <div class="orders-wrap">

                <?php if (!empty($orders)): ?>

                    <div class="table-responsive">

                        <table class="table orders-table align-middle">

                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Change Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($orders as $order): ?>

                                    <tr>

                                        <td>
                                            <a
                                                href="order-details.php?order_id=<?php echo (int) $order["id"]; ?>"
                                                class="order-id-link"
                                            >
                                                #<?php echo (int) $order["id"]; ?>
                                            </a>
                                        </td>

                                        <td>
                                            <span class="customer-name">
                                                <?php echo e($order["customer_name"]); ?>
                                            </span>
                                            <span class="customer-email">
                                                <?php echo e($order["email"]); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php
                                            echo !empty($order["created_at"])
                                                ? e(date("d M Y", strtotime($order["created_at"])))
                                                : "-";
                                            ?>
                                            <div style="font-size:12px;color:#94a3b8;">
                                                <?php
                                                echo !empty($order["created_at"])
                                                    ? e(date("h:i A", strtotime($order["created_at"])))
                                                    : "";
                                                ?>
                                            </div>
                                        </td>

                                        <td>
                                            <span style="font-size:13px;color:#64748b;">
                                                <?php echo (int) $order["item_count"]; ?> item<?php echo (int) $order["item_count"] === 1 ? "" : "s"; ?>
                                            </span>
                                        </td>

                                        <td>
                                            <strong>
                                                Rs. <?php echo number_format((float) $order["total"], 2); ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <span class="badge status-badge <?php echo orderStatusClass($order["status"]); ?>">
                                                <i class="<?php echo orderStatusIcon($order["status"]); ?>"></i>
                                                <?php echo e($order["status"]); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <form method="post" class="status-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="order_id" value="<?php echo (int) $order["id"]; ?>">
                                                <input type="hidden" name="update_status" value="1">

                                                <select name="status">
                                                    <?php foreach ($statusList as $st): ?>
                                                        <option
                                                            value="<?php echo e($st); ?>"
                                                            <?php echo strtolower($order["status"]) === strtolower($st) ? "selected" : ""; ?>
                                                        >
                                                            <?php echo e($st); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button type="submit" title="Update status">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        </td>

                                        <td class="text-center">
                                            <a
                                                href="order-details.php?order_id=<?php echo (int) $order["id"]; ?>"
                                                class="btn btn-outline-primary action-btn"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                                View
                                            </a>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                        <h5>No orders found</h5>
                        <p class="text-muted">
                            <?php if ($search !== "" || $filterStatus !== ""): ?>
                                Try clearing the filters or search term.
                            <?php else: ?>
                                Orders placed by customers will appear here.
                            <?php endif; ?>
                        </p>
                        <?php if ($search !== "" || $filterStatus !== ""): ?>
                            <a href="orders.php" class="btn btn-primary">
                                <i class="fa-solid fa-rotate me-1"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Mobile sidebar toggle
    (function () {
        var toggle = document.getElementById("sidebarToggle");
        var sidebar = document.getElementById("adminSidebar");

        if (toggle && sidebar) {
            toggle.addEventListener("click", function (e) {
                e.stopPropagation();
                sidebar.classList.toggle("open");
            });

            document.addEventListener("click", function (e) {
                if (
                    sidebar.classList.contains("open") &&
                    !sidebar.contains(e.target) &&
                    e.target !== toggle
                ) {
                    sidebar.classList.remove("open");
                }
            });
        }
    })();
</script>

</body>
</html>