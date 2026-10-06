<?php

session_start();

require_once __DIR__ . "/../Config/database.php";


/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function productImage($image)
{
    if (empty($image)) {
        return "../Assets/Images/product/1.png";
    }

    if (strpos($image, "../") === 0) {
        return $image;
    }

    return "../Assets/Images/product/" . rawurlencode(basename($image));
}


/* =========================================================
   INITIALIZE CART
========================================================= */

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}


/* =========================================================
   ADD PRODUCT TO CART
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_cart"])) {

    $productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

    if ($productId && $productId > 0) {

        $stmt = mysqli_prepare(
            $connect,
            "SELECT id, stock_quantity
             FROM products
             WHERE id = ? AND status = 'Active'
             LIMIT 1"
        );

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "i", $productId);
            mysqli_stmt_execute($stmt);

            $result  = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($product) {

                $stock = (int) $product["stock_quantity"];

                if ($stock > 0) {

                    $currentQty = isset($_SESSION["cart"][$productId])
                        ? (int) $_SESSION["cart"][$productId]
                        : 0;

                    $_SESSION["cart"][$productId] = min($currentQty + 1, $stock);
                }
            }
        }
    }

    header("Location: products.php");
    exit;
}


/* =========================================================
   FILTERS
========================================================= */

$search     = trim($_GET["search"] ?? "");
$categoryId = filter_input(INPUT_GET, "category", FILTER_VALIDATE_INT);
$sort       = $_GET["sort"] ?? "latest";
$limit      = filter_input(INPUT_GET, "limit", FILTER_VALIDATE_INT);


/* =========================================================
   VALIDATE LIMIT
========================================================= */

if (!in_array($limit, [10, 20, 30, 50], true)) {
    $limit = 50;
}


/* =========================================================
   VALIDATE SORT
========================================================= */

$allowedSorts = ["latest", "low", "high", "name"];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = "latest";
}


/* =========================================================
   FETCH ACTIVE CATEGORIES (for navbar mega menu + sidebar)
========================================================= */

$categories = [];

$categoryResult = mysqli_query(
    $connect,
    "SELECT id, name
     FROM categories
     WHERE status = 'Active'
     ORDER BY name ASC"
);

if ($categoryResult) {
    while ($category = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $category;
    }
}


/* =========================================================
   FETCH ACTIVE PRODUCTS
========================================================= */

$products = [];

$sql = "
    SELECT
        p.id, p.category_id, p.name, p.description,
        p.price, p.stock_quantity, p.image,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.status = 'Active'
";

$params = [];
$types  = "";

/* Search */
if ($search !== "") {

    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $types   .= "ss";
}

/* Category */
if ($categoryId && $categoryId > 0) {

    $sql .= " AND p.category_id = ?";

    $params[] = $categoryId;
    $types   .= "i";
}

/* Sort */
switch ($sort) {

    case "low":
        $sql .= " ORDER BY p.price ASC";
        break;

    case "high":
        $sql .= " ORDER BY p.price DESC";
        break;

    case "name":
        $sql .= " ORDER BY p.name ASC";
        break;

    case "latest":
    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}

/* Limit */
$sql .= " LIMIT ?";

$params[] = $limit;
$types   .= "i";


/* =========================================================
   EXECUTE PRODUCT QUERY
========================================================= */

