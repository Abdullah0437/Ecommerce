<?php

/* =========================================================
   CUSTOMER - MY PROFILE
   Furnishop
========================================================= */

session_start();

require_once __DIR__ . "/../Config/database.php";


/* =========================================================
   LOGIN CHECK
========================================================= */

require_once __DIR__ . "/../Includes/functions.php";
require_customer_login($connect, "profile.php");

$userId = (int) $_SESSION["user_id"];


/* =========================================================
   CSRF
========================================================= */

if (empty($_SESSION["profile_csrf"])) {
    $_SESSION["profile_csrf"] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION["profile_csrf"];


/* =========================================================
   HELPERS
========================================================= */

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}


/* =========================================================
   LOAD USER
========================================================= */

$stmt = mysqli_prepare(
    $connect,
    "SELECT id, NAME AS name, email, phone, address, city, country,
            STATUS AS status, created_at
     FROM users
     WHERE id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$user) {
    $_SESSION["flash_error"] = "Account not found.";
    header("Location: logout.php");
    exit;
}


/* =========================================================
   VARIABLES
========================================================= */

$profileErrors  = ["name" => "", "email" => "", "phone" => "", "address" => "", "city" => "", "country" => ""];
$passwordErrors = ["current_password" => "", "new_password" => "", "confirm_password" => ""];

$name    = $user["name"];
$email   = $user["email"];
$phone   = $user["phone"];
$address = $user["address"];
$city    = $user["city"];
$country = $user["country"];

$activeTab = "profile";


/* =========================================================
   UPDATE PROFILE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {

    $activeTab = "profile";

    if (
        empty($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["profile_csrf"], $_POST["csrf_token"])
    ) {
        $profileErrors["name"] = "Invalid session. Please refresh.";
    }

    $name    = trim($_POST["name"]    ?? "");
    $email   = trim($_POST["email"]   ?? "");
    $phone   = trim($_POST["phone"]   ?? "");
    $address = trim($_POST["address"] ?? "");
    $city    = trim($_POST["city"]    ?? "");
    $country = trim($_POST["country"] ?? "");

    if ($name === "") {
        $profileErrors["name"] = "Full name is required.";
    }

    if ($email === "") {
        $profileErrors["email"] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileErrors["email"] = "Please enter a valid email.";
    } else {
        $chk = mysqli_prepare(
            $connect,
            "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1"
        );
        mysqli_stmt_bind_param($chk, "si", $email, $userId);
        mysqli_stmt_execute($chk);
        $chkRes = mysqli_stmt_get_result($chk);

        if ($chkRes && mysqli_num_rows($chkRes) > 0) {
            $profileErrors["email"] = "This email is already registered.";
        }
        mysqli_stmt_close($chk);
    }

    if ($phone   === "") $profileErrors["phone"]   = "Phone is required.";
    if ($address === "") $profileErrors["address"] = "Address is required.";
    if ($city    === "") $profileErrors["city"]    = "City is required.";
    if ($country === "") $profileErrors["country"] = "Country is required.";

    $hasErrors = false;
    foreach ($profileErrors as $err) {
        if ($err !== "") { $hasErrors = true; break; }
    }

    if (!$hasErrors) {

        $upd = mysqli_prepare(
            $connect,
            "UPDATE users
             SET NAME = ?, email = ?, phone = ?, address = ?, city = ?, country = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param(
            $upd,
            "ssssssi",
            $name, $email, $phone, $address, $city, $country, $userId
        );

        if (mysqli_stmt_execute($upd)) {
            $_SESSION["user_name"]     = $name;
            $_SESSION["flash_success"] = "Profile updated successfully.";
        } else {
            $_SESSION["flash_error"] = "Failed to update profile.";
        }

        mysqli_stmt_close($upd);

        header("Location: profile.php");
        exit;
    }
}


/* =========================================================
   CHANGE PASSWORD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {

    $activeTab = "password";

    if (
        empty($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["profile_csrf"], $_POST["csrf_token"])
    ) {
        $passwordErrors["current_password"] = "Invalid session.";
    }

    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword     = $_POST["new_password"]     ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($currentPassword === "") {
        $passwordErrors["current_password"] = "Current password is required.";
    } else {
        $chk = mysqli_prepare(
            $connect,
            "SELECT PASSWORD AS password FROM users WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($chk, "i", $userId);
        mysqli_stmt_execute($chk);
        $chkRes = mysqli_stmt_get_result($chk);
        $row    = $chkRes ? mysqli_fetch_assoc($chkRes) : null;
        mysqli_stmt_close($chk);

        if (!$row || !password_verify($currentPassword, $row["password"])) {
            $passwordErrors["current_password"] = "Current password is incorrect.";
        }
    }

    if ($newPassword === "") {
        $passwordErrors["new_password"] = "New password is required.";
    } elseif (strlen($newPassword) < 6) {
        $passwordErrors["new_password"] = "Password must be at least 6 characters.";
    }

    if ($confirmPassword === "") {
        $passwordErrors["confirm_password"] = "Please confirm your new password.";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordErrors["confirm_password"] = "Passwords do not match.";
    }

    $hasErrors = false;
    foreach ($passwordErrors as $err) {
        if ($err !== "") { $hasErrors = true; break; }
    }

    if (!$hasErrors) {

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $upd = mysqli_prepare(
            $connect,
            "UPDATE users SET PASSWORD = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($upd, "si", $newHash, $userId);

        if (mysqli_stmt_execute($upd)) {
            $_SESSION["flash_success"] = "Password changed successfully.";
        } else {
            $_SESSION["flash_error"] = "Failed to change password.";
        }
        mysqli_stmt_close($upd);

        header("Location: profile.php");
        exit;
    }
}


/* =========================================================
   STATS
========================================================= */

