<?php

session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$emailErr = "";
$passwordErr = "";
$loginErr = "";

$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $userPassword = $_POST["password"];


    if (empty($email)) {
        $emailErr = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Enter a valid email";
    }

    if (empty($userPassword)) {
        $passwordErr = "Password is required";
    }


    if (empty($emailErr) && empty($passwordErr)) {

        $query = "SELECT id, NAME, email, PASSWORD, STATUS
                  FROM users
                  WHERE email = ?
                  LIMIT 1";

        $stmt = mysqli_prepare($connect, $query);

        mysqli_stmt_bind_param($stmt, "s", $email);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            if ($user["STATUS"] != "Active") {

                $loginErr = "Your account is inactive.";
            } elseif (password_verify($userPassword, $user["PASSWORD"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["NAME"];
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
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - E-Commerce</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-md-6 col-lg-5">

                <div class="card shadow">

                    <div class="card-body p-4">

                        <h2 class="text-center mb-4">
                            Customer Login
                        </h2>


                        <?php if (!empty($loginErr)) { ?>

                            <div class="alert alert-danger">
                                <?php echo $loginErr; ?>
                            </div>

                        <?php } ?>


                        <form method="POST" action="">



                            <div class="mb-3">

                                <label class="form-label">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($email); ?>">

                                <?php if (!empty($emailErr)) { ?>

                                    <small class="text-danger">
                                        <?php echo $emailErr; ?>
                                    </small>

                                <?php } ?>

                            </div>




                            <div class="mb-3">

                                <label class="form-label">
                                    Password
                                </label>

                                <input
                                    type="password"
                                    name="password"
                                    class="form-control">

                                <?php if (!empty($passwordErr)) { ?>

                                    <small class="text-danger">
                                        <?php echo $passwordErr; ?>
                                    </small>

                                <?php } ?>

                            </div>




                            <button
                                type="submit"
                                class="btn btn-primary w-100">
                                Login
                            </button>


                            <p class="text-center mt-3 mb-0">

                                Don't have an account?

                                <a href="register.php">
                                    Register
                                </a>

                            </p>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>