$stmt = mysqli_prepare($connect, $sql);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);

    $productResult = mysqli_stmt_get_result($stmt);

    while ($product = mysqli_fetch_assoc($productResult)) {
        $products[] = $product;
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;

foreach ($_SESSION["cart"] as $quantity) {
    $quantity = (int) $quantity;
    if ($quantity > 0) {
        $cartCount += $quantity;
    }
}


/* =========================================================
   LOGIN STATUS
========================================================= */

$isLoggedIn = isset($_SESSION["user_id"]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Furnishop</title>
    <meta name="description" content="Shop all products at Furnishop">
    <link rel="icon" type="image/x-icon" href="../Assets/Images/favicon.ico">

    <link rel="stylesheet" href="../Assets/CSS/bootstrap.min.css">
    <link rel="stylesheet" href="../Assets/Font/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/nice-select/nice-select.css">
    <link rel="stylesheet" href="../Assets/Plugin/OwlCarousel2-2.3.4/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/OwlCarousel2-2.3.4/dist/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/nouislider/nouislider.min.css">
    <link rel="stylesheet" href="../Assets/Plugin/slick/slick.css">
    <link rel="stylesheet" href="../Assets/CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- =========================
         HEADER (matches index.php)
    ========================== -->
    <header>
        <div class="container py-lg-2 mt-0 mt-lg-2">
            <div class="row">

                <div class="col-12 col-lg-2 mb-2 mb-lg-3 pt-3 pt-lg-2">
                    <div class="row">
                        <div class="col-12 d-flex justify-content-center mb-3 mb-lg-0">
                            <a class="navbar-brand flex-shrink-0 py-0" href="index.php">
                                <img src="../Assets/Images/logo.png" class="logo main-logo" alt="Furnishop">
                            </a>
                        </div>
                        <div class="col-12">
                            <div class="list-inline d-lg-none d-flex justify-content-between">
                                <div class="list-inline-item d-inline-block d-lg-none">
                                    <button class="navbar-toggler border-0 collapsed" type="button"
                                            data-bs-toggle="offcanvas" data-bs-target="#navbar-default"
                                            aria-controls="navbar-default" aria-label="Toggle navigation">
                                        <i class="bi bi-text-indent-left"></i>
                                    </button>
                                </div>
                                <div>
                                    <div class="list-inline-item me-4">
                                        <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"
                                           class="text-muted d-flex flex-column justify-content-center align-items-center">
                                            <i class="bi bi-person"></i>
                                            <span class="d-none d-lg-block">Account</span>
                                        </a>
                                    </div>
                                    <div class="list-inline-item me-4">
                                        <a href="cart.php"
                                           class="text-muted d-flex flex-column justify-content-center align-items-center">
                                            <div class="position-relative">
                                                <i class="bi bi-cart"></i>
                                                <?php if ($cartCount > 0): ?>
                                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                        <?= $cartCount ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="d-none d-lg-block">Your cart</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <nav class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2"
                         aria-label="Offcanvas navbar large">
                        <div class="container">
                            <div class="offcanvas offcanvas-start pt-2" tabindex="-1" id="navbar-default"
                                 aria-labelledby="navbar-defaultLabel">

                                <div class="offcanvas-header pb-1">
                                    <a href="index.php">
                                        <img src="../Assets/Images/logo.png" alt="Furnishop">
                                    </a>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                            aria-label="Close"></button>
                                </div>

                                <div class="offcanvas-body">
                                    <div class="d-block d-lg-none mb-4">
                                        <form action="products.php" method="GET">
                                            <div class="input-group">
                                                <input class="form-control" type="search" name="search"
                                                       value="<?= e($search) ?>"
                                                       placeholder="Search for products">
                                                <span class="input-group-append">
                                                    <button class="btn bg-white border border-start-0 ms-n10 rounded-0 rounded-end"
                                                            type="submit">
                                                        <span class="bi bi-search"></span>
                                                    </button>
                                                </span>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="mx-auto">
                                        <ul class="navbar-nav align-items-center ms-lg-5">

                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link" href="index.php">Home</a>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">Shop</a>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="products.php">All Products</a></li>
                                                    <li><a class="dropdown-item" href="cart.php">Shopping Cart</a></li>
                                                    <li><a class="dropdown-item" href="checkout.php">Checkout</a></li>
                                                </ul>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto dropdown-fullwidth">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                                                <div class="dropdown-menu category-mega-menu">
                                                    <div class="category-mega-inner">
                                                        <div class="category-mega-heading">
                                                            <span>Shop by Category</span>
                                                            <a href="products.php">View All Products</a>
                                                        </div>
                                                        <?php if (!empty($categories)): ?>
                                                            <div class="category-mega-grid">
                                                                <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                                                                    <a class="category-menu-item"
                                                                       href="products.php?category=<?= (int) $cat["id"] ?>">
                                                                        <span class="category-menu-icon"><i class="bi bi-grid"></i></span>
                                                                        <span>
                                                                            <strong><?= e($cat["name"]) ?></strong>
                                                                        </span>
                                                                        <i class="bi bi-arrow-right category-menu-arrow"></i>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <p class="text-muted mb-0">No categories yet.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>

                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">Account</a>
                                                <ul class="dropdown-menu">
                                                    <?php if ($isLoggedIn): ?>
                                                        <li><a class="dropdown-item" href="profile.php">My Account</a></li>
                                                        <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                                    <?php else: ?>
                                                        <li><a class="dropdown-item" href="login.php">Sign in</a></li>
                                                        <li><a class="dropdown-item" href="register.php">Signup</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>

                <div class="col-12 col-lg-3 d-none d-lg-block">
                    <div class="offcanvas offcanvas-top" tabindex="-1" id="offcanvasTop"
                         aria-labelledby="offcanvasTopLabel">
                        <div class="offcanvas-header">
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                    aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <div class="mt-50">
                                <form action="products.php" method="GET">
                                    <div class="input-group">
                                        <input class="form-control py-3" type="search" name="search"
                                               value="<?= e($search) ?>"
                                               placeholder="Search for products">
                                        <span class="input-group-append">
                                            <button class="btn bg-white border border-start-0 ms-n10 py-3 rounded-0 rounded-end"
                                                    type="submit">
                                                <span class="bi bi-search"></span>
                                            </button>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="list-inline d-flex pt-2">
                        <div class="list-inline-item me-4">
                            <a href="#offcanvasTop" data-bs-toggle="offcanvas"
                               data-bs-target="#offcanvasTop" aria-controls="offcanvasTop"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-search"></i>
                                <span class="d-none d-lg-block">Search</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-person"></i>
                                <span class="d-none d-lg-block">Account</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="cart.php"
                               class="text-muted d-flex flex-column justify-content-center align-items-center">
                                <div class="position-relative">
                                    <i class="bi bi-cart"></i>
                                    <?php if ($cartCount > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            <?= $cartCount ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="d-none d-lg-block">Your cart</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>
    <!-- Header End -->

    <!-- =========================
         MAIN
    ========================== -->
    <main>

        <!-- BREADCRUMB -->
        <div class="breadcrumb-main">
            <div class="container">
                <div class="breadcrumb-container">
                    <h2 class="page-title">Products</h2>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Products</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SHOP -->
        <div class="shop-section">
            <div class="mb-5 mt-50">
                <div class="container">
                    <div class="row gx-10">

                        <!-- SIDEBAR -->
                        <aside class="col-lg-3 col-md-4 mb-2">
                            <div class="offcanvas offcanvas-start offcanvas-collapse w-md-50"
                                 tabindex="-1" id="offcanvasCategory"
                                 aria-labelledby="offcanvasCategoryLabel">

                                <div class="offcanvas-header d-lg-none border-bottom">
                                    <h5 class="offcanvas-title" id="offcanvasCategoryLabel">Filter</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                            aria-label="Close"></button>
                                </div>

                                <div class="offcanvas-body ps-lg-2 pt-lg-0">

                                    <!-- SEARCH -->
                                    <div class="mb-4 border-bottom pb-3">
                                        <h5 class="mb-3">Search</h5>
                                        <form method="get" action="products.php">
                                            <div class="input-group">
                                                <input type="search" name="search" class="form-control"
                                                       value="<?= e($search) ?>"
                                                       placeholder="Product name">
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- CATEGORIES -->
                                    <div class="my-4 border-bottom pb-3">
                                        <h5 class="mb-3">Categories</h5>

                                        <div class="form-check mb-2">
                                            <a href="products.php" class="text-decoration-none">
                                                <i class="bi bi-grid me-2"></i>
                                                All Categories
                                            </a>
                                        </div>

                                        <?php foreach ($categories as $category): ?>
                                            <div class="form-check mb-2">
                                                <a href="products.php?category=<?= (int) $category["id"] ?>"
                                                   class="text-decoration-none">
                                                    <?= e($category["name"]) ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- COUNT -->
                                    <div class="my-4">
                                        <h5 class="mb-3">Products</h5>
                                        <p class="text-muted mb-0">
                                            <?= count($products) ?> product(s) found
                                        </p>
                                    </div>

                                </div>
                            </div>
                        </aside>

                        <!-- PRODUCTS -->
                        <section class="col-lg-9 col-md-12">

                            <!-- FILTERS / SORT -->
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                                <div class="d-lg-none">
                                    <a class="btn border px-4 text-muted"
                                       data-bs-toggle="offcanvas"
                                       href="#offcanvasCategory"
                                       role="button"
                                       aria-controls="offcanvasCategory">
                                        <i class="bi bi-funnel me-2"></i>
                                        Filters
                                    </a>
                                </div>

                                <div class="d-flex ms-auto">

                                    <!-- LIMIT -->
                                    <form method="get" action="products.php" class="me-2">
                                        <input type="hidden" name="search"   value="<?= e($search) ?>">
                                        <input type="hidden" name="category" value="<?= e((string) ($categoryId ?? '')) ?>">
                                        <input type="hidden" name="sort"     value="<?= e($sort) ?>">

                                        <select class="nice-option" name="limit" onchange="this.form.submit()">
                                            <option value="10" <?= $limit === 10 ? "selected" : "" ?>>Show: 10</option>
                                            <option value="20" <?= $limit === 20 ? "selected" : "" ?>>Show: 20</option>
                                            <option value="30" <?= $limit === 30 ? "selected" : "" ?>>Show: 30</option>
                                            <option value="50" <?= $limit === 50 ? "selected" : "" ?>>Show: 50</option>
                                        </select>
                                    </form>

                                    <!-- SORT -->
                                    <form method="get" action="products.php">
                                        <input type="hidden" name="search"   value="<?= e($search) ?>">
                                        <input type="hidden" name="category" value="<?= e((string) ($categoryId ?? '')) ?>">
                                        <input type="hidden" name="limit"    value="<?= (int) $limit ?>">

                                        <select class="nice-option" name="sort" onchange="this.form.submit()">
                                            <option value="latest" <?= $sort === "latest" ? "selected" : "" ?>>Latest</option>
                                            <option value="low"    <?= $sort === "low"    ? "selected" : "" ?>>Low to High</option>
                                            <option value="high"   <?= $sort === "high"   ? "selected" : "" ?>>High to Low</option>
                                            <option value="name"   <?= $sort === "name"   ? "selected" : "" ?>>Name A-Z</option>
                                        </select>
                                    </form>

                                </div>
                            </div>

                            <!-- PRODUCT GRID -->
                            <div class="product">
                                <div class="row g-4 row-cols-xl-3 row-cols-lg-3 row-cols-2 row-cols-md-2 mt-1">

                                    <?php if (empty($products)): ?>

                                        <div class="col-12">
                                            <div class="alert alert-light text-center py-5">
                                                <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
                                                <h5>No products found</h5>
                                                <p class="text-muted">Try another search or category.</p>
                                                <a href="products.php" class="btn btn-primary">
                                                    View All Products
                                                </a>
                                            </div>
                                        </div>

                                    <?php else: ?>

                                        <?php foreach ($products as $product): ?>

                                            <?php
                                            $productId = (int) $product["id"];
                                            $stock     = (int) $product["stock_quantity"];
                                            $image     = productImage($product["image"]);
                                            ?>

                                            <div class="col">
                                                <div class="card product-card h-100">

                                                    <a href="product-details.php?id=<?= $productId ?>">
                                                        <img src="<?= e($image) ?>"
                                                             class="card-img-top image-first"
                                                             alt="<?= e($product["name"]) ?>">
                                                        <img src="<?= e($image) ?>"
                                                             class="card-img-top image-second"
                                                             alt="<?= e($product["name"]) ?>">
                                                    </a>

                                                    <div class="card-body pt-0">
                                                        <?php if ($stock > 0 && $stock <= 5): ?>
                                                            <span class="discount-badge">Low Stock</span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="product-price px-3 pb-2">

                                                        <h5 class="card-title">
                                                            <a href="product-details.php?id=<?= $productId ?>">
                                                                <?= e($product["name"]) ?>
                                                            </a>
                                                        </h5>

                                                        <?php if (!empty($product["category_name"])): ?>
                                                            <small class="text-muted d-block mb-1">
                                                                <?= e($product["category_name"]) ?>
                                                            </small>
                                                        <?php endif; ?>

                                                        <div class="mb-2">
                                                            <small class="text-warning">
                                                                <i class="bi bi-star-fill"></i>
                                                                <i class="bi bi-star-fill"></i>
                                                                <i class="bi bi-star-fill"></i>
                                                                <i class="bi bi-star-fill"></i>
                                                                <i class="bi bi-star-half"></i>
                                                            </small>
                                                        </div>

                                                        <div class="d-block">
                                                            <span class="sell-price">
                                                                Rs. <?= number_format((float) $product["price"], 2) ?>
                                                            </span>
                                                        </div>

                                                        <?php if ($stock > 0): ?>
                                                            <small class="text-success">
                                                                <?= $stock <= 5 ? "Only " . $stock . " left" : "In Stock" ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <small class="text-danger">Out of Stock</small>
                                                        <?php endif; ?>

                                                    </div>

                                                    <div class="d-block mb-2">
                                                        <div class="d-flex gap-2 px-2">

                                                            <?php if ($stock > 0): ?>

                                                                <form method="post" class="flex-fill">
                                                                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                                                                    <button type="submit" name="add_to_cart"
                                                                            class="btn btn-primary w-100">
                                                                        Add to Cart
                                                                    </button>
                                                                </form>

                                                                <a href="product-details.php?id=<?= $productId ?>"
                                                                   class="btn btn-secondary flex-fill">
                                                                    Details
                                                                </a>

                                                            <?php else: ?>

                                                                <button class="btn btn-secondary w-100" disabled>
                                                                    Out of Stock
                                                                </button>

                                                            <?php endif; ?>

                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </div>
                            </div>

                            <!-- RESULTS / CLEAR -->
                            <div class="row mt-50 mb-5">
                                <div class="col-lg-12">
                                    <div class="pagination-main">
                                        <div class="row">

                                            <div class="col-sm-6 pagination_result">
                                                Showing <?= count($products) ?> product(s)
                                            </div>

                                            <div class="col-sm-6 text-md-end">
                                                <?php if ($search !== "" || $categoryId || $sort !== "latest"): ?>
                                                    <a href="products.php"
                                                       class="btn btn-outline-secondary btn-sm">
                                                        Clear Filters
                                                    </a>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        </section>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- =========================
         FOOTER (matches index.php)
    ========================== -->
    <footer class="mt-0">
        <div class="container">
            <div class="row">

                <div class="col-lg-4 mb-4 mb-md-0">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-12">
                            <div class="footer_logo">
                                <img loading="lazy" src="../Assets/Images/logo.png" class="logo" alt="Furnishop">
                            </div>
                            <div class="mt-4">
                                <p>Furnishop provides quality furniture and home products for every space.</p>
                                <h3 class="h5 fw-bold">+92 300 1234567</h3>
                                <p>support@furnishop.com</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-3 mb-md-0">
                    <div class="row">
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">My Account</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <?php if (!$isLoggedIn): ?>
                                        <li><a href="login.php">Login</a></li>
                                        <li><a href="register.php">Register</a></li>
                                    <?php else: ?>
                                        <li><a href="profile.php">My Account</a></li>
                                        <li><a href="orders.php">My Orders</a></li>
                                        <li><a href="logout.php">Logout</a></li>
                                    <?php endif; ?>
                                    <li><a href="cart.php">Cart</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Shopping</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <li><a href="products.php">Products</a></li>
                                    <li><a href="checkout.php">Checkout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Useful Links</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <li><a href="cart.php">Shopping Cart</a></li>
                                    <li><a href="orders.php">My Orders</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Categories</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
                                            <li>
                                                <a href="products.php?category=<?= (int) $cat["id"] ?>">
                                                    <?= e($cat["name"]) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li><a href="products.php">All Products</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="text-center py-3 mt-4 text-white px-3 copyright">
            <span>Copyright © <?= date("Y") ?>. All Rights Reserved. Furnishop.</span>
        </div>
    </footer>

    <!-- =========================
         SCRIPTS
    ========================== -->
    <script src="../Assets/JS/jquery-3.6.0.min.js"></script>
    <script src="../Assets/JS/bootstrap.bundle.min.js"></script>
    <script src="../Assets/Plugin/nice-select/jquery.nice-select.min.js"></script>
    <script src="../Assets/Plugin/OwlCarousel2-2.3.4/dist/owl.carousel.min.js"></script>
    <script src="../Assets/Plugin/nouislider/nouislider.min.js"></script>
    <script src="../Assets/Plugin/slick/slick.min.js"></script>
    <script src="../Assets/JS/main.js"></script>
</body>
</html>