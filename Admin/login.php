
<?php

/* =========================================================
   FURNISHOP ADMIN LOGIN
========================================================= */

session_start();

/* =========================================================
   DATABASE
========================================================= */

$host     = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($connect, "utf8mb4");


/* =========================================================
   ALREADY LOGGED IN
========================================================= */

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}


/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["login_csrf"])) {
    $_SESSION["login_csrf"] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION["login_csrf"];


/* =========================================================
   VARIABLES
========================================================= */

$error = "";
$email = "";


/* =========================================================
   HANDLE LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* -----------------------------------------------------
       CSRF VALIDATION
    ----------------------------------------------------- */

    if (
        empty($_POST["csrf_token"]) ||
        empty($_SESSION["login_csrf"]) ||
        !hash_equals($_SESSION["login_csrf"], $_POST["csrf_token"])
    ) {
        $error = "Your session has expired. Please refresh the page and try again.";
    }


    /* -----------------------------------------------------
       INPUT
    ----------------------------------------------------- */

    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    /* -----------------------------------------------------
       VALIDATION
    ----------------------------------------------------- */

    if ($error === "") {

        if ($email === "" || $password === "") {

            $error = "Please enter your email address and password.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            /* -------------------------------------------------
               FIND ADMIN
            ------------------------------------------------- */

            $sql = "SELECT id, name, email, password, status
                    FROM admins
                    WHERE email = ?
                    LIMIT 1";

            $stmt = mysqli_prepare($connect, $sql);

            if (!$stmt) {

                $error = "Something went wrong. Please try again.";

            } else {

                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                $admin = $result
                    ? mysqli_fetch_assoc($result)
                    : null;

                mysqli_stmt_close($stmt);


                /* ---------------------------------------------
                   CHECK LOGIN
                --------------------------------------------- */

                if (!$admin) {

                    $error = "Invalid email or password.";

                } elseif (!password_verify($password, $admin["password"])) {

                    $error = "Invalid email or password.";

                } else {

                    $status = strtolower(trim($admin["status"]));

                    if ($status !== "active") {

                        $error = "Your admin account is not active.";

                    } else {

                        /* -------------------------------------
                           LOGIN SUCCESS
                        ------------------------------------- */

                        session_regenerate_id(true);

                        $_SESSION["admin_id"]    = $admin["id"];
                        $_SESSION["admin_name"]  = $admin["name"];
                        $_SESSION["admin_email"] = $admin["email"];

                        unset($_SESSION["login_csrf"]);

                        $_SESSION["flash_success"] =
                            "Welcome back, " . $admin["name"] . "!";

                        header("Location: dashboard.php");
                        exit;
                    }
                }
            }
        }
    }
}

