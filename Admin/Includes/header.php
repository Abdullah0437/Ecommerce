<?php
/* =========================================================
   ADMIN HEADER
   Furnishop Admin Panel
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle      = $pageTitle      ?? "Admin Panel";
$pageHeading    = $pageHeading    ?? "Admin Panel";
$pageSubheading = $pageSubheading ?? "";

$adminName = $_SESSION["admin_name"] ?? "Admin";
$adminInitial = strtoupper(substr($adminName, 0, 1));

$currentPage = basename($_SERVER["PHP_SELF"]);
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?> — Furnishop Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6fb;
            margin: 0;
        }

        /* ============================
           LAYOUT
        ============================ */

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #cbd5e1;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            padding: 24px 0;
            overflow-y: auto;
            z-index: 100;
        }

        .admin-sidebar .brand {
            padding: 0 24px 24px;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 16px;
        }

        .admin-sidebar .brand i {
            color: #818cf8;
            margin-right: 8px;
        }

        .admin-sidebar .nav-link {
            color: #cbd5e1;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .admin-sidebar .nav-link:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.05);
        }

        .admin-sidebar .nav-link.active {
            color: #ffffff;
            background: rgba(129, 140, 248, 0.12);
            border-left-color: #818cf8;
        }

        .admin-sidebar .nav-link i {
            width: 18px;
            text-align: center;
        }

        .admin-sidebar .nav-section {
            padding: 18px 24px 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            font-weight: 600;
        }

        .admin-main {
            margin-left: 260px;
            flex: 1;
            min-width: 0;
        }

        /* ============================
           TOPBAR
        ============================ */

        .admin-topbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .admin-topbar .page-heading {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .admin-topbar .page-heading small {
            display: block;
            color: #6b7280;
            font-size: 13px;
            font-weight: 400;
            margin-top: 2px;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .admin-user .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        /* ============================
           CONTENT
        ============================ */

        .admin-content {
            padding: 28px 32px 60px;
        }

        /* ============================
           STAT BOXES
        ============================ */

        .stat-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e9ecef;
            height: 100%;
            transition: all 0.2s ease;
        }

        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .stat-box .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .stat-box .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
        }

        .stat-box .stat-label {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        .icon-primary { background: #eef2ff; color: #4f46e5; }
        .icon-success { background: #d1fae5; color: #047857; }
        .icon-warning { background: #fef3c7; color: #b45309; }
        .icon-info    { background: #dbeafe; color: #1d4ed8; }
        .icon-danger  { background: #fee2e2; color: #b91c1c; }

        /* ============================
           PANELS
        ============================ */

        .panel {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            margin-bottom: 22px;
            overflow: hidden;
        }

        .panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .panel-header h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-header h3 i {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #eef2ff;
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .panel-body {
            padding: 22px;
        }

        /* ============================
           TABLES
        ============================ */

        .admin-table {
            width: 100%;
            margin-bottom: 0;
        }

        .admin-table thead th {
            background: #f8fafc;
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .admin-table tbody td {
            padding: 16px;
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
        }

        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        .admin-table tbody tr:hover {
            background: #fafbff;
        }

        /* ============================
           BADGES
        ============================ */

        .badge-soft-success { background: #d1fae5; color: #047857; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-soft-secondary { background: #e2e8f0; color: #475569; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-soft-primary { background: #eef2ff; color: #4f46e5; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }

        /* ============================
           FILTER BAR
        ============================ */

        .filter-bar {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            border-radius: 8px;
            font-size: 14px;
        }

        /* ============================
           EMPTY STATE
        ============================ */

        .empty-state {
            text-align: center;
            padding: 70px 20px;
        }

        .empty-state-icon {
            font-size: 60px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        /* ============================
           RESPONSIVE
        ============================ */

        .mobile-toggle {
            display: none;
            background: transparent;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 18px;
            color: #0f172a;
        }

        @media (max-width: 991px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
            }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .admin-topbar { padding: 14px 20px; }
            .admin-content { padding: 20px; }
            .mobile-toggle { display: inline-flex; }
        }

    </style>

</head>

<body>

<div class="admin-layout">

    <?php include __DIR__ . "/sidebar.php"; ?>

    <main class="admin-main">

        <div class="admin-topbar">

            <div class="d-flex align-items-center gap-3">

                <button class="mobile-toggle" id="sidebarToggle" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <h1 class="page-heading">
                    <?php echo htmlspecialchars($pageHeading); ?>
                    <?php if ($pageSubheading !== ""): ?>
                        <small><?php echo htmlspecialchars($pageSubheading); ?></small>
                    <?php endif; ?>
                </h1>

            </div>

            <div class="admin-user">

                <div class="avatar"><?php echo $adminInitial; ?></div>

                <div>
                    <div class="fw-semibold" style="font-size:13px;">
                        <?php echo htmlspecialchars($adminName); ?>
                    </div>
                    <div style="font-size:11.5px;color:#6b7280;">
                        Administrator
                    </div>
                </div>

            </div>

        </div>

        <div class="admin-content">