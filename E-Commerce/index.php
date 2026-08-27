<?php
session_start();

require_once __DIR__ . "/../Config/database.php";

// Add product to session cart
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_cart"])) {
    $productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

    if ($productId) {
        $stmt = mysqli_prepare($connect, "SELECT id, name, price, stock_quantity, image FROM products WHERE id = ? AND status = 'Active' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($product && (int)$product["stock_quantity"] > 0) {
            if (!isset($_SESSION["cart"])) {
                $_SESSION["cart"] = [];
            }

            $currentQty = isset($_SESSION["cart"][$productId]) ? (int)$_SESSION["cart"][$productId] : 0;
            $_SESSION["cart"][$productId] = min($currentQty + 1, (int)$product["stock_quantity"]);
        }
    }

    header("Location: index.php");
    exit;
}

// Fetch active categories
$categories = [];
$categoryResult = mysqli_query($connect, "SELECT id, name FROM categories WHERE status = 'Active' ORDER BY name ASC");
if ($categoryResult) {
    while ($category = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $category;
    }
}

// Fetch active products
$products = [];
$productResult = mysqli_query($connect, "SELECT id, category_id, name, description, price, stock_quantity, image FROM products WHERE status = 'Active' ORDER BY id DESC");
if ($productResult) {
    while ($product = mysqli_fetch_assoc($productResult)) {
        $products[] = $product;
    }
}

