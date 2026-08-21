<?php



?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Admin Panel</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <style>
        body {
            background-color: #f5f6fa;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            transition: all 0.3s ease;
            overflow: hidden;
        }


        .sidebar .brand {
            display: block;
            padding: 20px;
            color: white;
            text-decoration: none;
            font-size: 20px;
            font-weight: bold;
            white-space: nowrap;
        }


        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #adb5bd;
            text-decoration: none;
            white-space: nowrap;
        }


        .sidebar a:hover {
            background-color: #343a40;
            color: white;
        }


        .sidebar.collapsed {
            display: none;
        }


        .sidebar.collapsed .sidebar-text {
            display: none;
        }


        .sidebar.collapsed a {
            text-align: center;
            padding: 12px 0;
        }


        .sidebar.collapsed .me-2 {
            margin-right: 0 !important;
        }


        .sidebar.collapsed .brand {
            text-align: center;
            padding: 20px 0;
        }


    

        .top-header {
            background-color: white;
            padding: 15px 25px;
            border-bottom: 1px solid #ddd;
        }


    

        .main-content {
            padding: 25px;
        }


      

        .card {
            border: none;
        }


     

        .page-header {
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }


        .page-title {
            font-weight: 600;
            margin: 0;
        }


        .product-card {
            border-radius: 12px;
        }


        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }


        .no-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background-color: #eeeeee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 12px;
        }


        .table th {
            white-space: nowrap;
            background-color: #f8f9fa;
        }


        .table td {
            vertical-align: middle;
        }


        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
        }


        .action-buttons {
            white-space: nowrap;
        }


        .search-box {
            width: 600px;
            max-width: 100%;
        }

        .dashboard-card {
            border: none;
            border-radius: 12px;
        }

        .dashboard-card h2 {
            font-weight: bold;
        }


     

        @media (max-width: 767px) {

            .sidebar {
                min-height: auto;
            }

            .top-header {
                padding: 15px;
            }

            .main-content {
                padding: 15px;
            }

            .page-header {
                padding: 15px;
            }

            .search-box {
                width: 100%;
            }

        }
    </style>

</head>


<body>


    <div class="container-fluid">

        <div class="row">

            <?php include "sidebar.php"; ?>



            <div class="col px-0">


                <div class="top-header">

                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-2">


                        <div class="d-flex align-items-center gap-2">


                            <button
                                type="button"
                                class="btn btn-dark"
                                id="sidebarToggle">

                                <i class="fa-solid fa-bars"></i>

                            </button>


                            <h5 class="mb-0">

                                Admin Panel

                            </h5>

                        </div>


                        <span>

                            Welcome,

                            <strong>

                                <?= htmlspecialchars(
                                    $_SESSION['admin_name']
                                ) ?>

                            </strong>

                        </span>


                    </div>

                </div>


            

                <div class="main-content">