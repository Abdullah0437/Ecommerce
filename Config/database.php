<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