$cartCount = 0;
if (!empty($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
    foreach ($_SESSION["cart"] as $quantity) {
        $cartCount += (int)$quantity;
    }
}

function productImage($image)
{
    if (empty($image)) {
        return "../assets/images/product/1.png";
    }

    if (strpos($image, "../Images/") === 0) {
        return $image;
    }

    return "../Images/" . rawurlencode(basename($image));
}

function stockLabel($stock)
{
    $stock = (int)$stock;
    if ($stock <= 0) return "Out of Stock";
    if ($stock <= 5) return "Only " . $stock . " left";
    return "In Stock";
}

$featuredProducts = $products;
usort($featuredProducts, function ($a, $b) {
    return (float)$b["price"] <=> (float)$a["price"];
});
$featuredProducts = array_slice($featuredProducts, 0, 4);

$specialProducts = array_values(array_filter($products, function ($p) {
    return (int)$p["stock_quantity"] <= 5;
}));
if (empty($specialProducts)) {
    $specialProducts = array_slice($products, 0, 4);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furnishop</title>
    <meta name="description" content="">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
    <!-- font-awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/plugin/nice-select/nice-select.css">
    <link rel="stylesheet" href="../assets/plugin/OwlCarousel2-2.3.4/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="../assets/plugin/OwlCarousel2-2.3.4/dist/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="../assets/plugin/nouislider/nouislider.min.css">
    <link rel="stylesheet" href="../assets/plugin/slick/slick.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
</head>

<body>
    <!-- Header Section Start -->
    <header>
        <!-- Navbar -->
        <div class="container py-lg-2 mt-0 mt-lg-2">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-2  mb-2 mb-lg-3 pt-3 pt-lg-2">
                    <div class="row">
                        <div class="col-12 d-flex justify-content-center mb-3 mb-lg-0">
                            <!-- Logo -->
                            <a class="navbar-brand flex-shrink-0 py-0 py-lg-0" href="index.php">
                                <img src="../assets/images/logo.png" class="logo main-logo" alt="eCommerce HTML Template">
                            </a>
                            <!-- Logo -->
                        </div>

                        <div class="col-12">
                            <div class="list-inline  d-lg-none d-flex justify-content-between">

                                <div class="list-inline-item d-inline-block d-lg-none">
                                    <button class="navbar-toggler border-0 collapsed" type="button" data-bs-toggle="offcanvas"
                                        data-bs-target="#navbar-default" aria-controls="navbar-default"
                                        aria-label="Toggle navigation">
                                        <i class="bi bi-text-indent-left"></i>
                                    </button>
                                </div>

                                <div>
                                    <div class="list-inline-item me-4">
                                        <a href="login.php" class="text-muted d-flex flex-column justity-content-center align-items-center">
                                            <i class="bi bi-person"></i>
                                            <span class="d-none d-sm-none d-md-none d-lg-block">Account</span>
                                        </a>
                                    </div>
                                    <div class="list-inline-item me-4">
                                        <a href="../wishlist.html" class="text-muted  d-flex flex-column justity-content-center align-items-center">
                                            <i class="bi bi-heart"></i>
                                            <span class="d-none d-sm-none d-md-none d-lg-block">Wishlist</span>
                                        </a>
                                    </div>
                                    <div class="list-inline-item me-4">
                                        <a href="cart.php" class="text-muted  d-flex flex-column justity-content-center align-items-center">
                                            <div class="position-relative">
                                                <i class="bi bi-cart"></i>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                    <?= $cartCount ?>
                                                </span>
                                            </div>
                                            <span class="d-none d-sm-none d-md-none d-lg-block">Your cart</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-12 col-lg-7">
                    <nav class="navbar navbar-expand-lg navbar-light navbar-default py-0 pb-lg-2" aria-label="Offcanvas navbar large">
                        <div class="container">
                            <div class="offcanvas offcanvas-start pt-2" tabindex="-1" id="navbar-default"
                                aria-labelledby="navbar-defaultLabel">
                                <div class="offcanvas-header pb-1">
                                    <a href="index.php"><img src="../assets/images/logo.png"
                                            alt="eCommerce HTML Template"></a>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                                </div>
                                <div class="offcanvas-body">
                                    <div class="d-block d-lg-none mb-4">
                                        <form action="#">
                                            <div class="input-group">
                                                <input class="form-control" type="search" placeholder="Search for products">
                                                <span class="input-group-append">
                                                    <button
                                                        class="btn bg-white border border-start-0 ms-n10 rounded-0 rounded-end"
                                                        type="button">
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
                                                    <li><a class="dropdown-item" href="products.php">Shop Page - Filter</a></li>
                                                    <li><a class="dropdown-item" href="product-details.php">Shop Single</a></li>
                                                    <li><a class="dropdown-item" href="../wishlist.html">Shop Wishlist</a></li>
                                                    <li><a class="dropdown-item" href="cart.php">Shop Cart</a></li>
                                                    <li><a class="dropdown-item" href="checkout.php">Shop Checkout</a></li>
                                                </ul>
                                            </li>
                                            <li class="nav-item dropdown w-100 w-lg-auto dropdown-fullwidth">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                                                <div class="dropdown-menu pb-0">
                                                    <div class="row p-2 p-lg-4">
                                                        <div class="col-lg-4 col-12 mb-4 mb-lg-0">
                                                            <h6 class="text-primary ps-3">Accessories</h6>
                                                            <a class="dropdown-item" href="#">Table</a>
                                                            <a class="dropdown-item" href="#">Chair</a>
                                                        </div>
                                                        <div class="col-lg-4 col-12 mb-4 mb-lg-0">
                                                            <h6 class="text-primary ps-3">Accessories</h6>
                                                            <a class="dropdown-item" href="#">Wardrobe</a>
                                                            <a class="dropdown-item" href="#">Cupboard</a>
                                                        </div>
                                                        <div class="col-lg-4 col-12 mb-4 mb-lg-0">
                                                            <h6 class="text-primary ps-3">Accessories</h6>
                                                            <a class="dropdown-item" href="#">Sofa</a>
                                                            <a class="dropdown-item" href="#">Bed</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">Pages</a>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="../blog.html">Blog</a></li>
                                                    <li><a class="dropdown-item" href="../blog-single.html">Blog Single</a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="../about.html">About us</a></li>
                                                    <li><a class="dropdown-item" href="../contact.html">Contact</a></li>
                                                </ul>
                                            </li>
                                            <li class="nav-item dropdown w-100 w-lg-auto">
                                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">Account</a>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="login.php">Sign in</a></li>
                                                    <li><a class="dropdown-item" href="register.php">Signup</a></li>
                                                    <li><a class="dropdown-item" href="../forgot-password.html">Forgot Password</a></li>
                                                </ul>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
                <div class="col-12 col-sm-12 col-md-12 col-lg-3 d-none d-lg-block">
                    <!-- Navbar Actions -->
                    <div class="offcanvas offcanvas-top" tabindex="-1" id="offcanvasTop" aria-labelledby="offcanvasTopLabel">
                        <div class="offcanvas-header">
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <div class="mt-50">
                                <form action="#">
                                    <div class="input-group">
                                        <input class="form-control py-3" type="search" placeholder="Search for products">
                                        <span class="input-group-append">
                                            <button
                                                class="btn bg-white border border-start-0 ms-n10 py-3 rounded-0 rounded-end"
                                                type="button">
                                                <span class="bi bi-search"></span>
                                            </button>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="list-inline  d-flex pt-2">
                        <div class="list-inline-item me-4">
                            <a href="login.php" data-bs-toggle="offcanvas" data-bs-target="#offcanvasTop" aria-controls="offcanvasTop" class="text-muted d-flex flex-column justity-content-center align-items-center">
                                <i class="bi bi-search"></i>
                                <span class="d-none d-sm-none d-md-none d-lg-block">Search</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="login.php" class="text-muted d-flex flex-column justity-content-center align-items-center">
                                <i class="bi bi-person"></i>
                                <span class="d-none d-sm-none d-md-none d-lg-block">Account</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="../wishlist.html" class="text-muted  d-flex flex-column justity-content-center align-items-center">
                                <i class="bi bi-heart"></i>
                                <span class="d-none d-sm-none d-md-none d-lg-block">Wishlist</span>
                            </a>
                        </div>
                        <div class="list-inline-item me-4">
                            <a href="cart.php" class="text-muted  d-flex flex-column justity-content-center align-items-center">
                                <i class="bi bi-cart"></i>
                                <span class="d-none d-sm-none d-md-none d-lg-block">Your cart</span>
                            </a>
                        </div>
                    </div>
                    <!-- Navbar Actions -->
                </div>
            </div>
        </div>
        <!-- Navbar -->
    </header>
    <!-- Header Section End -->
    <!-- Banner Slider -->
    <div class="banner-section" style="background-color: #FEF6F0;">
        <div class="container">
            <div class="owl-carousel owl-theme banner-slider">
                <div class="item">
                    <div class="banner-item" style="background-image: url(./assets/images/banner/1.png)">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-8 col-lg-6">
                                    <div class="banner-content text-left">
                                        <span class="mb-3 d-block">Top Selling!</span>
                                        <h2>Best Collection Furniture</h2>
                                        <a href="#" class="btn btn-primary uppercase mt-4">Shop Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="banner-item" style="background-image: url(./assets/images/banner/2.png)">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-8 col-lg-6">
                                    <div class="banner-content text-left">
                                        <span class="mb-3 d-block">Top Selling!</span>
                                        <h2>Best Collection Furniture</h2>
                                        <a href="#" class="btn btn-primary uppercase mt-4">Shop Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Banner Slider -->

    <!-- start hero banner Section -->
    <div class="hero-banner-area">
        <div class="container">
            <div class="row">
                <!-- Single -->
                <div class="col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
                    <div class="hero-banner-item rounded">
                        <img src="../assets/images/banner/4.png" alt="banner">
                        <div class="hero-banner-item-overly">
                            <div class="hero-banner-item-overly-full">
                                <h4>Exclusive Sale</h4>
                                <h3>Modern</h3>
                                <a class="btn btn-primary mt-2" href="products.php">Shop now</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Single -->
                <div class="col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
                    <div class="hero-banner-item rounded">
                        <img src="../assets/images/banner/3.png" alt="banner">
                        <div class="hero-banner-item-overly">
                            <div class="hero-banner-item-overly-full">
                                <h4>Super Sale</h4>
                                <h3>Furniture</h3>
                                <a class="btn btn-primary mt-2" href="products.php">Shop now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end hero banner Section -->

    <!-- Categories Section -->
    <div class="category-list">
        <div class="container">
            <div class="section-title">
                <h2>Category</h2>
            </div>
            <div class="category-container">
                <div class="owl-carousel category-slider">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $index => $category): ?>
                            <?php $categoryImage = (($index % 7) + 1); ?>
                            <div class="item">
                                <div>
                                    <div class="category-hover">
                                        <a href="products.php?category=<?= (int)$category['id'] ?>">
                                            <img src="../assets/images/category/<?= $categoryImage ?>.png" class="img-fluid" alt="<?= htmlspecialchars($category['name']) ?>">
                                        </a>
                                    </div>
                                    <a href="products.php?category=<?= (int)$category['id'] ?>" class="d-block category-title">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="item">
                            <p class="text-muted">No active categories found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- End Categories Section -->

    <!-- Product Section -->
    <div class="product mt-100">
        <div class="container">
            <div>
                <div class="section-title">
                    <h2>Our Product</h2>
                </div>
                <div class="d-flex justify-content-center">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-lastest-tab" data-bs-toggle="pill" data-bs-target="#pills-lastest" type="button" role="tab">Latest</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-popularity-tab" data-bs-toggle="pill" data-bs-target="#pills-popularity" type="button" role="tab">Featured</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-top-tab" data-bs-toggle="pill" data-bs-target="#pills-top" type="button" role="tab">Special</button>
                        </li>
                    </ul>
                </div>
            </div>

            <?php
            function renderProducts($items)
            {
                if (empty($items)) {
                    echo '<div class="col-12"><div class="alert alert-light text-center">No products available.</div></div>';
                    return;
                }
                foreach ($items as $product):
                    $stock = (int)$product['stock_quantity'];
                    $image = productImage($product['image']);
            ?>
                    <div class="col">
                        <div class="card product-card h-100">
                            <a href="product-details.php?id=<?= (int)$product['id'] ?>">
                                <img src="<?= htmlspecialchars($image) ?>" class="card-img-top image-first" alt="<?= htmlspecialchars($product['name']) ?>">
                                <img src="<?= htmlspecialchars($image) ?>" class="card-img-top image-second" alt="<?= htmlspecialchars($product['name']) ?>">
                            </a>
                            <div class="card-body pt-0">
                                <div class="icons">
                                    <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                        <i class="bi bi-heart"></i>
                                    </a>
                                </div>
                                <?php if ($stock > 0 && $stock <= 5): ?>
                                    <span class="discount-badge">Low Stock</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-price px-3 pb-2">
                                <h5 class="card-title"><a href="product-details.php?id=<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></h5>
                                <div class="mb-2"><small class="text-warning"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></small></div>
                                <div class="d-block">
                                    <span class="sell-price">Rs. <?= number_format((float)$product['price'], 2) ?></span>
                                </div>
                                <small class="<?= $stock > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= htmlspecialchars(stockLabel($stock)) ?>
                                </small>
                            </div>
                            <div class="d-block mb-2">
                                <div class="d-flex gap-2 px-2">
                                    <?php if ($stock > 0): ?>

                                        <form method="post" class="flex-fill">
                                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                                            <button type="submit"
                                                name="add_to_cart"
                                                class="btn btn-primary w-100">
                                                
                                                Add to Cart
                                            </button>
                                        </form>

                                        <a href="checkout.php?product_id=<?= (int)$product['id'] ?>"
                                            class="btn btn-secondary flex-fill">
                                           
                                            Buy Now
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
            <?php endforeach;
            } ?>

            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-lastest" role="tabpanel">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <?php renderProducts($products); ?>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-popularity" role="tabpanel">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <?php renderProducts($featuredProducts); ?>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-top" role="tabpanel">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <?php renderProducts($specialProducts); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Product Section -->



    <!-- Product Section -->
    <div class="product mt-100">
        <div class="container">
            <div>
                <div class="section-title">
                    <h2>Our Product</h2>
                </div>
                <div class="d-flex justify-content-center">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-lastest-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-lastest" type="button" role="tab" aria-controls="pills-lastest"
                                aria-selected="true">Lastest</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-popularity-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-popularity" type="button" role="tab"
                                aria-controls="pills-popularity" aria-selected="false">Featured</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-top-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-top" type="button" role="tab" aria-controls="pills-top"
                                aria-selected="false">Special</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-lastest" role="tabpanel" aria-labelledby="pills-lastest-tab" tabindex="0">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/1.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/2.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">20% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Sofa
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$13.00</span>
                                            <span class="text-muted strike-through"><s>$15.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/3.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/4.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">10% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Light Table
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$9.00</span>
                                            <span class="text-muted strike-through"><s>$10.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/5.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/6.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">5% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Particle board
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$18.00</span>
                                            <span class="text-muted strike-through"><s>$19.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/7.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/8.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Rosewood sheesham
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$17.00</span>
                                            <span class="text-muted strike-through"><s>$18.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/9.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/10.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">10% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Table rainbow
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$11.00</span>
                                            <span class="text-muted strike-through"><s>$13.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/11.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/12.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Wood worldih
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$13.00</span>
                                            <span class="text-muted strike-through"><s>$15.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/13.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/14.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Jumbo
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$6.00</span>
                                            <span class="text-muted strike-through"><s>$7.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/15.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/16.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Chair Table
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$6.00</span>
                                            <span class="text-muted strike-through"><s>$9.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <a href="products.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-popularity" role="tabpanel" aria-labelledby="pills-popularity-tab" tabindex="0">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/17.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/18.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">20% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Seater beige
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$13.00</span>
                                            <span class="text-muted strike-through"><s>$15.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/19.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/20.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">

                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">10% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Layer rack
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$9.00</span>
                                            <span class="text-muted strike-through"><s>$10.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/21.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/22.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">5% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Blue Sofa
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$18.00</span>
                                            <span class="text-muted strike-through"><s>$19.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <div class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</div>
                                            <div class="btn btn-secondary ms-lg-1">Buy Now</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/23.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/24.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Wooden
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$17.00</span>
                                            <span class="text-muted strike-through"><s>$18.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/25.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/26.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">10% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Coffe Table
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$11.00</span>
                                            <span class="text-muted strike-through"><s>$13.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/27.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/28.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Mini Wood
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$13.00</span>
                                            <span class="text-muted strike-through"><s>$15.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/29.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/30.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Tea Table
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$6.00</span>
                                            <span class="text-muted strike-through"><s>$7.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/31.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/32.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Study Table
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$6.00</span>
                                            <span class="text-muted strike-through"><s>$9.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <a href="products.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-top" role="tabpanel" aria-labelledby="pills-top-tab" tabindex="0">
                    <div class="product">
                        <div class="row g-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 mt-1">
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/23.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/24.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">20% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Wooden
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$13.00</span>
                                            <span class="text-muted strike-through"><s>$15.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/17.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/18.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">10% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Seater beige
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$9.00</span>
                                            <span class="text-muted strike-through"><s>$10.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/1.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/2.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">5% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Sofa
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$18.00</span>
                                            <span class="text-muted strike-through"><s>$19.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/9.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/10.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Table rainbow
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$17.00</span>
                                            <span class="text-muted strike-through"><s>$18.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/25.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/26.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">10% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Coffe Table
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$11.00</span>
                                            <span class="text-muted strike-through"><s>$13.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/5.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/6.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Particle board
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$13.00</span>
                                            <span class="text-muted strike-through"><s>$15.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/11.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/12.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Wood worldih
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$6.00</span>
                                            <span class="text-muted strike-through"><s>$7.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product-details.php">
                                        <img src="../assets/images/product/29.png" class="card-img-top image-first" alt="eCommerce Template">
                                        <img src="../assets/images/product/30.png" class="card-img-top image-second" alt="eCommerce Template">
                                    </a>
                                    <div class="card-body pt-0">
                                        <div class="icons">
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist">
                                                <i class="bi bi-heart"></i>
                                            </a>
                                        </div>
                                        <span class="discount-badge">30% OFF</span>
                                    </div>
                                    <div class="product-price px-3 pb-2">
                                        <h5 class="card-title"><a href="product-details.php">
                                                Tea Table
                                            </a>
                                        </h5>
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
                                            <span class="sell-price">$6.00</span>
                                            <span class="text-muted strike-through"><s>$9.00</s></span>
                                        </div>
                                    </div>
                                    <div class="d-block mb-2">
                                        <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                            <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                            <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <a href="products.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Product Section End -->

    <!-- Feature Section -->
    <div class="feature-section mt-100">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="white-bg border px-4 py-3">
                        <div class="row">
                            <div class="mb-20 col-12 col-sm-12 col-md-6 col-lg-3">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <img src="../assets/images/icon/delivery-truck.png" class="w-100" alt="truck Icon">
                                    </div>
                                    <div class="feature-info pt-3 ps-2">
                                        <h4>Free Shipping</h4>
                                        <p>On orders over&nbsp;<strong>$50.</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-20 col-12 col-sm-12 col-md-6 col-lg-3">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <img src="../assets/images/icon/loan.png" class="w-100" alt="truck Icon">
                                    </div>
                                    <div class="feature-info pt-3 ps-2">
                                        <h4>Money Back</h4>
                                        <p>Money back in 7 days.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-20 col-12 col-sm-12 col-md-6 col-lg-3">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <img src="../assets/images/icon/credit-card.png" class="w-100" alt="truck Icon">
                                    </div>
                                    <div class="feature-info pt-3 ps-2">
                                        <h4>Secure Checkout</h4>
                                        <p>100% Payment Secure.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-20 col-12 col-sm-12 col-md-6 col-lg-3">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <img src="../assets/images/icon/customer-service.png" class="w-100" alt="truck Icon">
                                    </div>
                                    <div class="feature-info pt-3 ps-2">
                                        <h4>Online Support</h4>
                                        <p>Ensure the product quality</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Feature Section -->

    <!-- Product Section -->
    <div class="product mt-100">
        <div class="container">
            <div class="section-title">
                <h2>New Arrivals</h2>
            </div>
            <div class="mt-0">
                <div class="owl-carousel product-slider">
                    <div class="card product-card mx-2 mb-3">
                        <a href="product-details.php">
                            <img src="../assets/images/product/17.png" class="card-img-top image-first" alt="eCommerce Template">
                            <img src="../assets/images/product/18.png" class="card-img-top image-second" alt="eCommerce Template">
                        </a>
                        <div class="card-body pt-0">
                            <div class="icons">
                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist"><i class="bi bi-heart"></i></a>
                            </div>
                            <span class="discount-badge">20% OFF</span>
                        </div>
                        <div class="product-price px-3 pb-2">
                            <h5 class="card-title"><a href="product-details.php">
                                    Seater beige
                                </a>
                            </h5>
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
                                <span class="sell-price">$12.00</span>
                                <span class="text-muted strike-through"><s>$15.00</s></span>
                            </div>
                        </div>
                        <div class="d-block mb-2">
                            <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="card product-card mx-2 mb-3">
                        <a href="product-details.php">
                            <img src="../assets/images/product/19.png" class="card-img-top image-first" alt="eCommerce Template">
                            <img src="../assets/images/product/20.png" class="card-img-top image-second" alt="eCommerce Template">
                        </a>
                        <div class="card-body pt-0">
                            <div class="icons">
                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist"><i class="bi bi-heart"></i></a>
                            </div>
                            <span class="discount-badge">10% OFF</span>
                        </div>
                        <div class="product-price px-3 pb-2">
                            <h5 class="card-title"><a href="product-details.php">
                                    Layer rack
                                </a>
                            </h5>
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
                                <span class="sell-price">$9.00</span>
                                <span class="text-muted strike-through"><s>$10.00</s></span>
                            </div>
                        </div>
                        <div class="d-block mb-2">
                            <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="card product-card mx-2 mb-3">
                        <a href="product-details.php">
                            <img src="../assets/images/product/21.png" class="card-img-top image-first" alt="eCommerce Template">
                            <img src="../assets/images/product/22.png" class="card-img-top image-second" alt="eCommerce Template">
                        </a>
                        <div class="card-body pt-0">
                            <div class="icons">
                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist"><i class="bi bi-heart"></i></a>
                            </div>
                            <!-- <span class="discount-badge">5% OFF</span> -->
                        </div>
                        <div class="product-price px-3 pb-2">
                            <h5 class="card-title"><a href="product-details.php">
                                    Blue Sofa
                                </a>
                            </h5>
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
                                <span class="sell-price">$10.00</span>
                                <span class="text-muted strike-through"><s>$12.00</s></span>
                            </div>
                        </div>
                        <div class="d-block mb-2">
                            <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="card product-card mx-2 mb-3">
                        <a href="product-details.php">
                            <img src="../assets/images/product/23.png" class="card-img-top image-first" alt="eCommerce Template">
                            <img src="../assets/images/product/24.png" class="card-img-top image-second" alt="eCommerce Template">
                        </a>
                        <div class="card-body pt-0">
                            <div class="icons">
                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist"><i class="bi bi-heart"></i></a>
                            </div>
                            <span class="discount-badge">30% OFF</span>
                        </div>
                        <div class="product-price px-3 pb-2">
                            <h5 class="card-title"><a href="product-details.php">
                                    Wooden
                                </a>
                            </h5>
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
                                <span class="sell-price">$12.00</span>
                                <span class="text-muted strike-through"><s>$15.00</s></span>
                            </div>
                        </div>
                        <div class="d-block mb-2">
                            <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="card product-card mx-2 mb-3">
                        <a href="product-details.php">
                            <img src="../assets/images/product/25.png" class="card-img-top image-first" alt="eCommerce Template">
                            <img src="../assets/images/product/26.png" class="card-img-top image-second" alt="eCommerce Template">
                        </a>
                        <div class="card-body pt-0">
                            <div class="icons">
                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Wishlist"><i class="bi bi-heart"></i></a>
                            </div>
                            <span class="discount-badge">10% OFF</span>
                        </div>
                        <div class="product-price px-3 pb-2">
                            <h5 class="card-title"><a href="product-details.php">
                                    Coffe Table
                                </a>
                            </h5>
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
                                <span class="sell-price">$12.00</span>
                                <span class="text-muted strike-through"><s>$15.00</s></span>
                            </div>
                        </div>
                        <div class="d-block mb-2">
                            <div class="d-flex flex-column flex-sm-column flex-md-column flex-lg-row justify-content-between px-2">
                                <a href="cart.php" class="btn btn-primary  mb-2 mb-lg-0">Add to Cart</a>
                                <a href="checkout.php" class="btn btn-secondary ms-lg-1">Buy Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="products.php" class="btn btn-primary">View All</a>
            </div>
        </div>
    </div>
    <!-- Product Section End -->

    <!-- Start Blog Section -->
    <div class="blog-section mt-100">
        <div class="container">
            <div class="section-title">
                <h2>Latest Blog</h2>
            </div>
            <div class="owl-carousel  owl-theme blog-slider">
                <div class="blog-item p-1">
                    <div class="blog-wraper">
                        <div class="blog-header">
                            <div class="mb-3">
                                <a href="../blog-single.html">
                                    <div class="img-zoom">
                                        <img src="../assets/images/blog-1.png" alt="eCommerce Html Template" class="img-fluid w-100">
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="blog-body pb-4 px-3">
                            <div class="row row-cols-xl-2 row-cols-lg-2 row-cols-1 row-cols-md-1 blog-info">
                                <div><i class="bi bi-calendar"></i><span class="ms-2">November 12, 2023</span></div>
                                <div class="d-none d-sm-none d-md-none d-lg-block"><i class="bi bi-chat"></i><span class="ms-2">0 comments</span></div>
                            </div>
                            <div>
                                <h2 class="h5 blog-title mt-3">
                                    <a href="../blog-single.html" class="text-inherit">Lorem ipsum dolor sit amet consectetur, adipisicing elit. Consequatur voluptates excepturi enim!</a>
                                </h2>
                                <div class="text-muted mt-3">
                                    <a href="../blog-single.html" class="btn btn-primary btn-sm">Read More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="blog-item p-1">
                    <div class="blog-wraper">
                        <div class="blog-header">
                            <div class="mb-3">
                                <a href="../blog-single.html">
                                    <div class="img-zoom">
                                        <img src="../assets/images/blog-2.png" alt="eCommerce Html Template" class="img-fluid w-100">
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="blog-body pb-4 px-3">
                            <div class="row row-cols-xl-2 row-cols-lg-2 row-cols-1 row-cols-md-1 blog-info">
                                <div><i class="bi bi-calendar"></i><span class="ms-2">November 12, 2023</span></div>
                                <div class="d-none d-sm-none d-md-none d-lg-block"><i class="bi bi-chat"></i><span class="ms-2">0 comments</span></div>
                            </div>
                            <div>
                                <h2 class="h5 blog-title mt-3">
                                    <a href="../blog-single.html" class="text-inherit">Lorem ipsum dolor sit amet consectetur, adipisicing elit. Consequatur voluptates excepturi enim!</a>
                                </h2>
                                <div class="text-muted mt-3">
                                    <a href="../blog-single.html" class="btn btn-primary btn-sm">Read More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="blog-item p-1">
                    <div class="blog-wraper">
                        <div class="blog-header">
                            <div class="mb-3">
                                <a href="../blog-single.html">
                                    <div class="img-zoom">
                                        <img src="../assets/images/blog-3.png" alt="eCommerce Html Template" class="img-fluid w-100">
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="blog-body pb-4 px-3">
                            <div class="row row-cols-xl-2 row-cols-lg-2 row-cols-1 row-cols-md-1 blog-info">
                                <div><i class="bi bi-calendar"></i><span class="ms-2">November 12, 2023</span></div>
                                <div class="d-none d-sm-none d-md-none d-lg-block"><i class="bi bi-chat"></i><span class="ms-2">0 comments</span></div>
                            </div>
                            <div>
                                <h2 class="h5 blog-title mt-3">
                                    <a href="../blog-single.html" class="text-inherit">Lorem ipsum dolor sit amet consectetur, adipisicing elit. Consequatur voluptates excepturi enim!</a>
                                </h2>
                                <div class="text-muted mt-3">
                                    <a href="../blog-single.html" class="btn btn-primary btn-sm">Read More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="blog-item p-1">
                    <div class="blog-wraper">
                        <div class="blog-header">
                            <div class="mb-3">
                                <a href="../blog-single.html">
                                    <div class="img-zoom">
                                        <img src="../assets/images/blog-4.png" alt="eCommerce Html Template" class="img-fluid w-100">
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="blog-body pb-4 px-3">
                            <div class="row row-cols-xl-2 row-cols-lg-2 row-cols-1 row-cols-md-1 blog-info">
                                <div><i class="bi bi-calendar"></i><span class="ms-2">November 12, 2023</span></div>
                                <div class="d-none d-sm-none d-md-none d-lg-block"><i class="bi bi-chat"></i><span class="ms-2">0 comments</span></div>
                            </div>
                            <div>
                                <h2 class="h5 blog-title mt-3">
                                    <a href="../blog-single.html" class="text-inherit">Lorem ipsum dolor sit amet consectetur, adipisicing elit. Consequatur voluptates excepturi enim!</a>
                                </h2>
                                <div class="text-muted mt-3">
                                    <a href="../blog-single.html" class="btn btn-primary btn-sm">Read More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Blog Section -->


    <div class="news-letter mt-50">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-6">
                            <h4 class="text-center mb-4 mb-lg-0"> <i class="bi bi-envelope me-2"></i> Subscribe Our Newsletter</h4>
                        </div>
                        <div class="col-lg-6">
                            <div class="input-group d-flex me-5">
                                <input id="searchInput" class="form-control py-10 px-20" type="email" placeholder="Enter your new address">
                                <button type="button" class="btn btn-primary bg-gradient">Subscribe</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- footer Section -->
    <footer class="mt-0">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-md-0">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-12">
                            <div class="footer_logo">
                                <img loading="lazy" src="../assets/images/logo.png" class="logo" alt="easy shop">
                            </div>
                            <div class="mt-4">
                                <p>Widgetify Inc, 456 Gadget Avenue, <br> Techtown, TX 67890, <br> United States of America</p>
                                <h3 class="h5 fw-bold">(987) 654-3210</h3>
                                <p>info@example.com</p>
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
                                    <li><a href="#">Orders</a></li>
                                    <li><a href="#">Wishlist</a></li>
                                    <li><a href="#">Track Order</a></li>
                                    <li><a href="#">Manage Account</a></li>
                                </ul>

                            </div>
                        </div>
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Information</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <li><a href="#">About Us</a></li>
                                    <li><a href="#">Return Policy</a></li>
                                    <li><a href="#">Privacy Policy</a></li>
                                    <li><a href="#">FAQ</a></li>
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
                                    <li><a href="#">Orders</a></li>
                                    <li><a href="#">Wishlist</a></li>
                                    <li><a href="#">Track Order</a></li>
                                    <li><a href="#">Manage Account</a></li>
                                </ul>

                            </div>
                        </div>
                        <div class="col-6">
                            <div class="footer_menu">
                                <h4 class="footer_title">Categories</h4>
                                <ul class="m-0 p-0 list-unstyled">
                                    <li><a href="#">About Us</a></li>
                                    <li><a href="#">Return Policy</a></li>
                                    <li><a href="#">Privacy Policy</a></li>
                                    <li><a href="#">FAQ</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center py-3 mt-4 text-white px-3 copyright">
            <span>Copyright © 2024. All Rights Reserved. Themes By TemplateRise</span>
        </div>
    </footer>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/plugin/nice-select/jquery.nice-select.min.js"></script>
    <script src="../assets/plugin/OwlCarousel2-2.3.4/dist/owl.carousel.min.js"></script>
    <script src="../assets/plugin/nouislider/nouislider.min.js"></script>
    <script src="../assets/plugin/slick/slick.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>