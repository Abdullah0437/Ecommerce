<?php

/* =========================================================
   ADMIN - MY PROFILE
   Furnishop Admin Panel
========================================================= */

require_once __DIR__ . "/../Includes/auth.php";

if (!isset($connect)) {
    $host = "localhost"; $username = "root"; $password = ""; $database = "ecommerce";
    $connect = mysqli_connect($host, $username, $password, $database);
    if (!$connect) die("Database Connection Failed: " . mysqli_connect_error());
}


/* =========================================================
   CSRF
========================================================= */

if (empty($_SESSION["admin_csrf"])) {
    $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION["admin_csrf"];


/* =========================================================
   HELPERS
========================================================= */

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}


/* =========================================================
   LOAD CURRENT ADMIN
   NOTE: admins table uses uppercase NAME and STATUS columns
========================================================= */

$adminId = (int) $_SESSION["admin_id"];

$stmt = mysqli_prepare(
    $connect,
    "SELECT id, NAME AS name, email, STATUS AS status, created_at
     FROM admins
     WHERE id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $adminId);
mysqli_stmt_execute($stmt);
$res   = mysqli_stmt_get_result($stmt);
$admin = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$admin) {
    $_SESSION["flash_error"] = "Admin account not found.";
    header("Location: logout.php");
    exit;
}


/* =========================================================
   VARIABLES
========================================================= */

$profileErrors  = ["name" => "", "email" => ""];
$passwordErrors = ["current_password" => "", "new_password" => "", "confirm_password" => ""];

$name  = $admin["name"];
$email = $admin["email"];


