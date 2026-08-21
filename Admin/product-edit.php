<?php

session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";
$connect = mysqli_connect($host, $username, $password, $database);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = (int)$_GET['id'];



$query = "SELECT * 
        FROM products 
        WHERE id = ?";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: products.php");
    exit;
}




$categoryQuery = "SELECT id, name 
                FROM categories 
                ORDER BY name ASC";
$categoryResult = mysqli_query($connect, $categoryQuery);




$nameErr = "";
$categoryErr = "";
$priceErr = "";
$stockErr = "";
$imageErr = "";




if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock_quantity'];
    $status = $_POST['status'];

  

    if ($name == "") {
        $nameErr = "Product name is required.";
    }



    if ($category_id == "" || !is_numeric($category_id)) {
        $categoryErr = "Category is required.";
    }


    if ($price == "") {
        $priceErr = "Price is required.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $priceErr = "Enter a valid price.";
    }

  

    if ($stock == "") {
        $stockErr = "Stock is required.";
    } elseif (!is_numeric($stock) || $stock < 0) {
        $stockErr = "Stock must be a whole number and cannot be negative.";
    }


  

    $image = $product['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] != 4) {

        if ($_FILES['image']['error'] != 0) {

            $imageErr = "Image upload failed.";
        } else {

            $imageSize = $_FILES['image']['size'];
            $imageTmp = $_FILES['image']['tmp_name'];

            if ($imageSize > 500000) {

                $imageErr = "Image size must be less than 500KB.";
            } elseif (getimagesize($imageTmp) === false) {

                $imageErr = "Please select a valid image.";
            } else {

                $imageType = mime_content_type($imageTmp);

                if (
                    $imageType != "image/jpeg" &&
                    $imageType != "image/png"
                ) {

                    $imageErr = "Only JPG and PNG images are allowed.";
                } else {

                    if ($imageType == "image/jpeg") {
                        $extension = "jpg";
                    } else {
                        $extension = "png";
                    }

                    $image = uniqid() . "." . $extension;
                }
            }
        }
    }


  

    if (
        $nameErr == "" &&
        $categoryErr == "" &&
        $priceErr == "" &&
        $stockErr == "" &&
        $imageErr == ""
    ) {

       

        if ($image != $product['image']) {

            $imageFolder = "../Images/";

            if (!move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $imageFolder . $image
            )) {

                $imageErr = "Image could not be uploaded.";
            }
        }


        if ($imageErr == "") {

            $update = "
                UPDATE products
                SET
                    category_id = ?,
                    NAME = ?,
                    description = ?,
                    price = ?,
                    stock_quantity = ?,
                    image = ?,
                    STATUS = ?
                WHERE id = ?
            ";

            $updateStmt = mysqli_prepare($connect, $update);

            mysqli_stmt_bind_param(
                $updateStmt,
                "issdissi",
                $category_id,
                $name,
                $description,
                $price,
                $stock,
                $image,
                $status,
                $product_id
            );

            if (mysqli_stmt_execute($updateStmt)) {

                

                if (
                    $image != $product['image'] &&
                    !empty($product['image'])
                ) {

                    $oldImage = "../Images/" . $product['image'];

                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }

                header("Location: products.php?message=product_updated");
                exit;
            }
        }
    }
}

?>


<!doctype html>
<html lang="en">

<head>

    <title>Edit Product</title>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .current-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>

</head>


<body>

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    <i class="fa-solid fa-pen-to-square me-2"></i>
                    Edit Product
                </h2>

                <p class="text-muted mb-0">
                    Update product information
                </p>

            </div>

            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i>
                Back
            </a>

        </div>


        <div class="card">

            <div class="card-body p-4">

                <form method="POST" enctype="multipart/form-data">

                   

                    <div class="mb-3">

                        <label class="form-label">
                            Product Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['name'] ?? $product['NAME']) ?>">

                        <?php if ($nameErr != ""): ?>

                            <small class="text-danger">
                                <?= $nameErr ?>
                            </small>

                        <?php endif; ?>

                    </div>


                 

                    <div class="mb-3">

                        <label class="form-label">
                            Category
                        </label>

                        <select name="category_id" class="form-select">

                            <option value="">
                                Select Category
                            </option>

                            <?php while ($category = mysqli_fetch_assoc($categoryResult)): ?>

                                <option
                                    value="<?= $category['id'] ?>"
                                    <?= (($_POST['category_id'] ?? $product['category_id']) == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>

                            <?php endwhile; ?>

                        </select>

                        <?php if ($categoryErr != ""): ?>

                            <small class="text-danger">
                                <?= $categoryErr ?>
                            </small>

                        <?php endif; ?>

                    </div>


                 

                    <div class="mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                                name="description"
                                rows="4"
                                class="form-control text-start"><?= htmlspecialchars($_POST['description'] ?? $product['description']) ?></textarea>

                    </div>


                 

                    <div class="mb-3">

                        <label class="form-label">
                            Price
                        </label>

                        <input
                            type="number"
                            name="price"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>">

                        <?php if ($priceErr != ""): ?>

                            <small class="text-danger">
                                <?= $priceErr ?>
                            </small>

                        <?php endif; ?>

                    </div>


                 

                    <div class="mb-3">

                        <label class="form-label">
                            Stock Quantity
                        </label>

                        <input
                            type="number"
                            name="stock_quantity"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['stock_quantity'] ?? $product['stock_quantity']) ?>">

                        <?php if ($stockErr != ""): ?>

                            <small class="text-danger">
                                <?= $stockErr ?>
                            </small>

                        <?php endif; ?>

                    </div>


                    

                    <div class="mb-3">

                        <label class="form-label">
                            Current Image
                        </label>

                        <br>

                        <?php

                        $imagePath = "../Images/" . $product['image'];

                        ?>

                        <?php if (!empty($product['image']) && file_exists($imagePath)): ?>

                            <img
                                src="<?= htmlspecialchars($imagePath) ?>"
                                class="current-image"
                                alt="Product Image">

                        <?php else: ?>

                            <span class="text-muted">
                                No image
                            </span>

                        <?php endif; ?>

                    </div>


                   

                    <div class="mb-3">

                        <label class="form-label">
                            Change Image
                        </label>

                        <input
                            type="file"
                            name="image"
                            class="form-control"
                            accept=".jpg,.jpeg,.png">

                        <?php if ($imageErr != ""): ?>

                            <br>

                            <small class="text-danger">
                                <?= $imageErr ?>
                            </small>

                        <?php endif; ?>

                    </div>

                  

                    <div class="mb-4">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status" class="form-select">

                            <option
                                value="Active"
                                <?= ($_POST['status'] ?? $product['STATUS']) == "Active" ? "selected" : "" ?>>
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?= ($_POST['status'] ?? $product['STATUS']) == "Inactive" ? "selected" : "" ?>>
                                Inactive
                            </option>

                        </select>

                    </div>


                    <button type="submit" class="btn btn-primary">
                        Update Product

                    </button>

                    <a
                        href="products.php"
                        class="btn btn-secondary">
                        Cancel
                    </a>

                </form>

            </div>

        </div>

    </div>

</body>

</html>

<?php

mysqli_close($connect);

?>