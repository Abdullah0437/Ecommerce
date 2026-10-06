<?php

/* =========================================================
   ADMIN AUTH GUARD
   Include at the top of every admin page (except login.php).
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/functions.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Admin/login.php");
    exit;
}

/* Make sure this admin still exists and is Active */
$__adminCheck = mysqli_prepare($connect, "SELECT id FROM admins WHERE id = ? AND status = 'Active' LIMIT 1");
mysqli_stmt_bind_param($__adminCheck, "i", $_SESSION['admin_id']);
mysqli_stmt_execute($__adminCheck);
mysqli_stmt_store_result($__adminCheck);
$__adminOk = mysqli_stmt_num_rows($__adminCheck) === 1;
mysqli_stmt_close($__adminCheck);
unset($__adminCheck);

if (!$__adminOk) {
    $_SESSION = [];
    session_destroy();
    header("Location: ../Admin/login.php");
    exit;
}
unset($__adminOk);
