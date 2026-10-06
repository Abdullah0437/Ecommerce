<?php
/* =========================================================
   ADMIN SIDEBAR
========================================================= */

$currentPage = $currentPage ?? basename($_SERVER["PHP_SELF"]);

function navActive($file, $current) {
    return $file === $current ? " active" : "";
}
?>

<aside class="admin-sidebar" id="adminSidebar">

    <div class="brand">
        <i class="fa-solid fa-couch"></i> Furnishop
    </div>

    <ul class="nav flex-column">

        <li class="nav-item">
            <a class="nav-link<?php echo navActive('dashboard.php', $currentPage); ?>" href="dashboard.php">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </li>

        <li class="nav-section">Catalog</li>

        <li class="nav-item">
            <a class="nav-link<?php echo navActive('categories.php', $currentPage); ?>" href="categories.php">
                <i class="fa-solid fa-layer-group"></i> Categories
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link<?php echo navActive('products.php', $currentPage); ?>" href="products.php">
                <i class="fa-solid fa-box"></i> Products
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link<?php echo navActive('stock.php', $currentPage); ?>" href="stock.php">
                <i class="fa-solid fa-warehouse"></i> Stock
            </a>
        </li>

        <li class="nav-section">Sales</li>

        <li class="nav-item">
            <a class="nav-link<?php echo navActive('orders.php', $currentPage); ?>" href="orders.php">
                <i class="fa-solid fa-receipt"></i> Orders
            </a>
        </li>

        <li class="nav-section">Users</li>

        <li class="nav-item">
            <a class="nav-link<?php echo navActive('users.php', $currentPage); ?>" href="users.php">
                <i class="fa-solid fa-users"></i> Customers
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link<?php echo navActive('profile.php', $currentPage); ?>" href="profile.php">
                <i class="fa-solid fa-user-gear"></i> My Profile
            </a>
        </li>

        <li class="nav-section">&nbsp;</li>

        <li class="nav-item">
            <a class="nav-link" href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </li>

    </ul>

</aside>