$stats = ["total_orders" => 0, "pending_orders" => 0, "total_spent" => 0.0];

$sq = mysqli_prepare(
    $connect,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        COALESCE(SUM(CASE WHEN status != 'Cancelled' THEN total ELSE 0 END), 0) AS spent
     FROM orders
     WHERE user_id = ?"
);
mysqli_stmt_bind_param($sq, "i", $userId);
mysqli_stmt_execute($sq);
$sqRes    = mysqli_stmt_get_result($sq);
$statsRow = $sqRes ? mysqli_fetch_assoc($sqRes) : null;
mysqli_stmt_close($sq);

if ($statsRow) {
    $stats["total_orders"]   = (int) $statsRow["total"];
    $stats["pending_orders"] = (int) $statsRow["pending"];
    $stats["total_spent"]    = (float) $statsRow["spent"];
}


/* =========================================================
   FETCH ACTIVE CATEGORIES (for navbar mega menu)
========================================================= */

$categories = [];

$catRes = mysqli_query(
    $connect,
    "SELECT id, name
     FROM categories
     WHERE status = 'Active'
     ORDER BY name ASC
     LIMIT 8"
);

if ($catRes) {
    while ($row = mysqli_fetch_assoc($catRes)) {
        $categories[] = $row;
    }
}


/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;

