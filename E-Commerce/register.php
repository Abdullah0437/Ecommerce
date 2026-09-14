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
   VARIABLES
========================================================= */

$name    = "";
$email   = "";
$phone   = "";
$address = "";
$city    = "";
$country = "";

$nameErr            = "";
$emailErr           = "";
$phoneErr           = "";
$passwordErr        = "";
$confirmPasswordErr = "";
$addressErr         = "";
$cityErr            = "";
$countryErr         = "";
$agreeErr           = "";

$successMsg = "";


/* =========================================================
   FORM PROCESSING
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name            = trim($_POST["name"] ?? "");
    $email           = trim($_POST["email"] ?? "");
    $phone           = trim($_POST["phone"] ?? "");
    $userPassword    = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $address         = trim($_POST["address"] ?? "");
    $city            = trim($_POST["city"] ?? "");
    $country         = trim($_POST["country"] ?? "");
    $agree           = isset($_POST["agree"]) ? 1 : 0;


    /* -----------------------------
       Name
    ----------------------------- */

    if ($name === "") {

        $nameErr = "Full name is required.";
    } elseif (strlen($name) < 3) {

        $nameErr = "Name must be at least 3 characters.";
    } elseif (strlen($name) > 100) {

        $nameErr = "Name is too long.";
    }


    /* -----------------------------
       Email
    ----------------------------- */

    if ($email === "") {

        $emailErr = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $emailErr = "Enter a valid email address.";
    } elseif (strlen($email) > 150) {

        $emailErr = "Email is too long.";
    } else {

        $stmt = mysqli_prepare(
            $connect,
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {

                $emailErr = "This email is already registered.";
            }

            mysqli_stmt_close($stmt);
        }
    }


    /* -----------------------------
       Phone
    ----------------------------- */

    if ($phone === "") {

        $phoneErr = "Phone is required.";
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {

        $phoneErr = "Enter a valid phone number.";
    }


    /* -----------------------------
       Password
    ----------------------------- */

    if ($userPassword === "") {

        $passwordErr = "Password is required.";
    } elseif (strlen($userPassword) < 6) {

        $passwordErr = "Password must be at least 6 characters.";
    } elseif (strlen($userPassword) > 255) {

        $passwordErr = "Password is too long.";
    }


    /* -----------------------------
       Confirm Password
    ----------------------------- */

    if ($confirmPassword === "") {

        $confirmPasswordErr = "Please confirm your password.";
    } elseif ($confirmPassword !== $userPassword) {

        $confirmPasswordErr = "Passwords do not match.";
    }


    /* -----------------------------
       Address
    ----------------------------- */

    if ($address === "") {

        $addressErr = "Address is required.";
    } elseif (strlen($address) > 255) {

        $addressErr = "Address is too long.";
    }


    /* -----------------------------
       City
    ----------------------------- */

    if ($city === "") {

        $cityErr = "City is required.";
    } elseif (strlen($city) > 100) {

        $cityErr = "City is too long.";
    }


    /* -----------------------------
       Country
    ----------------------------- */

    if ($country === "") {

        $countryErr = "Country is required.";
    } elseif (strlen($country) > 100) {

        $countryErr = "Country is too long.";
    }


    /* -----------------------------
       Agree to policy
    ----------------------------- */

    if (!$agree) {

        $agreeErr = "You must agree to the Privacy Policy.";
    }


    /* -----------------------------
       Insert
    ----------------------------- */

    $hasErrors = (
        $nameErr ||
        $emailErr ||
        $phoneErr ||
        $passwordErr ||
        $confirmPasswordErr ||
        $addressErr ||
        $cityErr ||
        $countryErr ||
        $agreeErr
    );

    if (!$hasErrors) {

        $hash   = password_hash($userPassword, PASSWORD_DEFAULT);
        $status = "Active";

        $insert = "
            INSERT INTO users
                (NAME, email, phone, PASSWORD, address, city, country, STATUS)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($connect, $insert);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ssssssss",
                $name,
                $email,
                $phone,
                $hash,
                $address,
                $city,
                $country,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {

                $successMsg = "Registration successful! You can now sign in.";

                /* Reset form */
                $name    = "";
                $email   = "";
                $phone   = "";
                $address = "";
                $city    = "";
                $country = "";
            } else {

                $emailErr = "Something went wrong. Please try again.";
            }

            mysqli_stmt_close($stmt);
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

    <title>Sign Up - Furnishop</title>

    <meta
        name="description"
        content="Create your Furnishop customer account">

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
           REGISTER PAGE
        ===================================================== */

        .register-section {
            background: #f7f8fa;
            padding: 60px 0;
        }

        .register-card {
            border: 1px solid #e8eaee;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .register-card::before {
            content: "";
            display: block;
            height: 4px;
            background: #0d6efd;
        }

        .register-card .card-body {
            padding: 40px 36px;
        }

        .register-heading {
            font-size: 1.35rem;
            font-weight: 600;
            color: #1a1f29;
            margin-bottom: 6px;
        }

        .register-subtext {
            color: #6b7280;
            font-size: 0.92rem;
            margin-bottom: 0;
        }

        .register-input {
            min-height: 44px;
            border: 1px solid #d8dce3;
            border-radius: 6px;
            background: #ffffff;
            font-size: 0.95rem;
            color: #1a1f29;
        }

        .register-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
            background: #ffffff;
        }

        .register-input.is-invalid {
            border-color: #dc3545;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .register-input {
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

        .register-button {
            min-height: 46px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.95rem;
            background: #0d6efd;
            border: 1px solid #0d6efd;
            color: #ffffff;
        }

        .register-button:hover {
            background: #0b5ed7;
            border-color: #0b5ed7;
            color: #ffffff;
        }

        .register-button:focus,
        .register-button:active {
            background: #0a58ca;
            border-color: #0a58ca;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
            color: #ffffff;
        }

        .register-alert {
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .login-link {
            color: #0d6efd;
            font-weight: 500;
            text-decoration: none;
        }

        .login-link:hover {
            color: #0b5ed7;
            text-decoration: underline;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .cart-count {
            font-size: 10px;
        }

        @media (max-width: 767px) {

            .register-section {
                padding: 35px 0;
            }

            .register-card .card-body {
                padding: 30px 22px;
            }

            .register-heading {
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

                        <div class="list-inline-item me-4">

                            <a
                                href="<?= $isLoggedIn ? 'account.php' : 'login.php' ?>"
                                class="text-muted d-flex flex-column justify-content-center align-items-center">

                                <i class="bi bi-person"></i>

                                <span><?= $isLoggedIn ? 'My Account' : 'Account' ?></span>

                            </a>

                        </div>

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

                    <h2 class="page-title">Sign Up</h2>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php">Home</a>
                        </li>

                        <li class="breadcrumb-item">
                            <a href="login.php">Account</a>
                        </li>

                        <li class="breadcrumb-item active">Sign Up</li>

                    </ul>

                </div>

            </div>

        </div>

        <!-- REGISTER FORM -->
        <section class="register-section">

            <div class="container">

                <div class="row justify-content-center">

                    <div class="col-12 col-md-8 col-lg-6">

                        <div class="card register-card">

                            <div class="card-body">

                                <div class="mb-4">

                                    <h1 class="register-heading">
                                        Create Your Account
                                    </h1>

                                    <p class="register-subtext">
                                        Sign up with your details to start shopping.
                                    </p>

                                </div>

                                <!-- SUCCESS -->
                                <?php if ($successMsg !== ""): ?>

                                    <div
                                        class="alert alert-success register-alert alert-dismissible fade show"
                                        role="alert">

                                        <?= e($successMsg) ?>

                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="alert"
                                            aria-label="Close"></button>

                                    </div>

                                <?php endif; ?>

                                <!-- FORM -->
                                <form
                                    method="POST"
                                    action="<?= e($_SERVER["PHP_SELF"]) ?>"
                                    novalidate>

                                    <div class="row g-3">

                                        <!-- NAME -->
                                        <div class="col-12">

                                            <label for="formName" class="form-label">
                                                Full Name
                                            </label>

                                            <input
                                                type="text"
                                                name="name"
                                                id="formName"
                                                class="form-control register-input <?= $nameErr !== "" ? 'is-invalid' : '' ?>"
                                                placeholder="Enter full name"
                                                value="<?= e($name) ?>"
                                                autocomplete="name"
                                                maxlength="100"
                                                required>

                                            <?php if ($nameErr !== ""): ?>
                                                <div class="invalid-feedback">
                                                    <?= e($nameErr) ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <!-- EMAIL -->
                                        <div class="col-12">

                                            <label for="formEmail" class="form-label">
                                                Email
                                            </label>

                                            <input
                                                type="email"
                                                name="email"
                                                id="formEmail"
                                                class="form-control register-input <?= $emailErr !== "" ? 'is-invalid' : '' ?>"
                                                placeholder="Enter email"
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

                                        <!-- PHONE -->
                                        <div class="col-12">

                                            <label for="formPhone" class="form-label">
                                                Phone
                                            </label>

                                            <input
                                                type="text"
                                                name="phone"
                                                id="formPhone"
                                                class="form-control register-input <?= $phoneErr !== "" ? 'is-invalid' : '' ?>"
                                                placeholder="Enter phone number"
                                                value="<?= e($phone) ?>"
                                                autocomplete="tel"
                                                maxlength="20"
                                                required>

                                            <?php if ($phoneErr !== ""): ?>
                                                <div class="invalid-feedback">
                                                    <?= e($phoneErr) ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <!-- ADDRESS -->
                                        <div class="col-12">

                                            <label for="formAddress" class="form-label">
                                                Address
                                            </label>

                                            <input
                                                type="text"
                                                name="address"
                                                id="formAddress"
                                                class="form-control register-input <?= $addressErr !== "" ? 'is-invalid' : '' ?>"
                                                placeholder="Enter address"
                                                value="<?= e($address) ?>"
                                                autocomplete="street-address"
                                                maxlength="255"
                                                required>

                                            <?php if ($addressErr !== ""): ?>
                                                <div class="invalid-feedback">
                                                    <?= e($addressErr) ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <!-- CITY -->
                                        <div class="col-12 col-md-6">

                                            <label for="formCity" class="form-label">
                                                City
                                            </label>

                                            <input
                                                type="text"
                                                name="city"
                                                id="formCity"
                                                class="form-control register-input <?= $cityErr !== "" ? 'is-invalid' : '' ?>"
                                                placeholder="Enter city"
                                                value="<?= e($city) ?>"
                                                autocomplete="address-level2"
                                                maxlength="100"
                                                required>

                                            <?php if ($cityErr !== ""): ?>
                                                <div class="invalid-feedback">
                                                    <?= e($cityErr) ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <!-- COUNTRY -->
                                        <div class="col-12 col-md-6">

                                            <label for="formCountry" class="form-label">
                                                Country
                                            </label>

                                            <input
                                                type="text"
                                                name="country"
                                                id="formCountry"
                                                class="form-control register-input <?= $countryErr !== "" ? 'is-invalid' : '' ?>"
                                                placeholder="Enter country"
                                                value="<?= e($country) ?>"
                                                autocomplete="country-name"
                                                maxlength="100"
                                                required>

                                            <?php if ($countryErr !== ""): ?>
                                                <div class="invalid-feedback">
                                                    <?= e($countryErr) ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <!-- PASSWORD -->
                                        <div class="col-12 col-md-6">

                                            <label for="formPassword" class="form-label">
                                                Password
                                            </label>

                                            <div class="password-wrapper">

                                                <input
                                                    type="password"
                                                    name="password"
                                                    id="formPassword"
                                                    class="form-control register-input <?= $passwordErr !== "" ? 'is-invalid' : '' ?>"
                                                    placeholder="Enter password"
                                                    autocomplete="new-password"
                                                    maxlength="255"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="password-toggle"
                                                    data-target="formPassword"
                                                    aria-label="Show password">

                                                    <i class="bi bi-eye"></i>

                                                </button>

                                            </div>

                                            <?php if ($passwordErr !== ""): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= e($passwordErr) ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <!-- CONFIRM PASSWORD -->
                                        <div class="col-12 col-md-6">

                                            <label for="formConfirmPassword" class="form-label">
                                                Confirm Password
                                            </label>

                                            <div class="password-wrapper">

                                                <input
                                                    type="password"
                                                    name="confirm_password"
                                                    id="formConfirmPassword"
                                                    class="form-control register-input <?= $confirmPasswordErr !== "" ? 'is-invalid' : '' ?>"
                                                    placeholder="Confirm password"
                                                    autocomplete="new-password"
                                                    maxlength="255"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="password-toggle"
                                                    data-target="formConfirmPassword"
                                                    aria-label="Show password">

                                                    <i class="bi bi-eye"></i>

                                                </button>

                                            </div>

                                            <?php if ($confirmPasswordErr !== ""): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= e($confirmPasswordErr) ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <!-- AGREE -->
                                        <div class="col-12">

                                            <div class="form-check">

                                                <input
                                                    type="checkbox"
                                                    name="agree"
                                                    value="1"
                                                    id="policy"
                                                    class="form-check-input <?= $agreeErr !== "" ? 'is-invalid' : '' ?>"
                                                    required>

                                                <label class="form-check-label" for="policy">
                                                    I have read and agree to the
                                                    <a href="#" class="text-primary">Privacy Policy</a>
                                                </label>

                                                <?php if ($agreeErr !== ""): ?>
                                                    <div class="invalid-feedback d-block">
                                                        <?= e($agreeErr) ?>
                                                    </div>
                                                <?php endif; ?>

                                            </div>

                                        </div>

                                        <!-- SUBMIT -->
                                        <div class="col-12 d-grid">

                                            <button
                                                type="submit"
                                                class="btn register-button">

                                                Create Account

                                            </button>

                                        </div>

                                        <!-- LOGIN LINK -->
                                        <div class="col-12 text-center">

                                            <span class="register-subtext">
                                                Already registered?
                                            </span>

                                            <a href="login.php" class="login-link">
                                                Sign in
                                            </a>

                                        </div>

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
        /* Password show / hide — works for both password fields */
        document.querySelectorAll(".password-toggle").forEach(function(btn) {

            btn.addEventListener("click", function() {

                const targetId = btn.getAttribute("data-target");
                const input = document.getElementById(targetId);
                const icon = btn.querySelector("i");

                if (!input || !icon) return;

                if (input.type === "password") {

                    input.type = "text";
                    icon.classList.remove("bi-eye");
                    icon.classList.add("bi-eye-slash");
                    btn.setAttribute("aria-label", "Hide password");

                } else {

                    input.type = "password";
                    icon.classList.remove("bi-eye-slash");
                    icon.classList.add("bi-eye");
                    btn.setAttribute("aria-label", "Show password");

                }

            });

        });
    </script>

</body>

</html>