mysqli_close($connect);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Furnishop Admin Login">

    <title>Admin Login | Furnishop</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">


    <style>

        /* =================================================
           GLOBAL
        ================================================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }


        /* =================================================
           MAIN LAYOUT
        ================================================= */

        .login-page {
            min-height: 100vh;
            display: flex;
        }


        /* =================================================
           LEFT BRAND SECTION
        ================================================= */

        .brand-section {
            position: relative;
            width: 56%;
            min-height: 100vh;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            padding: 48px 65px;

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    rgba(15, 23, 42, 0.97),
                    rgba(30, 41, 59, 0.94) 50%,
                    rgba(79, 70, 229, 0.88)
                ),
                url("../Assets/Images/breadcrumb/breadcrumb-bg.jpg");

            background-size: cover;
            background-position: center;
            overflow: hidden;
        }


        /* Decorative circles */

        .brand-section::before {
            content: "";
            position: absolute;

            width: 420px;
            height: 420px;

            border-radius: 50%;

            background: rgba(129, 140, 248, 0.08);

            top: -180px;
            right: -120px;
        }

        .brand-section::after {
            content: "";

            position: absolute;

            width: 300px;
            height: 300px;

            border-radius: 50%;

            border: 1px solid rgba(255, 255, 255, 0.08);

            bottom: -130px;
            left: -100px;
        }


        /* =================================================
           BRAND HEADER
        ================================================= */

        .brand-header {
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;

            color: #ffffff;
            text-decoration: none;

            font-size: 25px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .brand-logo-icon {
            width: 46px;
            height: 46px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: rgba(255, 255, 255, 0.10);

            border: 1px solid rgba(255, 255, 255, 0.16);

            color: #c7d2fe;

            backdrop-filter: blur(10px);

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.15);
        }


        /* Admin badge */

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            margin-top: 20px;

            padding: 7px 13px;

            border-radius: 50px;

            font-size: 10px;
            font-weight: 600;

            letter-spacing: 1px;
            text-transform: uppercase;

            color: #c7d2fe;

            background: rgba(99, 102, 241, 0.16);

            border: 1px solid rgba(165, 180, 252, 0.20);
        }


        /* =================================================
           BRAND CONTENT
        ================================================= */

        .brand-content {
            position: relative;
            z-index: 2;

            max-width: 570px;

            margin: auto 0;
        }

        .brand-content h1 {
            margin: 0 0 20px;

            font-size: clamp(36px, 4vw, 54px);

            line-height: 1.12;

            font-weight: 700;

            letter-spacing: -1.8px;
        }

        .brand-content h1 span {
            color: #a5b4fc;
        }

        .brand-description {
            max-width: 500px;

            margin: 0;

            color: rgba(255, 255, 255, 0.72);

            font-size: 15px;

            line-height: 1.8;
        }


        /* =================================================
           FEATURES
        ================================================= */

        .features {
            margin-top: 38px;

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 14px;
        }

        .feature {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 13px 14px;

            border-radius: 12px;

            background: rgba(255, 255, 255, 0.055);

            border: 1px solid rgba(255, 255, 255, 0.08);

            backdrop-filter: blur(8px);

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .feature:hover {
            background: rgba(255, 255, 255, 0.09);

            transform: translateY(-2px);
        }

        .feature-icon {
            width: 35px;
            height: 35px;

            min-width: 35px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            color: #c7d2fe;

            background: rgba(99, 102, 241, 0.17);

            font-size: 13px;
        }

        .feature span {
            font-size: 11.5px;

            line-height: 1.45;

            color: rgba(255, 255, 255, 0.82);
        }


        /* =================================================
           BRAND FOOTER
        ================================================= */

        .brand-footer {
            position: relative;
            z-index: 2;

            font-size: 11px;

            color: rgba(255, 255, 255, 0.45);
        }


        /* =================================================
           RIGHT LOGIN SECTION
        ================================================= */

        .login-section {
            width: 44%;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 50px;

            background: #ffffff;
        }


        /* =================================================
           LOGIN CARD
        ================================================= */

        .login-card {
            width: 100%;
            max-width: 410px;
        }


        /* Top icon */

        .login-icon {
            width: 54px;
            height: 54px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 23px;

            border-radius: 15px;

            color: #4f46e5;

            background: #eef2ff;

            font-size: 20px;
        }


        /* Title */

        .login-title {
            margin: 0;

            font-size: 29px;

            font-weight: 700;

            letter-spacing: -0.7px;

            color: #0f172a;
        }

        .login-subtitle {
            margin: 8px 0 30px;

            font-size: 13px;

            line-height: 1.6;

            color: #64748b;
        }


        /* =================================================
           ERROR MESSAGE
        ================================================= */

        .login-error {
            display: flex;
            align-items: flex-start;

            gap: 11px;

            padding: 13px 14px;

            margin-bottom: 22px;

            border-radius: 11px;

            background: #fff1f2;

            border: 1px solid #fecdd3;

            color: #be123c;

            font-size: 12px;

            line-height: 1.5;
        }

        .login-error i {
            margin-top: 2px;

            font-size: 14px;
        }


        /* =================================================
           FORM
        ================================================= */

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 8px;

            color: #334155;

            font-size: 12px;

            font-weight: 600;
        }

        .form-control-wrapper {
            position: relative;
        }

        .form-control-custom {
            width: 100%;

            height: 48px;

            padding: 0 14px;

            border: 1px solid #e2e8f0;

            border-radius: 11px;

            outline: none;

            color: #0f172a;

            background: #ffffff;

            font-family: "Poppins", sans-serif;

            font-size: 13px;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .form-control-custom::placeholder {
            color: #a1aab8;
        }

        .form-control-custom:hover {
            border-color: #cbd5e1;
        }

        .form-control-custom:focus {
            border-color: #6366f1;

            background: #ffffff;

            box-shadow:
                0 0 0 4px rgba(99, 102, 241, 0.10);
        }


        /* Password field */

        .password-input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;

            top: 50%;
            right: 12px;

            width: 30px;
            height: 30px;

            transform: translateY(-50%);

            display: flex;
            align-items: center;
            justify-content: center;

            border: none;

            border-radius: 7px;

            color: #94a3b8;

            background: transparent;

            cursor: pointer;

            transition:
                color 0.2s ease,
                background 0.2s ease;
        }

        .password-toggle:hover {
            color: #4f46e5;

            background: #eef2ff;
        }


        /* =================================================
           LOGIN BUTTON
        ================================================= */

        .btn-login {
            width: 100%;

            height: 49px;

            margin-top: 3px;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 9px;

            border: none;

            border-radius: 11px;

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #1e293b,
                    #4338ca
                );

            font-family: "Poppins", sans-serif;

            font-size: 13px;

            font-weight: 600;

            cursor: pointer;

            box-shadow:
                0 8px 18px rgba(67, 56, 202, 0.18);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                opacity 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);

            box-shadow:
                0 12px 25px rgba(67, 56, 202, 0.25);
        }

        .btn-login:active {
            transform: translateY(0);
        }


        /* =================================================
           SECURITY NOTE
        ================================================= */

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            margin-top: 19px;

            color: #94a3b8;

            font-size: 10.5px;
        }

        .security-note i {
            color: #10b981;
        }


        /* =================================================
           BACK TO STORE
        ================================================= */

        .back-store {
            display: flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            margin-top: 25px;

            color: #64748b;

            font-size: 12px;

            text-decoration: none;

            transition: color 0.2s ease;
        }

        .back-store:hover {
            color: #4f46e5;
        }


        /* =================================================
           RESPONSIVE - TABLET
        ================================================= */

        @media (max-width: 1100px) {

            .brand-section {
                width: 50%;

                padding: 40px;
            }

            .login-section {
                width: 50%;

                padding: 35px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .brand-content h1 {
                font-size: 40px;
            }
        }


        /* =================================================
           RESPONSIVE - MOBILE
        ================================================= */

        @media (max-width: 768px) {

            .login-page {
                min-height: 100vh;
            }

            .brand-section {
                display: none;
            }

            .login-section {
                width: 100%;

                min-height: 100vh;

                padding: 30px 22px;

                background:
                    linear-gradient(
                        135deg,
                        #f8fafc,
                        #eef2ff
                    );
            }

            .login-card {
                max-width: 420px;

                padding: 30px 24px;

                border-radius: 18px;

                background: #ffffff;

                box-shadow:
                    0 15px 45px rgba(15, 23, 42, 0.08);
            }
        }


        /* =================================================
           SMALL MOBILE
        ================================================= */

        @media (max-width: 400px) {

            .login-section {
                padding: 18px 14px;
            }

            .login-card {
                padding: 25px 19px;
            }

            .login-title {
                font-size: 25px;
            }
        }

    </style>

</head>


<body>


<div class="login-page">


    <!-- =====================================================
         LEFT BRAND SECTION
    ====================================================== -->

    <section class="brand-section">


        <!-- BRAND HEADER -->

        <div class="brand-header">

            <a
                href="../index.php"
                class="brand-logo">

                <span class="brand-logo-icon">

                    <i class="fa-solid fa-couch"></i>

                </span>

                <span>Furnishop</span>

            </a>


            <div>

                <span class="admin-badge">

                    <i class="fa-solid fa-shield-halved"></i>

                    Admin Panel

                </span>

            </div>

        </div>



        <!-- BRAND CONTENT -->

        <div class="brand-content">


            <h1>

                Manage your store
                <span>with confidence.</span>

            </h1>


            <p class="brand-description">

                Everything you need to manage your Furnishop
                store in one simple and secure dashboard.

            </p>


            <div class="features">


                <div class="feature">

                    <div class="feature-icon">

                        <i class="fa-solid fa-chart-line"></i>

                    </div>

                    <span>

                        Sales overview &amp;
                        order tracking

                    </span>

                </div>


                <div class="feature">

                    <div class="feature-icon">

                        <i class="fa-solid fa-box"></i>

                    </div>

                    <span>

                        Product &amp;
                        stock management

                    </span>

                </div>


                <div class="feature">

                    <div class="feature-icon">

                        <i class="fa-solid fa-layer-group"></i>

                    </div>

                    <span>

                        Categories &amp;
                        inventory control

                    </span>

                </div>


                <div class="feature">

                    <div class="feature-icon">

                        <i class="fa-solid fa-users"></i>

                    </div>

                    <span>

                        Customer accounts &
                        order history

                    </span>

                </div>


            </div>

        </div>



        <!-- FOOTER -->

        <div class="brand-footer">

            &copy;
            <?php echo date("Y"); ?>
            Furnishop.
            All rights reserved.

        </div>


    </section>



    <!-- =====================================================
         RIGHT LOGIN SECTION
    ====================================================== -->

    <main class="login-section">


        <div class="login-card">


            <!-- LOGIN ICON -->

            <div class="login-icon">

                <i class="fa-solid fa-lock"></i>

            </div>


            <!-- TITLE -->

            <h2 class="login-title">

                Welcome back

            </h2>


            <p class="login-subtitle">

                Sign in to your admin account
                to continue to the dashboard.

            </p>



            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div
                    class="login-error"
                    role="alert">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>

                        <?php echo htmlspecialchars($error); ?>

                    </span>

                </div>

            <?php endif; ?>



            <!-- LOGIN FORM -->

            <form
                method="post"
                autocomplete="on">


                <!-- CSRF -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($csrf); ?>">



                <!-- EMAIL -->

                <div class="form-group">

                    <label
                        for="email"
                        class="form-label">

                        <span>Email Address</span>

                    </label>


                    <div class="form-control-wrapper">

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control-custom"
                            value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="admin@example.com"
                            autocomplete="username"
                            required
                            autofocus>

                    </div>

                </div>



                <!-- PASSWORD -->

                <div class="form-group">

                    <label
                        for="password"
                        class="form-label">

                        <span>Password</span>

                    </label>


                    <div class="form-control-wrapper">

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control-custom password-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required>


                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                            title="Show password">

                            <i
                                class="fa-solid fa-eye"
                                id="toggleIcon">
                            </i>

                        </button>

                    </div>

                </div>



                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="btn-login">

                    <i class="fa-solid fa-arrow-right-to-bracket"></i>

                    <span>Sign In to Dashboard</span>

                </button>


            </form>

        </div>


    </main>


</div>



<!-- =========================================================
     PASSWORD TOGGLE
========================================================= -->

<script>

(function () {

    const button = document.getElementById("togglePassword");
    const input  = document.getElementById("password");
    const icon   = document.getElementById("toggleIcon");

    if (!button || !input || !icon) {
        return;
    }

    button.addEventListener("click", function () {

        const isPassword =
            input.type === "password";

        input.type =
            isPassword ? "text" : "password";

        icon.className =
            isPassword
                ? "fa-solid fa-eye-slash"
                : "fa-solid fa-eye";

        button.setAttribute(
            "aria-label",
            isPassword
                ? "Hide password"
                : "Show password"
        );

        button.setAttribute(
            "title",
            isPassword
                ? "Hide password"
                : "Show password"
        );

    });

})();

</script>


</body>

</html>