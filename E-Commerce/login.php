<?php

session_start();

require_once __DIR__ . "/../Config/database.php";


/* =========================================================
   REDIRECT ALREADY LOGGED-IN CUSTOMER
========================================================= */

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   VARIABLES
========================================================= */

$email       = "";
$emailErr    = "";
$passwordErr = "";
$loginErr    = "";


/* =========================================================
   INITIALIZE CART
========================================================= */

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}


/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;

foreach ($_SESSION["cart"] as $quantity) {
    $cartCount += (int) $quantity;
}


/* =========================================================
   LOGIN FORM PROCESSING
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email        = trim($_POST["email"] ?? "");
    $userPassword = $_POST["password"] ?? "";


    /* -----------------------------
       Email Validation
    ----------------------------- */

    if ($email === "") {

        $emailErr = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $emailErr = "Enter a valid email address.";
    }


    /* -----------------------------
       Password Validation
    ----------------------------- */

    if ($userPassword === "") {

        $passwordErr = "Password is required.";
    }


    /* -----------------------------
       Database Authentication
    ----------------------------- */

    if ($emailErr === "" && $passwordErr === "") {

        $query = "
            SELECT id, NAME, email, PASSWORD, STATUS
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($connect, $query);

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {

                $user = mysqli_fetch_assoc($result);

                /* Check account status */
                if ($user["STATUS"] !== "Active") {

                    $loginErr = "Your account is inactive. Please contact support.";
                } elseif (password_verify($userPassword, $user["PASSWORD"])) {

                    /* Prevent session fixation */
                    session_regenerate_id(true);

                    /* Store session */
                    $_SESSION["user_id"]    = (int) $user["id"];
                    $_SESSION["user_name"]  = $user["NAME"];
                    $_SESSION["user_email"] = $user["email"];

                    header("Location: index.php");
                    exit;
                } else {

                    $loginErr = "Invalid email or password.";
                }
            } else {

                $loginErr = "Invalid email or password.";
            }

            mysqli_stmt_close($stmt);
        } else {

            $loginErr = "Something went wrong. Please try again.";
        }
    }
}


/* =========================================================
   ESCAPE OUTPUT
========================================================= */

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Login - Furnishop</title>

    <meta
        name="description"
        content="Login to your Furnishop customer account">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
        href="../assets/font/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        /* =====================================================
           LOGIN PAGE
        ===================================================== */

        .login-section {
            background: #f7f8fa;
            padding: 60px 0;
            min-height: 500px;
        }

        .login-card {
            border: 1px solid #e8eaee;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        /* Thin accent bar at top */
        .login-card::before {
            content: "";
            display: block;
            height: 4px;
            background: #0d6efd;
        }

        .login-card .card-body {
            padding: 40px 36px;
        }

        .login-heading {
            font-size: 1.35rem;
            font-weight: 600;
            color: #1a1f29;
            margin-bottom: 6px;
        }

        .login-subtext {
            color: #6b7280;
            font-size: 0.92rem;
            margin-bottom: 0;
        }

        /* ---- Inputs ---- */
        .login-input {
            min-height: 44px;
            border: 1px solid #d8dce3;
            border-radius: 6px;
            background: #ffffff;
            font-size: 0.95rem;
            color: #1a1f29;
        }

        .login-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
            background: #ffffff;
        }

        .login-input.is-invalid {
            border-color: #dc3545;
        }

        /* ---- Password toggle ---- */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .login-input {
            padding-right: 46px;
        }

        .password-toggle {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #9aa1ad;
            padding: 8px 12px;
            cursor: pointer;
            line-height: 1;
        }

        .password-toggle:hover {
            color: #4b5563;
        }

        .password-toggle:focus {
            outline: none;
        }

        /* ---- Button ---- */
        .login-button {
            min-height: 46px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.95rem;
            background: #0d6efd;
            border: 1px solid #0d6efd;
            color: #ffffff;
        }

        .login-button:hover {
            background: #0b5ed7;
            border-color: #0b5ed7;
            color: #ffffff;
        }

        .login-button:focus,
        .login-button:active {
            background: #0a58ca;
            border-color: #0a58ca;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
            color: #ffffff;
        }

        /* ---- Alert ---- */
        .login-alert {
            border-radius: 6px;
            font-size: 0.9rem;
        }

        /* ---- Register link ---- */
        .register-text {
            color: #6b7280;
            font-size: 0.92rem;
        }

        .register-link {
            color: #0d6efd;
            font-weight: 500;
            text-decoration: none;
        }

        .register-link:hover {
            color: #0b5ed7;
            text-decoration: underline;
        }

        /* ---- Cart badge ---- */
        .cart-count {
            font-size: 10px;
        }

        /* ---- Mobile ---- */
        @media (max-width: 767px) {

            .login-section {
                padding: 35px 0;
            }

            .login-card .card-body {
                padding: 30px 22px;
            }

            .login-heading {
                font-size: 1.2rem;
            }
        }
    </style>

