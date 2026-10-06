<?php

session_start();

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Includes/functions.php";


/* =========================================================
   REDIRECT ALREADY LOGGED-IN CUSTOMER
========================================================= */

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
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

$formErr = "";
$csrfOk  = true;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !customer_csrf_valid($_POST["csrf_token"] ?? null)) {
    $csrfOk  = false;
    $formErr = "Your session expired. Please try again.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $csrfOk) {

    $name            = trim($_POST["name"] ?? "");
    $email           = trim($_POST["email"] ?? "");
    $phone           = trim($_POST["phone"] ?? "");
    $userPassword    = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $address         = trim($_POST["address"] ?? "");
    $city            = trim($_POST["city"] ?? "");
    $country         = trim($_POST["country"] ?? "");
    $agree           = isset($_POST["agree"]) ? 1 : 0;

    /* Name */
    if ($name === "") {
        $nameErr = "Full name is required.";
    } elseif (strlen($name) < 3) {
        $nameErr = "Name must be at least 3 characters.";
    } elseif (strlen($name) > 100) {
        $nameErr = "Name is too long.";
    }

    /* Email */
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

    /* Phone */
    if ($phone === "") {
        $phoneErr = "Phone is required.";
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $phoneErr = "Enter a valid phone number.";
    }

    /* Password */
    if ($userPassword === "") {
        $passwordErr = "Password is required.";
    } elseif (strlen($userPassword) < 6) {
        $passwordErr = "Password must be at least 6 characters.";
    } elseif (strlen($userPassword) > 255) {
        $passwordErr = "Password is too long.";
    }

    /* Confirm Password */
    if ($confirmPassword === "") {
        $confirmPasswordErr = "Please confirm your password.";
    } elseif ($confirmPassword !== $userPassword) {
        $confirmPasswordErr = "Passwords do not match.";
    }

    /* Address */
    if ($address === "") {
        $addressErr = "Address is required.";
    } elseif (strlen($address) > 255) {
        $addressErr = "Address is too long.";
    }

    /* City */
    if ($city === "") {
        $cityErr = "City is required.";
    } elseif (strlen($city) > 100) {
        $cityErr = "City is too long.";
    }

    /* Country */
    if ($country === "") {
        $countryErr = "Country is required.";
    } elseif (strlen($country) > 100) {
        $countryErr = "Country is too long.";
    }

    /* Agree */
    if (!$agree) {
        $agreeErr = "You must agree to the Privacy Policy.";
    }

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
   LOGIN STATUS
========================================================= */

$isLoggedIn = isset($_SESSION["user_id"]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Furnishop</title>
    <meta name="description" content="Create your Furnishop customer account">
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
        .register-section {
            background: #f7f8fa;
            padding: 60px 0;
        }
        .register-card {
            border: 1px solid #e8eaee;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .register-card::before {
            content: "";
            display: block;
            height: 4px;
            background: var(--theme-default);
        }
        .register-card .card-body {
            padding: 36px 32px;
        }
        .register-heading {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a1f29;
            margin-bottom: 6px;
        }
        .register-subtext {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .register-input {
            min-height: 44px;
            border: 1px solid #d8dce3;
            border-radius: 8px;
            font-size: 0.92rem;
        }
        .register-input:focus {
            border-color: var(--theme-default);
            box-shadow: 0 0 0 3px rgba(140, 89, 59, 0.12);
        }
        .password-wrapper { position: relative; }
        .password-wrapper .register-input { padding-right: 46px; }
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
        .password-toggle:hover { color: #4b5563; }
        .password-toggle:focus { outline: none; }

        .register-button {
            min-height: 46px;
            border-radius: 8px;
            font-weight: 500;
        }
        .login-link {
            color: var(--theme-default);
            font-weight: 500;
            text-decoration: none;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        @media (max-width: 767px) {
            .register-section { padding: 35px 0; }
            .register-card .card-body { padding: 28px 20px; }
            .register-heading { font-size: 1.15rem; }
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
                    <h2 class="page-title">Sign Up</h2>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
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
                                    <h1 class="register-heading">Create Your Account</h1>
                                    <p class="register-subtext">
                                        Sign up with your details to start shopping.
                                    </p>
                                </div>

                                <?php if ($successMsg !== ""): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="bi bi-check-circle me-1"></i>
                                        <?= e($successMsg) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($formErr !== ""): ?>
                                    <div class="alert alert-danger"><?= e($formErr) ?></div>
                                <?php endif; ?>

                                <form method="POST" action="<?= e($_SERVER["PHP_SELF"]) ?>" novalidate>

                                    <input type="hidden" name="csrf_token" value="<?= e(customer_csrf_token()) ?>">

                                    <div class="row g-3">

                                        <!-- NAME -->
                                        <div class="col-12">
                                            <label for="formName" class="form-label">Full Name</label>
                                            <input type="text"
                                                   name="name"
                                                   id="formName"
                                                   class="form-control register-input <?= $nameErr !== "" ? 'is-invalid' : '' ?>"
                                                   placeholder="Enter full name"
                                                   value="<?= e($name) ?>"
                                                   autocomplete="name"
                                                   maxlength="100"
                                                   required>
                                            <?php if ($nameErr !== ""): ?>
                                                <div class="invalid-feedback"><?= e($nameErr) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- EMAIL -->
                                        <div class="col-12">
                                            <label for="formEmail" class="form-label">Email</label>
                                            <input type="email"
                                                   name="email"
                                                   id="formEmail"
                                                   class="form-control register-input <?= $emailErr !== "" ? 'is-invalid' : '' ?>"
                                                   placeholder="Enter email"
                                                   value="<?= e($email) ?>"
                                                   autocomplete="email"
                                                   maxlength="150"
                                                   required>
                                            <?php if ($emailErr !== ""): ?>
                                                <div class="invalid-feedback"><?= e($emailErr) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- PHONE -->
                                        <div class="col-12">
                                            <label for="formPhone" class="form-label">Phone</label>
                                            <input type="text"
                                                   name="phone"
                                                   id="formPhone"
                                                   class="form-control register-input <?= $phoneErr !== "" ? 'is-invalid' : '' ?>"
                                                   placeholder="Enter phone number"
                                                   value="<?= e($phone) ?>"
                                                   autocomplete="tel"
                                                   maxlength="20"
                                                   required>
                                            <?php if ($phoneErr !== ""): ?>
                                                <div class="invalid-feedback"><?= e($phoneErr) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- ADDRESS -->
                                        <div class="col-12">
                                            <label for="formAddress" class="form-label">Address</label>
                                            <input type="text"
                                                   name="address"
                                                   id="formAddress"
                                                   class="form-control register-input <?= $addressErr !== "" ? 'is-invalid' : '' ?>"
                                                   placeholder="Enter address"
                                                   value="<?= e($address) ?>"
                                                   autocomplete="street-address"
                                                   maxlength="255"
                                                   required>
                                            <?php if ($addressErr !== ""): ?>
                                                <div class="invalid-feedback"><?= e($addressErr) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- CITY -->
                                        <div class="col-12 col-md-6">
                                            <label for="formCity" class="form-label">City</label>
                                            <input type="text"
                                                   name="city"
                                                   id="formCity"
                                                   class="form-control register-input <?= $cityErr !== "" ? 'is-invalid' : '' ?>"
                                                   placeholder="Enter city"
                                                   value="<?= e($city) ?>"
                                                   autocomplete="address-level2"
                                                   maxlength="100"
                                                   required>
                                            <?php if ($cityErr !== ""): ?>
                                                <div class="invalid-feedback"><?= e($cityErr) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- COUNTRY -->
                                        <div class="col-12 col-md-6">
                                            <label for="formCountry" class="form-label">Country</label>
                                            <input type="text"
                                                   name="country"
                                                   id="formCountry"
                                                   class="form-control register-input <?= $countryErr !== "" ? 'is-invalid' : '' ?>"
                                                   placeholder="Enter country"
                                                   value="<?= e($country) ?>"
                                                   autocomplete="country-name"
                                                   maxlength="100"
                                                   required>
                                            <?php if ($countryErr !== ""): ?>
                                                <div class="invalid-feedback"><?= e($countryErr) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- PASSWORD -->
                                        <div class="col-12 col-md-6">
                                            <label for="formPassword" class="form-label">Password</label>
                                            <div class="password-wrapper">
                                                <input type="password"
                                                       name="password"
                                                       id="formPassword"
                                                       class="form-control register-input <?= $passwordErr !== "" ? 'is-invalid' : '' ?>"
                                                       placeholder="Enter password"
                                                       autocomplete="new-password"
                                                       maxlength="255"
                                                       required>
                                                <button type="button"
                                                        class="password-toggle"
                                                        data-target="formPassword"
                                                        aria-label="Show password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                            <?php if ($passwordErr !== ""): ?>
                                                <div class="invalid-feedback d-block"><?= e($passwordErr) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- CONFIRM PASSWORD -->
                                        <div class="col-12 col-md-6">
                                            <label for="formConfirmPassword" class="form-label">Confirm Password</label>
                                            <div class="password-wrapper">
                                                <input type="password"
                                                       name="confirm_password"
                                                       id="formConfirmPassword"
                                                       class="form-control register-input <?= $confirmPasswordErr !== "" ? 'is-invalid' : '' ?>"
                                                       placeholder="Confirm password"
                                                       autocomplete="new-password"
                                                       maxlength="255"
                                                       required>
                                                <button type="button"
                                                        class="password-toggle"
                                                        data-target="formConfirmPassword"
                                                        aria-label="Show password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                            <?php if ($confirmPasswordErr !== ""): ?>
                                                <div class="invalid-feedback d-block"><?= e($confirmPasswordErr) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- AGREE -->
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       name="agree"
                                                       value="1"
                                                       id="policy"
                                                       class="form-check-input <?= $agreeErr !== "" ? 'is-invalid' : '' ?>"
                                                       required>
                                                <label class="form-check-label" for="policy">
                                                    I have read and agree to the
                                                    <a href="#" class="text-decoration-none">Privacy Policy</a>
                                                </label>
                                                <?php if ($agreeErr !== ""): ?>
                                                    <div class="invalid-feedback d-block"><?= e($agreeErr) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- SUBMIT -->
                                        <div class="col-12 d-grid">
                                            <button type="submit" class="btn btn-primary register-button">
                                                Create Account
                                            </button>
                                        </div>

                                        <!-- LOGIN LINK -->
                                        <div class="col-12 text-center">
                                            <span class="text-muted small">Already registered?</span>
                                            <a href="login.php" class="login-link">Sign in</a>
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
                                    <li><a href="login.php">Login</a></li>
                                    <li><a href="register.php">Register</a></li>
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

    <script>
        /* Password show/hide for both fields */
        document.querySelectorAll(".password-toggle").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var targetId = btn.getAttribute("data-target");
                var input    = document.getElementById(targetId);
                var icon     = btn.querySelector("i");

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