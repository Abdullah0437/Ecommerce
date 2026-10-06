<?php

/* =========================================================
   ADMIN - ORDER DETAILS
   Furnishop Admin Panel
========================================================= */

require_once __DIR__ . "/../Includes/auth.php";

require_once __DIR__ . "/../Config/database.php";

$csrf = admin_csrf_token();


/* =========================================================
   GET ORDER ID
========================================================= */

$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (!$orderId || $orderId <= 0) {
    $_SESSION["flash_error"] = "Invalid order ID.";
    header("Location: orders.php");
    exit;
}


/* =========================================================
   HANDLE STATUS UPDATE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {

    if (!admin_csrf_valid($_POST["csrf_token"] ?? null)) {
        $_SESSION["flash_error"] = "Invalid security token. Please try again.";
        header("Location: order-details.php?order_id=" . $orderId);
        exit;
    }

    $newStatus = trim($_POST["status"] ?? "");

    [$ok, $msg] = admin_update_order_status($connect, (int) $orderId, $newStatus);

    $_SESSION[$ok ? "flash_success" : "flash_error"] = $msg;

    header("Location: order-details.php?order_id=" . $orderId);
    exit;
}


/* =========================================================
   FLASH
========================================================= */

$flash_success = $_SESSION["flash_success"] ?? null;
$flash_error   = $_SESSION["flash_error"]   ?? null;

unset($_SESSION["flash_success"], $_SESSION["flash_error"]);


/* =========================================================
   FETCH ORDER
========================================================= */

$order = null;

$sql = "
    SELECT
        id,
        user_id,
        customer_name,
        email,
        phone,
        address,
        city,
        country,
        subtotal,
        shipping,
        total,
        payment_method,
        status,
        created_at
    FROM orders
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($connect, $sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars(mysqli_error($connect)));
}

mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($result) {
    $order = mysqli_fetch_assoc($result);
}

mysqli_stmt_close($stmt);


/* =========================================================
   NOT FOUND
========================================================= */

if (!$order) {
    $_SESSION["flash_error"] = "Order not found.";
    header("Location: orders.php");
    exit;
}


/* =========================================================
   FETCH ORDER ITEMS
========================================================= */

$orderItems = [];

$sql = "
    SELECT
        oi.id,
        oi.product_id,
        oi.product_name,
        oi.price,
        oi.quantity,
        oi.subtotal,
        p.image AS live_image
    FROM order_items AS oi
    LEFT JOIN products AS p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
";

