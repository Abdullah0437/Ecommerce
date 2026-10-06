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
   LOGIN FORM PROCESSING
========================================================= */

$csrfOk = true;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !customer_csrf_valid($_POST["csrf_token"] ?? null)) {
    $csrfOk   = false;
    $loginErr = "Your session expired. Please try again.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $csrfOk) {

    $email        = trim($_POST["email"] ?? "");
    $userPassword = $_POST["password"] ?? "";

    /* Email Validation */
    if ($email === "") {
        $emailErr = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Enter a valid email address.";
    }

    /* Password Validation */
    if ($userPassword === "") {
        $passwordErr = "Password is required.";
    }

    /* Database Authentication */
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

                if ($user["STATUS"] !== "Active") {

                    $loginErr = "Your account is inactive. Please contact support.";
                } elseif (password_verify($userPassword, $user["PASSWORD"])) {

                    session_regenerate_id(true);

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
   HELPERS
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Furnishop</title>
    <meta name="description" content="Login to your Furnishop customer account">
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
        /* LOGIN CARD */
        .login-section {
            background: #f7f8fa;
            padding: 60px 0;
            min-height: 500px;
        }
        .login-card {
            border: 1px solid #e8eaee;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .login-card::before {
            content: "";
            display: block;
            height: 4px;
            background: var(--theme-default);
        }
        .login-card .card-body {
            padding: 36px 32px;
        }
        .login-heading {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a1f29;
            margin-bottom: 6px;
        }
        .login-subtext {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .login-input {
            min-height: 44px;
            border: 1px solid #d8dce3;
            border-radius: 8px;
            font-size: 0.92rem;
        }
        .login-input:focus {
            border-color: var(--theme-default);
            box-shadow: 0 0 0 3px rgba(140, 89, 59, 0.12);
        }

        .password-wrapper { position: relative; }
        .password-wrapper .login-input { padding-right: 46px; }
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

        .login-button {
            min-height: 46px;
            border-radius: 8px;
            font-weight: 500;
        }

        .register-link {
            color: var(--theme-default);
            font-weight: 500;
            text-decoration: none;
        }
        .register-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 767px) {
            .login-section { padding: 35px 0; }
            .login-card .card-body { padding: 28px 20px; }
            .login-heading { font-size: 1.15rem; }
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
                    <h2 class="page-title">Login</h2>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
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

                                <div class="mb-4">
                                    <h1 class="login-heading">Registered Customers</h1>
                                    <p class="login-subtext">
                                        Sign in with your email to access your account.
                                    </p>
                                </div>

                                <?php if ($loginErr !== ""): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?= e($loginErr) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="<?= e($_SERVER["PHP_SELF"]) ?>" novalidate>

                                    <input type="hidden" name="csrf_token" value="<?= e(customer_csrf_token()) ?>">

                                    <!-- EMAIL -->
                                    <div class="mb-3">
                                        <label for="formSigninEmail" class="form-label">Email</label>
                                        <input type="email"
                                               name="email"
                                               id="formSigninEmail"
                                               class="form-control login-input <?= $emailErr !== "" ? 'is-invalid' : '' ?>"
                                               placeholder="Enter your email"
                                               value="<?= e($email) ?>"
                                               autocomplete="email"
                                               maxlength="150"
                                               required>
                                        <?php if ($emailErr !== ""): ?>
                                            <div class="invalid-feedback"><?= e($emailErr) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- PASSWORD -->
                                    <div class="mb-3">
                                        <label for="formSigninPassword" class="form-label">Password</label>
                                        <div class="password-wrapper">
                                            <input type="password"
                                                   name="password"
                                                   id="formSigninPassword"
                                                   class="form-control login-input <?= $passwordErr !== "" ? 'is-invalid' : '' ?>"
                                                   placeholder="Enter your password"
                                                   autocomplete="current-password"
                                                   maxlength="255"
                                                   required>
                                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password">
                                                <i class="bi bi-eye" id="passwordIcon"></i>
                                            </button>
                                        </div>
                                        <?php if ($passwordErr !== ""): ?>
                                            <div class="invalid-feedback d-block"><?= e($passwordErr) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- LOGIN BUTTON -->
                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary login-button">
                                            Sign In
                                        </button>
                                    </div>

                                    <!-- REGISTER LINK -->
                                    <div class="text-center">
                                        <span class="text-muted small">New customer?</span>
                                        <a href="register.php" class="register-link">Create an account</a>
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
        (function () {
            var togglePassword = document.getElementById("togglePassword");
            var passwordInput  = document.getElementById("formSigninPassword");
            var passwordIcon   = document.getElementById("passwordIcon");

            if (togglePassword && passwordInput && passwordIcon) {
                togglePassword.addEventListener("click", function () {
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
        })();
    </script>
</body>
</html>