if (isset($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
    foreach ($_SESSION["cart"] as $quantity) {
        $cartCount += (int) $quantity;
    }
}


/* =========================================================
   LOGIN STATUS
========================================================= */

$isLoggedIn = isset($_SESSION["user_id"]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Furnishop</title>
    <meta name="description" content="My Profile">
    <link rel="icon" type="image/x-icon" href="../Assets/Images/favicon.ico">

    <link rel="stylesheet" href="../Assets/CSS/bootstrap.min.css">
    <link rel="stylesheet" href="../Assets/Font/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/nice-select/nice-select.css">
    <link rel="stylesheet" href="../Assets/Plugin/OwlCarousel2-2.3.4/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/OwlCarousel2-2.3.4/dist/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/nouislider/nouislider.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/slick/slick.css">
    <link rel="stylesheet" href="../Assets/CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        .profile-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 26px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        }
        .profile-avatar {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--theme-default), #b07a52);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .profile-name {
            font-size: 17px;
            font-weight: 600;
            color: #0f172a;
        }
        .profile-email {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .profile-tabs .nav-link {
            border-radius: 10px;
            font-weight: 500;
            color: #475569;
            padding: 10px 20px;
        }
        .profile-tabs .nav-link.active {
            background-color: var(--theme-default);
            color: #fff;
        }
        .form-label {
            font-weight: 500;
            font-size: 13.5px;
            color: #334155;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <!-- =========================
         HEADER (matches index.php)
    ========================== -->
    <header>
        <div class="container py-lg-2 mt-0 mt-lg-2">
            <div class="row">

                <div class="col-12 col-lg-2 mb-2 mb-lg-3 pt-3 pt-lg-2">
                    <div class="row">
                        <div class="col-12 d-flex justify-content-center mb-3 mb-lg-0">
                            <a class="navbar-brand flex-shrink-0 py-0" href="index.php">
                                <img src="../Assets/Images/logo.png" class="logo main-logo" alt="Furnishop">
                            </a>
                        </div>
                        <div class="col-12">
                            <div class="list-inline d-lg-none d-flex justify-content-between">
                                <div class="list-inline-item d-inline-block d-lg-none">
                                    <button class="navbar-toggler border-0 collapsed" type="button"
                                            data-bs-toggle="offcanvas" data-bs-target="#navbar-default"
                                            aria-controls="navbar-default" aria-label="Toggle navigation">
                                        <i class="bi bi-text-indent-left"></i>
                                    </button>
                                </div>
                                <div>
                                    <div class="list-inline-item me-4">
                                        <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"
                                           class="text-muted d-flex flex-column justify-content-center align-items-center">
                                            <i class="bi bi-person"></i>
                                            <span class="d-none d-lg-block">Account</span>
                                        </a>
                                    </div>
                                    <div class="list-inline-item me-4">
                                        <a href="cart.php"
                                           class="text-muted d-flex flex-column justify-content-center align-items-center">
                                            <div class="position-relative">
                                                <i class="bi bi-cart"></i>
                                                <?php if ($cartCount > 0): ?>
                                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                        <?= $cartCount ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="d-none d-lg-block">Your cart</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <nav class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2"
                         aria-label="Offcanvas navbar large">
                        <div class="container">
                            <div class="offcanvas offcanvas-start pt-2" tabindex="-1" id="navbar-default"
                                 aria-labelledby="navbar-defaultLabel">

                                <div class="offcanvas-header pb-1">
                                    <a href="index.php">
                                        <img src="../Assets/Images/logo.png" alt="Furnishop">
                                    </a>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                            aria-label="Close"></button>
                                </div>

                                <div class="offcanvas-body">
                                    <div class="d-block d-lg-none mb-4">
                                        <form action="products.php" method="GET">
                                            <div class="input-group">
                                                <input class="form-control" type="search" name="search"
                                                       placeholder="Search for products">
                                                <span class="input-group-append">
                                                    <button class="btn bg-white border border-start-0 ms-n10 rounded-0 rounded-end"
                                                            type="submit">
                                                        <span class="bi bi-search"></span>
                                                    </button>
                                                </span>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="mx-auto">
                                        <ul class="navbar-nav align-items-center ms-lg-5">

                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link" href="index.php">Home</a>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">Shop</a>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="products.php">All Products</a></li>
                                                    <li><a class="dropdown-item" href="cart.php">Shopping Cart</a></li>
                                                    <li><a class="dropdown-item" href="checkout.php">Checkout</a></li>
                                                </ul>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto dropdown-fullwidth">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                                                <div class="dropdown-menu category-mega-menu">
                                                    <div class="category-mega-inner">
                                                        <div class="category-mega-heading">
                                                            <span>Shop by Category</span>
                                                            <a href="products.php">View All Products</a>
                                                        </div>
                                                        <?php if (!empty($categories)): ?>
                                                            <div class="category-mega-grid">
                                                                <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                                                                    <a class="category-menu-item"
                                                                       href="products.php?category=<?= (int) $cat["id"] ?>">
                                                                        <span class="category-menu-icon"><i class="bi bi-grid"></i></span>
                                                                        <span>
                                                                            <strong><?= e($cat["name"]) ?></strong>
                                                                        </span>
                                                                        <i class="bi bi-arrow-right category-menu-arrow"></i>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <p class="text-muted mb-0">No categories yet.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">Account</a>
                                                <ul class="dropdown-menu">
                                                    <?php if ($isLoggedIn): ?>
                                                        <li><a class="dropdown-item" href="profile.php">My Account</a></li>
                                                        <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                                    <?php else: ?>
                                                        <li><a class="dropdown-item" href="login.php">Sign in</a></li>
                                                        <li><a class="dropdown-item" href="register.php">Signup</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>

                <div class="col-12 col-lg-3 d-none d-lg-block">
                    <div class="list-inline d-flex pt-2">
                        <div class="list-inline-item me-4">
                            <a href="products.php"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-search"></i>
                                <span class="d-none d-lg-block">Search</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-person"></i>
                                <span class="d-none d-lg-block">Account</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="cart.php"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <div class="position-relative">
                                    <i class="bi bi-cart"></i>
                                    <?php if ($cartCount > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            <?= $cartCount ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="d-none d-lg-block">Your cart</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>
    <!-- Header End -->

    <!-- =========================
         MAIN
    ========================== -->
    <main>

        <!-- BREADCRUMB -->
        <div class="breadcrumb-main">
            <div class="container">
                <div class="breadcrumb-container">
                    <h2 class="page-title">My Profile</h2>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">My Profile</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- PROFILE -->
        <div class="container my-5">

            <!-- FLASH -->
            <?php if (!empty($_SESSION["flash_success"])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= e($_SESSION["flash_success"]) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION["flash_success"]); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION["flash_error"])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= e($_SESSION["flash_error"]) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION["flash_error"]); ?>
            <?php endif; ?>

            <div class="row g-4">

                <!-- SIDEBAR -->
                <div class="col-lg-4">

                    <!-- AVATAR -->
                    <div class="profile-card mb-4">
                        <div class="text-center pb-3 border-bottom">
                            <div class="profile-avatar">
                                <?= e(strtoupper(substr($user["name"], 0, 1))) ?>
                            </div>
                            <div class="profile-name"><?= e($user["name"]) ?></div>
                            <div class="profile-email"><?= e($user["email"]) ?></div>
                            <?php if (strtolower($user["status"]) === "active"): ?>
                                <span class="badge bg-success-subtle text-success-emphasis mt-2">
                                    <i class="bi bi-check-circle me-1"></i> Active Member
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger-emphasis mt-2">
                                    <i class="bi bi-x-circle me-1"></i> Inactive
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Customer ID</span>
                                <strong class="small">#<?= (int) $user["id"] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Joined</span>
                                <strong class="small">
                                    <?= !empty($user["created_at"])
                                        ? e(date("d M Y", strtotime($user["created_at"])))
                                        : "-" ?>
                                </strong>
                            </div>
                        </div>
                    </div>

                    <!-- STATS -->
                    <div class="profile-card">
                        <h5 class="mb-3">Activity</h5>

                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <div class="ms-3">
                                <div class="fw-bold"><?= $stats["total_orders"] ?></div>
                                <div class="text-muted small">Total Orders</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="ms-3">
                                <div class="fw-bold"><?= $stats["pending_orders"] ?></div>
                                <div class="text-muted small">Pending Orders</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="ms-3">
                                <div class="fw-bold">Rs. <?= number_format($stats["total_spent"], 0) ?></div>
                                <div class="text-muted small">Total Spent</div>
                            </div>
                        </div>

                        <a href="orders.php" class="btn btn-primary w-100 mt-4">
                            <i class="bi bi-list me-1"></i> View My Orders
                        </a>
                    </div>

                </div>

                <!-- MAIN -->
                <div class="col-lg-8">

                    <ul class="nav nav-pills profile-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $activeTab === "profile" ? "active" : "" ?>"
                                    data-bs-toggle="pill"
                                    data-bs-target="#tab-profile"
                                    type="button"
                                    role="tab">
                                <i class="bi bi-person me-2"></i> Profile Details
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $activeTab === "password" ? "active" : "" ?>"
                                    data-bs-toggle="pill"
                                    data-bs-target="#tab-password"
                                    type="button"
                                    role="tab">
                                <i class="bi bi-lock me-2"></i> Password
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- PROFILE TAB -->
                        <div class="tab-pane fade <?= $activeTab === "profile" ? "show active" : "" ?>" id="tab-profile">
                            <div class="profile-card">

                                <h5 class="mb-3">Edit Profile Information</h5>

                                <form method="post" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                                    <div class="row g-3">

                                        <div class="col-md-6">
                                            <label class="form-label">Full Name *</label>
                                            <input type="text" name="name"
                                                   class="form-control <?= $profileErrors["name"] ? "is-invalid" : "" ?>"
                                                   value="<?= e($name) ?>" required>
                                            <?php if ($profileErrors["name"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($profileErrors["name"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Email *</label>
                                            <input type="email" name="email"
                                                   class="form-control <?= $profileErrors["email"] ? "is-invalid" : "" ?>"
                                                   value="<?= e($email) ?>" required>
                                            <?php if ($profileErrors["email"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($profileErrors["email"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Phone *</label>
                                            <input type="text" name="phone"
                                                   class="form-control <?= $profileErrors["phone"] ? "is-invalid" : "" ?>"
                                                   value="<?= e($phone) ?>" required>
                                            <?php if ($profileErrors["phone"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($profileErrors["phone"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Country *</label>
                                            <input type="text" name="country"
                                                   class="form-control <?= $profileErrors["country"] ? "is-invalid" : "" ?>"
                                                   value="<?= e($country) ?>" required>
                                            <?php if ($profileErrors["country"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($profileErrors["country"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Address *</label>
                                            <input type="text" name="address"
                                                   class="form-control <?= $profileErrors["address"] ? "is-invalid" : "" ?>"
                                                   value="<?= e($address) ?>" required>
                                            <?php if ($profileErrors["address"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($profileErrors["address"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">City *</label>
                                            <input type="text" name="city"
                                                   class="form-control <?= $profileErrors["city"] ? "is-invalid" : "" ?>"
                                                   value="<?= e($city) ?>" required>
                                            <?php if ($profileErrors["city"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($profileErrors["city"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                    </div>

                                    <div class="text-end mt-4">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <!-- PASSWORD TAB -->
                        <div class="tab-pane fade <?= $activeTab === "password" ? "show active" : "" ?>" id="tab-password">
                            <div class="profile-card">

                                <h5 class="mb-3">Change Password</h5>

                                <form method="post" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                                    <div class="row g-3">

                                        <div class="col-12">
                                            <label class="form-label">Current Password *</label>
                                            <input type="password" name="current_password"
                                                   class="form-control <?= $passwordErrors["current_password"] ? "is-invalid" : "" ?>"
                                                   required>
                                            <?php if ($passwordErrors["current_password"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($passwordErrors["current_password"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">New Password *</label>
                                            <input type="password" name="new_password"
                                                   class="form-control <?= $passwordErrors["new_password"] ? "is-invalid" : "" ?>"
                                                   required>
                                            <?php if ($passwordErrors["new_password"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($passwordErrors["new_password"]) ?></div>
                                            <?php else: ?>
                                                <small class="text-muted">Minimum 6 characters.</small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Confirm New Password *</label>
                                            <input type="password" name="confirm_password"
                                                   class="form-control <?= $passwordErrors["confirm_password"] ? "is-invalid" : "" ?>"
                                                   required>
                                            <?php if ($passwordErrors["confirm_password"]): ?>
                                                <div class="invalid-feedback d-block"><?= e($passwordErrors["confirm_password"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                    </div>

                                    <div class="text-end mt-4">
                                        <button type="submit" name="change_password" class="btn btn-primary">
                                            <i class="bi bi-key me-1"></i> Update Password
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </main>

    <!-- =========================
         FOOTER (matches index.php)
    ========================== -->
    <footer class="mt-0">
        <div class="container">
            <div class="row">

                <div class="col-lg-4 mb-4 mb-md-0">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-12">
                            <div class="footer_logo">
                                <img loading="lazy" src="../Assets/Images/logo.png" class="logo" alt="Furnishop">
                            </div>
                            <div class="mt-4">
                                <p>Furnishop provides quality furniture and home products for every space.</p>
                                <h3 class="h5 fw-bold">+92 300 1234567</h3>
                                <p>support@furnishop.com</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-3 mb-md-0">
                    <div class="row">
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">My Account</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <li><a href="profile.php">My Account</a></li>
                                    <li><a href="orders.php">My Orders</a></li>
                                    <li><a href="logout.php">Logout</a></li>
                                    <li><a href="cart.php">Cart</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Shopping</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <li><a href="products.php">Products</a></li>
                                    <li><a href="checkout.php">Checkout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Useful Links</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <li><a href="cart.php">Shopping Cart</a></li>
                                    <li><a href="orders.php">My Orders</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Categories</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
                                            <li>
                                                <a href="products.php?category=<?= (int) $cat["id"] ?>">
                                                    <?= e($cat["name"]) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li><a href="products.php">All Products</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="text-center py-3 mt-4 text-white px-3 copyright">
            <span>Copyright © <?= date("Y") ?>. All Rights Reserved. Furnishop.</span>
        </div>
    </footer>

    <!-- =========================
         SCRIPTS
    ========================== -->
    <script src="../Assets/JS/jquery-3.6.0.min.js"></script>
    <script src="../Assets/JS/bootstrap.bundle.min.js"></script>
    <script src="../Assets/Plugin/nice-select/jquery.nice-select.min.js"></script>
    <script src="../Assets/Plugin/OwlCarousel2-2.3.4/dist/owl.carousel.min.js"></script>
    <script src="../Assets/Plugin/nouislider/nouislider.min.js"></script>
    <script src="../Assets/Plugin/slick/slick.min.js"></script>
    <script src="../Assets/JS/main.js"></script>
</body>
</html>