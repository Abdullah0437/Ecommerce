<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Admin Panel</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            min-height: 100vh;
            background: #0f172a;
            transition: 0.3s ease;
            overflow: hidden;
        }

        .brand {
            display: flex !important;
            align-items: center;
            padding: 24px 20px !important;
            margin: 0 !important;
            border-radius: 0 !important;
            color: white !important;
            font-size: 21px;
            font-weight: bold;
            text-decoration: none;
        }

        .brand i {
            color: #60a5fa;
            font-size: 22px;
        }

        .sidebar hr {
            border-color: #334155;
            opacity: 0.5;
            margin: 10px 15px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 13px 16px;
            margin: 5px 10px;
            border-radius: 8px;
            color: #cbd5e1;
            text-decoration: none;
            transition: 0.2s;
        }

        .sidebar a i {
            width: 25px;
            font-size: 16px;
        }

        .sidebar a:hover {
            background: #1e293b;
            color: white;
        }

        .sidebar a.active {
            background: #2563eb;
            color: white;
        }

        .sidebar a.text-danger {
            color: #f87171 !important;
        }

        .sidebar a.text-danger:hover {
            background: #3f1d24;
            color: #fca5a5 !important;
        }

        /* =========================
           COLLAPSED SIDEBAR
        ========================= */

        .sidebar.collapsed {
            display: none;
        }

        .sidebar.collapsed .sidebar-text {
            display: none;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding: 13px 0;
        }

        .sidebar.collapsed a i {
            margin: 0 !important;
        }

        .sidebar.collapsed .brand {
            justify-content: center;
            padding: 24px 0 !important;
        }

        /* =========================
           TOP HEADER
        ========================= */

        .top-header {
            height: 70px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 25px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .top-header h5 {
            color: #0f172a;
            font-weight: 600;
        }

        .welcome-text {
            color: #64748b;
        }

        .welcome-text strong {
            color: #2563eb;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            padding: 25px;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
        }

        .page-title {
            margin: 0;
            color: #0f172a;
            font-size: 24px;
            font-weight: 700;
        }

        .page-header p {
            color: #64748b;
        }

        /* =========================
           CARDS
        ========================= */

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
        }

        .dashboard-card {
            border: none;
            border-radius: 12px;
            background: white;
            transition: 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .dashboard-card h2 {
            color: #0f172a;
            font-weight: 700;
        }

        /* =========================
           DASHBOARD ICON
        ========================= */

        .dashboard-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        /* =========================
           TABLES
        ========================= */

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            white-space: nowrap;
            border-bottom: 1px solid #e2e8f0;
        }

        .table td {
            vertical-align: middle;
            color: #334155;
            border-color: #e2e8f0;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* =========================
           PRODUCT IMAGE
        ========================= */

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .no-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 12px;
        }

        /* =========================
           FORMS
        ========================= */

        .form-label {
            color: #334155;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        textarea.form-control {
            min-height: 120px;
        }

        /* =========================
           BUTTONS
        ========================= */

        .btn {
            border-radius: 7px;
            font-weight: 500;
        }

        .btn-primary {
            background: #2563eb;
            border-color: #2563eb;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        /* =========================
           BADGES
        ========================= */

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
        }

        .action-buttons {
            white-space: nowrap;
        }

        /* =========================
           SEARCH
        ========================= */

        .search-box {
            width: 600px;
            max-width: 100%;
        }

        /* =========================
           ALERTS
        ========================= */

        .alert {
            border: none;
            border-radius: 9px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 767px) {

            .sidebar {
                min-height: auto;
            }

            .top-header {
                height: auto;
                padding: 15px;
            }

            .main-content {
                padding: 15px;
            }

            .page-header {
                padding: 17px;
            }

            .page-title {
                font-size: 20px;
            }

            .welcome-text {
                display: none;
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

            <!-- TOP HEADER -->

            <div class="top-header">

                <div class="d-flex justify-content-between align-items-center">

                    <div class="d-flex align-items-center gap-2">

                        <button
                            type="button"
                            class="btn btn-primary"
                            id="sidebarToggle">

                            <i class="fa-solid fa-bars"></i>

                        </button>

                        <h5 class="mb-0">
                            Admin Panel
                        </h5>

                    </div>

                    <div class="welcome-text">

                        Welcome,

                        <strong>
                            <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                        </strong>

                    </div>

                </div>

            </div>

            <!-- MAIN CONTENT START -->

            <div class="main-content">