</head>

<body>

    <!-- =====================================================
         HEADER START
    ===================================================== -->

    <header>

        <div class="container py-lg-2 mt-0 mt-lg-2">

            <div class="row">

                <!-- LOGO -->
                <div class="col-12 col-sm-12 col-md-12 col-lg-2 mb-2 mb-lg-3 pt-3 pt-lg-2">

                    <div class="row">

                        <div class="col-12 d-flex justify-content-center mb-3 mb-lg-0">

                            <a class="navbar-brand flex-shrink-0 py-0" href="index.php">

                                <img
                                    src="../assets/images/logo.png"
                                    class="logo main-logo"
                                    alt="Furnishop">

                            </a>

                        </div>

                        <!-- MOBILE ACTIONS -->
                        <div class="col-12">

                            <div class="list-inline d-lg-none d-flex justify-content-between">

                                <!-- Mobile Menu -->
                                <div class="list-inline-item">

                                    <button
                                        class="navbar-toggler border-0 collapsed"
                                        type="button"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#navbar-default"
                                        aria-controls="navbar-default"
                                        aria-label="Open menu">

                                        <i class="bi bi-text-indent-left"></i>

                                    </button>

                                </div>

                                <div>

                                    <!-- Account -->
                                    <div class="list-inline-item me-3">

                                        <a
                                            href="<?= $isLoggedIn ? 'account.php' : 'login.php' ?>"
                                            class="text-muted d-flex flex-column justify-content-center align-items-center">

                                            <i class="bi bi-person"></i>
                                            <span>Account</span>

                                        </a>

                                    </div>

                                    <!-- Cart -->
                                    <div class="list-inline-item">

                                        <a
                                            href="cart.php"
                                            class="text-muted d-flex flex-column justify-content-center align-items-center">

                                            <div class="position-relative">

                                                <i class="bi bi-cart"></i>

                                                <?php if ($cartCount > 0): ?>
                                                    <span
                                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success cart-count">

                                                        <?= $cartCount ?>

                                                    </span>
                                                <?php endif; ?>

                                            </div>

                                            <span>Your cart</span>

                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- NAVIGATION -->
                <div class="col-12 col-sm-12 col-md-12 col-lg-7">

                    <nav
                        class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2"
                        aria-label="Main navigation">

                        <div class="container">

                            <div
                                class="offcanvas offcanvas-start pt-2"
                                tabindex="-1"
                                id="navbar-default"
                                aria-labelledby="navbar-defaultLabel">

                                <!-- Mobile Offcanvas Header -->
                                <div class="offcanvas-header pb-1">

                                    <a href="index.php">

                                        <img
                                            src="../assets/images/logo.png"
                                            alt="Furnishop">

                                    </a>

                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>

                                </div>

                                <!-- Offcanvas Body -->
                                <div class="offcanvas-body">

                                    <!-- Mobile Search -->
                                    <div class="d-block d-lg-none mb-4">

                                        <form action="products.php" method="GET">

                                            <div class="input-group">

                                                <input
                                                    class="form-control"
                                                    type="search"
                                                    name="search"
                                                    placeholder="Search for products"
                                                    autocomplete="off">

                                                <button
                                                    class="btn bg-white border"
                                                    type="submit">

                                                    <i class="bi bi-search"></i>

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                    <!-- Navigation Menu -->
                                    <div class="mx-auto">

                                        <ul class="navbar-nav align-items-center ms-lg-5">

                                            <li class="nav-item w-100 w-lg-auto">
                                                <a class="nav-link" href="index.php">Home</a>
                                            </li>

                                            <li class="nav-item w-100 w-lg-auto">
                                                <a class="nav-link" href="products.php">Products</a>
                                            </li>

                                            <li class="nav-item w-100 w-lg-auto">
                                                <a class="nav-link" href="categories.php">Categories</a>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto">

                                                <a
                                                    class="nav-link dropdown-toggle"
                                                    href="#"
                                                    role="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false">

                                                    Account

                                                </a>

                                                <ul class="dropdown-menu">

                                                    <?php if (!$isLoggedIn): ?>

                                                        <li>
                                                            <a class="dropdown-item" href="login.php">
                                                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                                                Sign In
                                                            </a>
                                                        </li>

                                                        <li>
                                                            <a class="dropdown-item" href="register.php">
                                                                <i class="bi bi-person-plus me-2"></i>
                                                                Sign Up
                                                            </a>
                                                        </li>

                                                    <?php else: ?>

                                                        <li>
                                                            <a class="dropdown-item" href="account.php">
                                                                <i class="bi bi-person me-2"></i>
                                                                My Account
                                                            </a>
                                                        </li>

                                                        <li>
                                                            <a class="dropdown-item" href="orders.php">
                                                                <i class="bi bi-bag me-2"></i>
                                                                My Orders
                                                            </a>
                                                        </li>

                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>

                                                        <li>
                                                            <a class="dropdown-item text-danger" href="logout.php">
                                                                <i class="bi bi-box-arrow-right me-2"></i>
                                                                Logout
                                                            </a>
                                                        </li>

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

                <!-- DESKTOP ACTIONS -->
                <div class="col-12 col-sm-12 col-md-12 col-lg-3 d-none d-lg-block">

                    <div class="list-inline d-flex pt-2">

                        <!-- Account -->
                        <div class="list-inline-item me-4">

                            <a
                                href="<?= $isLoggedIn ? 'account.php' : 'login.php' ?>"
                                class="text-muted d-flex flex-column justify-content-center align-items-center">

                                <i class="bi bi-person"></i>

                                <span><?= $isLoggedIn ? 'My Account' : 'Account' ?></span>

                            </a>

                        </div>

                        <!-- Cart -->
                        <div class="list-inline-item me-4">

                            <a
                                href="cart.php"
                                class="text-muted d-flex flex-column justify-content-center align-items-center">

                                <div class="position-relative">

                                    <i class="bi bi-cart"></i>

                                    <?php if ($cartCount > 0): ?>
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success cart-count">

                                            <?= $cartCount ?>

                                        </span>
                                    <?php endif; ?>

                                </div>

                                <span>Your cart</span>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </header>

    <!-- HEADER END -->


    <!-- =====================================================
         MAIN START
    ===================================================== -->

    <main>

        <!-- BREADCRUMB -->
        <div class="breadcrumb-main">

            <div class="container">

                <div class="breadcrumb-container">

                    <h2 class="page-title">Login</h2>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php">Home</a>
                        </li>

                        <li class="breadcrumb-item">
                            <a href="login.php">Account</a>
                        </li>

                        <li class="breadcrumb-item active">Login</li>

                    </ul>

                </div>

            </div>

        </div>

        <!-- LOGIN FORM -->
        <section class="login-section">

            <div class="container">

                <div class="row justify-content-center">

                    <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                        <div class="card login-card">

                            <div class="card-body">

                                <!-- Heading -->
                                <div class="mb-4">

                                    <h1 class="login-heading">
                                        Registered Customers
                                    </h1>

                                    <p class="login-subtext">
                                        Sign in with your email to access your account.
                                    </p>

                                </div>

                                <!-- LOGIN ERROR -->
                                <?php if ($loginErr !== ""): ?>

                                    <div
                                        class="alert alert-danger login-alert alert-dismissible fade show"
                                        role="alert">

                                        <?= e($loginErr) ?>

                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="alert"
                                            aria-label="Close"></button>

                                    </div>

                                <?php endif; ?>

                                <!-- LOGIN FORM -->
                                <form
                                    method="POST"
                                    action="<?= e($_SERVER["PHP_SELF"]) ?>"
                                    novalidate>

                                    <!-- EMAIL -->
                                    <div class="mb-3">

                                        <label for="formSigninEmail" class="form-label">
                                            Email
                                        </label>

                                        <input
                                            type="email"
                                            name="email"
                                            id="formSigninEmail"
                                            class="form-control login-input <?= $emailErr !== "" ? 'is-invalid' : '' ?>"
                                            placeholder="Enter your email"
                                            value="<?= e($email) ?>"
                                            autocomplete="email"
                                            maxlength="150"
                                            required>

                                        <?php if ($emailErr !== ""): ?>
                                            <div class="invalid-feedback">
                                                <?= e($emailErr) ?>
                                            </div>
                                        <?php endif; ?>

                                    </div>

                                    <!-- PASSWORD -->
                                    <div class="mb-3">

                                        <label for="formSigninPassword" class="form-label">
                                            Password
                                        </label>

                                        <div class="password-wrapper">

                                            <input
                                                type="password"
                                                name="password"
                                                id="formSigninPassword"
                                                class="form-control login-input <?= $passwordErr !== "" ? 'is-invalid' : '' ?>"
                                                placeholder="Enter your password"
                                                autocomplete="current-password"
                                                maxlength="255"
                                                required>

                                            <button
                                                type="button"
                                                class="password-toggle"
                                                id="togglePassword"
                                                aria-label="Show password">

                                                <i class="bi bi-eye" id="passwordIcon"></i>

                                            </button>

                                        </div>

                                        <?php if ($passwordErr !== ""): ?>
                                            <div class="invalid-feedback d-block">
                                                <?= e($passwordErr) ?>
                                            </div>
                                        <?php endif; ?>

                                    </div>

                                    <!-- LOGIN BUTTON -->
                                    <div class="d-grid mb-3">

                                        <button
                                            type="submit"
                                            class="btn login-button">

                                            Sign In

                                        </button>

                                    </div>

                                    <!-- REGISTER LINK -->
                                    <div class="text-center">

                                        <span class="register-text">
                                            New customer?
                                        </span>

                                        <a
                                            href="register.php"
                                            class="register-link">

                                            Create an account

                                        </a>

                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>

    <!-- MAIN END -->


    <!-- =====================================================
         FOOTER START
    ===================================================== -->

    <footer class="mt-50">

        <div class="container">

            <div class="row">

                <!-- FOOTER LOGO / CONTACT -->
                <div class="col-lg-4 mb-4 mb-md-0">

                    <div class="footer_logo">

                        <a href="index.php">

                            <img
                                loading="lazy"
                                src="../assets/images/logo.png"
                                class="logo"
                                alt="Furnishop">

                        </a>

                    </div>

                    <div class="mt-4">

                        <p>
                            Furnishop provides quality furniture
                            and home products for every space.
                        </p>

                        <h3 class="h5 fw-bold">+92 300 1234567</h3>

                        <p>support@furnishop.com</p>

                    </div>

                </div>

                <!-- MY ACCOUNT / INFORMATION -->
                <div class="col-lg-4 mb-3 mb-md-0">

                    <div class="row">

                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">My Account</h4>

                                <ul class="m-0 p-0 list-unstyled">

                                    <?php if (!$isLoggedIn): ?>

                                        <li><a href="login.php">Login</a></li>
                                        <li><a href="register.php">Register</a></li>

                                    <?php else: ?>

                                        <li><a href="account.php">My Account</a></li>
                                        <li><a href="orders.php">My Orders</a></li>
                                        <li><a href="logout.php">Logout</a></li>

                                    <?php endif; ?>

                                    <li><a href="cart.php">Cart</a></li>

                                </ul>

                            </div>

                        </div>

                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">Information</h4>

                                <ul class="m-0 p-0 list-unstyled">

                                    <li><a href="#">About Us</a></li>
                                    <li><a href="#">Return Policy</a></li>
                                    <li><a href="#">Privacy Policy</a></li>
                                    <li><a href="#">FAQ</a></li>

                                </ul>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- USEFUL LINKS / CATEGORIES -->
                <div class="col-lg-4">

                    <div class="row">

                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">Useful Links</h4>

                                <ul class="m-0 p-0 list-unstyled">

                                    <li><a href="products.php">Products</a></li>
                                    <li><a href="cart.php">Shopping Cart</a></li>
                                    <li><a href="orders.php">My Orders</a></li>
                                    <li><a href="#">Contact</a></li>

                                </ul>

                            </div>

                        </div>

                        <div class="col-6">

                            <div class="footer_menu">

                                <h4 class="footer_title">Categories</h4>

                                <ul class="m-0 p-0 list-unstyled">

                                    <li><a href="products.php">All Products</a></li>

                                </ul>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- COPYRIGHT -->
        <div class="text-center py-3 mt-4 text-white px-3 copyright">

            <span>
                Copyright © <?= date("Y") ?>.
                All Rights Reserved. Furnishop.
            </span>

        </div>

    </footer>

    <!-- FOOTER END -->


    <!-- =====================================================
         JAVASCRIPT
    ===================================================== -->

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>

    <script>
        const togglePassword = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("formSigninPassword");
        const passwordIcon = document.getElementById("passwordIcon");

        if (togglePassword && passwordInput && passwordIcon) {

            togglePassword.addEventListener("click", function() {

                if (passwordInput.type === "password") {

                    passwordInput.type = "text";
                    passwordIcon.classList.remove("bi-eye");
                    passwordIcon.classList.add("bi-eye-slash");
                    togglePassword.setAttribute("aria-label", "Hide password");

                } else {

                    passwordInput.type = "password";
                    passwordIcon.classList.remove("bi-eye-slash");
                    passwordIcon.classList.add("bi-eye");
                    togglePassword.setAttribute("aria-label", "Show password");

                }

            });

        }
    </script>

</body>

</html>