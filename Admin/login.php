<?php

session_start();

/* DATABASE CONNECTION */
$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

/* ALREADY LOGGED IN */
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

/* LOGIN */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = "Email and password are required.";
    } else {

        $sql = "SELECT id, name, email, password, status
                FROM admins
                WHERE email = ?
                LIMIT 1";

        $stmt = mysqli_prepare($connect, $sql);

        if (!$stmt) {
            die("SQL Error: " . mysqli_error($connect));
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($admin = mysqli_fetch_assoc($result)) {

            /* VERIFY PASSWORD FIRST */
            if (!password_verify($password, $admin['password'])) {

                $error = "Invalid email or password.";
            } else {

                /* CHECK STATUS */
                $status = strtolower(trim($admin['status']));

                if ($status !== 'active') {

                    $error = "Your admin account is not active.";
                } else {

                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];

                    header("Location: dashboard.php");
                    exit;
                }
            }
        } else {

            $error = "Invalid email or password.";
        }

        mysqli_stmt_close($stmt);
    }
}

mysqli_close($connect);

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container">

        <div class="row justify-content-center mt-5">

            <div class="col-md-5">

                <div class="card shadow">

                    <div class="card-body p-4">

                        <h2 class="text-center mb-4">
                            Admin Login
                        </h2>

                        <?php if (!empty($error)) : ?>

                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>

                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label class="form-label">Email</label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>

                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Login
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>