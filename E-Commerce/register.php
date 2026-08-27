<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$nameErr = "";
$emailErr = "";
$phoneErr = "";
$passwordErr = "";
$confirmPasswordErr = "";
$addressErr = "";
$cityErr = "";
$countryErr = "";

$message = "";

$name = "";
$email = "";
$phone = "";
$address = "";
$city = "";
$country = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $userPassword = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];
    $address = trim($_POST["address"]);
    $city = trim($_POST["city"]);
    $country = trim($_POST["country"]);


   
    if (empty($name)) {
        $nameErr = "Name is required";
    }


    if (empty($email)) {
        $emailErr = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Enter a valid email";
    } else {

        $emailCheck = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($connect, $emailCheck);

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $emailErr = "Email already exists";
        }

        mysqli_stmt_close($stmt);
    }


   
    if (empty($phone)) {
        $phoneErr = "Phone is required";
    }


  
    if (empty($userPassword)) {
        $passwordErr = "Password is required";
    } elseif (strlen($userPassword) < 6) {
        $passwordErr = "Password must be at least 6 characters";
    }


   
    if (empty($confirmPassword)) {
        $confirmPasswordErr = "Please confirm your password";
    } elseif ($userPassword != $confirmPassword) {
        $confirmPasswordErr = "Passwords do not match";
    }


    
    if (empty($address)) {
        $addressErr = "Address is required";
    }


    
    if (empty($city)) {
        $cityErr = "City is required";
    }


    
    if (empty($country)) {
        $countryErr = "Country is required";
    }


    
    if (
        empty($nameErr) &&
        empty($emailErr) &&
        empty($phoneErr) &&
        empty($passwordErr) &&
        empty($confirmPasswordErr) &&
        empty($addressErr) &&
        empty($cityErr) &&
        empty($countryErr)
    ) {

        $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

        $status = "Active";

        $insertQuery = "INSERT INTO users 
                        (NAME, email, phone, PASSWORD, address, city, country, STATUS)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($connect, $insertQuery);

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssss",
            $name,
            $email,
            $phone,
            $hashedPassword,
            $address,
            $city,
            $country,
            $status
        );

        if (mysqli_stmt_execute($stmt)) {

            $message = "Registration successful! You can now login.";

            $name = "";
            $email = "";
            $phone = "";
            $address = "";
            $city = "";
            $country = "";
        } else {
            $message = "Something went wrong. Please try again.";
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

    <title>Register - E-Commerce</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-md-8 col-lg-6">

                <div class="card shadow">

                    <div class="card-body p-4">

                        <h2 class="text-center mb-4">
                            Create Account
                        </h2>


                        <?php if (!empty($message)) { ?>

                            <div class="alert alert-success">
                                <?php echo $message; ?>
                            </div>

                        <?php } ?>


                        <form method="POST" action="">

                           

                            <div class="mb-3">

                                <label class="form-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($name); ?>">

                                <?php if (!empty($nameErr)) { ?>
                                    <small class="text-danger">
                                        <?php echo $nameErr; ?>
                                    </small>
                                <?php } ?>

                            </div>


                           

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
                                    Phone
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($phone); ?>">

                                <?php if (!empty($phoneErr)) { ?>
                                    <small class="text-danger">
                                        <?php echo $phoneErr; ?>
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


                      

                            <div class="mb-3">

                                <label class="form-label">
                                    Confirm Password
                                </label>

                                <input
                                    type="password"
                                    name="confirm_password"
                                    class="form-control">

                                <?php if (!empty($confirmPasswordErr)) { ?>
                                    <small class="text-danger">
                                        <?php echo $confirmPasswordErr; ?>
                                    </small>
                                <?php } ?>

                            </div>


                            

                            <div class="mb-3">

                                <label class="form-label">
                                    Address
                                </label>

                                <textarea
                                    name="address"
                                    class="form-control"
                                    rows="3"><?php echo htmlspecialchars($address); ?></textarea>

                                <?php if (!empty($addressErr)) { ?>
                                    <small class="text-danger">
                                        <?php echo $addressErr; ?>
                                    </small>
                                <?php } ?>

                            </div>


                          

                            <div class="mb-3">

                                <label class="form-label">
                                    City
                                </label>

                                <input
                                    type="text"
                                    name="city"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($city); ?>">

                                <?php if (!empty($cityErr)) { ?>
                                    <small class="text-danger">
                                        <?php echo $cityErr; ?>
                                    </small>
                                <?php } ?>

                            </div>


                          

                            <div class="mb-3">

                                <label class="form-label">
                                    Country
                                </label>

                                <input
                                    type="text"
                                    name="country"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($country); ?>">

                                <?php if (!empty($countryErr)) { ?>
                                    <small class="text-danger">
                                        <?php echo $countryErr; ?>
                                    </small>
                                <?php } ?>

                            </div>


                        

                            <button
                                type="submit"
                                class="btn btn-primary w-100">
                                Register
                            </button>


                            <p class="text-center mt-3 mb-0">

                                Already have an account?

                                <a href="login.php">
                                    Login
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