/* =========================================================
   HANDLE: UPDATE PROFILE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {

    if (
        empty($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"])
    ) {
        $profileErrors["name"] = "Invalid session. Please refresh.";
    }

    $name  = trim($_POST["name"]  ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($name === "") {
        $profileErrors["name"] = "Name is required.";
    } elseif (strlen($name) > 100) {
        $profileErrors["name"] = "Name cannot exceed 100 characters.";
    }

    if ($email === "") {
        $profileErrors["email"] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileErrors["email"] = "Please enter a valid email address.";
    } else {
        $chk = mysqli_prepare(
            $connect,
            "SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1"
        );
        mysqli_stmt_bind_param($chk, "si", $email, $adminId);
        mysqli_stmt_execute($chk);
        $chkRes = mysqli_stmt_get_result($chk);

        if ($chkRes && mysqli_num_rows($chkRes) > 0) {
            $profileErrors["email"] = "This email is used by another admin.";
        }
        mysqli_stmt_close($chk);
    }

    $hasErrors = false;
    foreach ($profileErrors as $err) {
        if ($err !== "") { $hasErrors = true; break; }
    }

    if (!$hasErrors) {

        /* NOTE: admins.NAME is uppercase */
        $upd = mysqli_prepare(
            $connect,
            "UPDATE admins SET NAME = ?, email = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($upd, "ssi", $name, $email, $adminId);

        if (mysqli_stmt_execute($upd)) {

            $_SESSION["admin_name"]  = $name;
            $_SESSION["admin_email"] = $email;

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
   HANDLE: CHANGE PASSWORD
   NOTE: admins.PASSWORD is uppercase
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {

    if (
        empty($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"])
    ) {
        $passwordErrors["current_password"] = "Invalid session. Please refresh.";
    }

    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword     = $_POST["new_password"]     ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($currentPassword === "") {
        $passwordErrors["current_password"] = "Current password is required.";
    } else {
        $chk = mysqli_prepare(
            $connect,
            "SELECT PASSWORD AS password FROM admins WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($chk, "i", $adminId);
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
        $passwordErrors["new_password"] = "New password must be at least 6 characters.";
    }

    if ($confirmPassword === "") {
        $passwordErrors["confirm_password"] = "Please confirm the new password.";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordErrors["confirm_password"] = "Passwords do not match.";
    }

    $hasErrors = false;
    foreach ($passwordErrors as $err) {
        if ($err !== "") { $hasErrors = true; break; }
    }

    if (!$hasErrors) {

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        /* NOTE: admins.PASSWORD is uppercase */
        $upd = mysqli_prepare(
            $connect,
            "UPDATE admins SET PASSWORD = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($upd, "si", $newHash, $adminId);

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
   PAGE META + HEADER
========================================================= */

$pageTitle      = "My Profile";
$pageHeading    = "My Profile";
$pageSubheading = "Manage your admin account details and password";

require_once __DIR__ . "/Includes/header.php";
?>


<!-- FLASH -->

<?php if (!empty($_SESSION["flash_success"])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-circle-check me-1"></i>
        <?php echo e($_SESSION["flash_success"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION["flash_success"]); ?>
<?php endif; ?>

<?php if (!empty($_SESSION["flash_error"])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-circle-exclamation me-1"></i>
        <?php echo e($_SESSION["flash_error"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION["flash_error"]); ?>
<?php endif; ?>


<div class="row g-4">

    <!-- LEFT: Account Summary -->

    <div class="col-lg-4">

        <div class="panel">

            <div class="panel-header">
                <h3>
                    <i class="fa-solid fa-user-gear"></i>
                    Account
                </h3>
            </div>

            <div class="panel-body text-center">

                <div style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:36px;margin:0 auto 16px;">
                    <?php echo e(strtoupper(substr($admin["name"], 0, 1))); ?>
                </div>

                <h5 style="font-weight:600;color:#0f172a;margin-bottom:4px;">
                    <?php echo e($admin["name"]); ?>
                </h5>

                <p style="color:#6b7280;font-size:13px;margin-bottom:16px;">
                    <?php echo e($admin["email"]); ?>
                </p>

                <span class="badge-soft-success">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    <?php echo e(ucfirst(strtolower($admin["status"]))); ?> Admin
                </span>

                <div style="margin-top:20px;padding-top:20px;border-top:1px solid #f1f5f9;text-align:left;">

                    <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                        <span style="color:#6b7280;">Admin ID</span>
                        <strong>#<?php echo (int) $admin["id"]; ?></strong>
                    </div>

                    <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                        <span style="color:#6b7280;">Joined</span>
                        <strong>
                            <?php
                            echo !empty($admin["created_at"])
                                ? e(date("d M Y", strtotime($admin["created_at"])))
                                : "-";
                            ?>
                        </strong>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- RIGHT: Profile + Password forms -->

    <div class="col-lg-8">

        <!-- PROFILE -->

        <div class="panel">

            <div class="panel-header">
                <h3>
                    <i class="fa-solid fa-user-pen"></i>
                    Profile Information
                </h3>
            </div>

            <div class="panel-body">

                <form method="post" novalidate>

                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                class="form-control <?php echo $profileErrors["name"] ? "is-invalid" : ""; ?>"
                                value="<?php echo e($name); ?>"
                                required>
                            <?php if ($profileErrors["name"]): ?>
                                <div class="invalid-feedback d-block">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    <?php echo e($profileErrors["name"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input
                                type="email"
                                name="email"
                                class="form-control <?php echo $profileErrors["email"] ? "is-invalid" : ""; ?>"
                                value="<?php echo e($email); ?>"
                                required>
                            <?php if ($profileErrors["email"]): ?>
                                <div class="invalid-feedback d-block">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    <?php echo e($profileErrors["email"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                        </button>
                    </div>

                </form>

            </div>

        </div>


        <!-- CHANGE PASSWORD -->

        <div class="panel">

            <div class="panel-header">
                <h3>
                    <i class="fa-solid fa-lock"></i>
                    Change Password
                </h3>
            </div>

            <div class="panel-body">

                <form method="post" novalidate>

                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">

                    <div class="row g-3">

                        <div class="col-md-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <input
                                type="password"
                                name="current_password"
                                class="form-control <?php echo $passwordErrors["current_password"] ? "is-invalid" : ""; ?>"
                                required>
                            <?php if ($passwordErrors["current_password"]): ?>
                                <div class="invalid-feedback d-block">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    <?php echo e($passwordErrors["current_password"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <input
                                type="password"
                                name="new_password"
                                class="form-control <?php echo $passwordErrors["new_password"] ? "is-invalid" : ""; ?>"
                                required>
                            <?php if ($passwordErrors["new_password"]): ?>
                                <div class="invalid-feedback d-block">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    <?php echo e($passwordErrors["new_password"]); ?>
                                </div>
                            <?php else: ?>
                                <small class="text-muted">Minimum 6 characters.</small>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control <?php echo $passwordErrors["confirm_password"] ? "is-invalid" : ""; ?>"
                                required>
                            <?php if ($passwordErrors["confirm_password"]): ?>
                                <div class="invalid-feedback d-block">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    <?php echo e($passwordErrors["confirm_password"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fa-solid fa-key me-1"></i> Update Password
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<?php require_once __DIR__ . "/Includes/footer.php"; ?>