$stmt = mysqli_prepare($connect, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $orderItems[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   HELPERS
========================================================= */

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

function productImage($image)
{
    $fallback = "../Assets/Images/product/1.png";
    $image    = trim((string) $image);

    if ($image === "") return $fallback;
    if (strpos($image, "../Assets/Images/product/") === 0) return $image;

    return "../Assets/Images/product/" . rawurlencode(basename($image));
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


/* =========================================================
   BUILD ADDRESS (single line)
========================================================= */

$addressDisplay = "—";

$rawAddress = trim((string) $order["address"]);

if ($rawAddress !== "") {
    $parts = array_filter(
        array_map("trim", explode(",", $rawAddress)),
        function ($p) { return $p !== ""; }
    );

    if (!empty($parts)) {
        $addressDisplay = e(implode(", ", $parts));
    }
}

$statusList = [
    "Pending",
    "Confirmed",
    "Processing",
    "Shipped",
    "Delivered",
    "Cancelled",
];

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order #<?php echo (int) $order["id"]; ?> — Admin</title>

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

        /* LAYOUT */

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

        .admin-sidebar .nav-link i { width: 18px; text-align: center; }

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

        /* TOPBAR */

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

        /* CONTENT */

        .admin-content {
            padding: 28px 32px 60px;
        }

        /* CARDS */

        .panel {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .panel-header h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-header h3 i {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #eef2ff;
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .panel-body {
            padding: 22px;
        }

        /* INFO ROWS */

        .info-row {
            display: flex;
            border-bottom: 1px solid #f1f5f9;
            padding: 10px 0;
            font-size: 14px;
        }

        .info-row:last-child { border-bottom: none; }

        .info-label {
            width: 140px;
            color: #6b7280;
            font-weight: 500;
            flex-shrink: 0;
        }

        .info-value {
            color: #0f172a;
            word-break: break-word;
        }

        /* STATUS BADGE */

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* STATUS UPDATE FORM */

        .status-update-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-update-form select {
            border-radius: 8px;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            max-width: 200px;
        }

        .status-update-form button {
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            transition: transform 0.15s;
        }

        .status-update-form button:hover {
            transform: translateY(-1px);
        }

        /* TABLE */

        .items-table {
            width: 100%;
            margin-bottom: 0;
        }

        .items-table thead th {
            background: #f8fafc;
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .product-thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            flex-shrink: 0;
        }

        /* SUMMARY */

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-row:last-child { border-bottom: none; }

        .summary-total {
            font-size: 17px;
            font-weight: 700;
            padding-top: 14px;
            color: #0f172a;
        }

        /* RESPONSIVE */

        @media (max-width: 991px) {

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
            }

            .admin-sidebar.open { transform: translateX(0); }

            .admin-main { margin-left: 0; }

            .admin-topbar { padding: 14px 20px; }

            .admin-content { padding: 20px; }

            .mobile-toggle { display: inline-flex !important; }
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

    <!-- SIDEBAR -->

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


    <!-- MAIN -->

    <main class="admin-main">

        <div class="admin-topbar">

            <div class="d-flex align-items-center gap-3">

                <button class="mobile-toggle" id="sidebarToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>
                    <h1 class="page-heading">
                        Order #<?php echo (int) $order["id"]; ?>
                        <small>Order details and status management</small>
                    </h1>
                </div>

            </div>

            <div class="admin-user">

                <div class="avatar">
                    <?php echo strtoupper(substr($_SESSION["admin_name"] ?? "A", 0, 1)); ?>
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


            <!-- BACK + STATUS BAR -->

            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">

                <a href="orders.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders
                </a>

                <div class="d-flex align-items-center gap-3">
                    <span style="font-size:13px;color:#6b7280;">Current status:</span>
                    <span class="badge status-badge <?php echo orderStatusClass($order["status"]); ?>">
                        <i class="<?php echo orderStatusIcon($order["status"]); ?>"></i>
                        <?php echo e($order["status"]); ?>
                    </span>
                </div>

            </div>


            <div class="row g-4">

                <!-- LEFT COLUMN -->

                <div class="col-lg-8">

                    <!-- CUSTOMER INFO -->

                    <div class="panel">

                        <div class="panel-header">
                            <h3>
                                <i class="fa-solid fa-user"></i>
                                Customer Information
                            </h3>
                        </div>

                        <div class="panel-body">

                            <div class="info-row">
                                <div class="info-label">Name</div>
                                <div class="info-value"><?php echo e($order["customer_name"]); ?></div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo e($order["email"]); ?></div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo e($order["phone"]); ?></div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?php echo $addressDisplay; ?></div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Country</div>
                                <div class="info-value"><?php echo e($order["country"]); ?></div>
                            </div>

                        </div>

                    </div>


                    <!-- ORDER ITEMS -->

                    <div class="panel">

                        <div class="panel-header">
                            <h3>
                                <i class="fa-solid fa-box"></i>
                                Ordered Products (<?php echo count($orderItems); ?>)
                            </h3>
                        </div>

                        <?php if (!empty($orderItems)): ?>

                            <div class="table-responsive">

                                <table class="table items-table align-middle">

                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($orderItems as $item): ?>

                                            <?php
                                            $imgSrc   = productImage($item["live_image"] ?? "");
                                            $fallback = "../Assets/Images/product/1.png";
                                            ?>

                                            <tr>

                                                <td>
                                                    <div class="d-flex align-items-center gap-3">

                                                        <img
                                                            src="<?php echo e($imgSrc); ?>"
                                                            alt="<?php echo e($item["product_name"]); ?>"
                                                            class="product-thumb"
                                                            onerror="this.onerror=null;this.src='<?php echo e($fallback); ?>';">

                                                        <span>
                                                            <?php echo e($item["product_name"]); ?>
                                                        </span>

                                                    </div>
                                                </td>

                                                <td>Rs. <?php echo number_format((float) $item["price"], 2); ?></td>

                                                <td><?php echo (int) $item["quantity"]; ?></td>

                                                <td>
                                                    <strong>
                                                        Rs. <?php echo number_format((float) $item["subtotal"], 2); ?>
                                                    </strong>
                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div class="panel-body text-center text-muted">
                                No items found for this order.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- RIGHT COLUMN -->

                <div class="col-lg-4">

                    <!-- ORDER INFO -->

                    <div class="panel">

                        <div class="panel-header">
                            <h3>
                                <i class="fa-solid fa-receipt"></i>
                                Order Info
                            </h3>
                        </div>

                        <div class="panel-body">

                            <div class="info-row">
                                <div class="info-label">Order ID</div>
                                <div class="info-value">#<?php echo (int) $order["id"]; ?></div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Date</div>
                                <div class="info-value">
                                    <?php
                                    echo !empty($order["created_at"])
                                        ? e(date("d M Y, h:i A", strtotime($order["created_at"])))
                                        : "-";
                                    ?>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Payment</div>
                                <div class="info-value"><?php echo e($order["payment_method"]); ?></div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="badge status-badge <?php echo orderStatusClass($order["status"]); ?>">
                                        <i class="<?php echo orderStatusIcon($order["status"]); ?>"></i>
                                        <?php echo e($order["status"]); ?>
                                    </span>
                                </div>
                            </div>

                        </div>

                    </div>


                    <!-- UPDATE STATUS -->

                    <div class="panel">

                        <div class="panel-header">
                            <h3>
                                <i class="fa-solid fa-pen-to-square"></i>
                                Update Status
                            </h3>
                        </div>

                        <div class="panel-body">

                            <form method="post" class="status-update-form">

                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="update_status" value="1">

                                <select name="status" required>

                                    <?php foreach ($statusList as $st): ?>

                                        <option
                                            value="<?php echo e($st); ?>"
                                            <?php echo strtolower($order["status"]) === strtolower($st) ? "selected" : ""; ?>
                                        >
                                            <?php echo e($st); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button type="submit">
                                    <i class="fa-solid fa-check me-1"></i> Save
                                </button>

                            </form>

                        </div>

                    </div>


                    <!-- SUMMARY -->

                    <div class="panel">

                        <div class="panel-header">
                            <h3>
                                <i class="fa-solid fa-calculator"></i>
                                Order Summary
                            </h3>
                        </div>

                        <div class="panel-body">

                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>Rs. <?php echo number_format((float) $order["subtotal"], 2); ?></span>
                            </div>

                            <div class="summary-row">
                                <span>Shipping</span>
                                <span>Rs. <?php echo number_format((float) $order["shipping"], 2); ?></span>
                            </div>

                            <div class="summary-row summary-total">
                                <span>Total</span>
                                <span>Rs. <?php echo number_format((float) $order["total"], 2); ?></span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
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