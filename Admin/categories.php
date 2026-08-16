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

// Admin authentication

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";


// ADD CATEGORY

if (isset($_POST['add_category'])) {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    if ($name === "") {

        $error = "Category name is required.";
    } else {

        $check = mysqli_prepare($connect, "SELECT id FROM categories WHERE name = ?");

        mysqli_stmt_bind_param($check, "s", $name);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "Category name already exists.";
        } else {

            $stmt = mysqli_prepare(
                $connect,
                "INSERT INTO categories
                (name, description, status)
                VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $name,
                $description,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Category added successfully.";
            } else {

                $error = "Failed to add category.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}



// UPDATE CATEGORY

if (isset($_POST['update_category'])) {

    $id = (int) $_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    if ($name === "") {

        $error = "Category name is required.";
    } else {
        $check = mysqli_prepare(
            $connect,
            "SELECT id
             FROM categories
             WHERE name = ?
             AND id != ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "si",
            $name,
            $id
        );

        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "Another category with this name already exists.";
        } else {

            $stmt = mysqli_prepare(
                $connect,
                "UPDATE categories
                 SET name = ?,
                    description = ?,
                    status = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $name,
                $description,
                $status,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Category updated successfully.";
            } else {

                $error = "Failed to update category.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}


// DELETE / DEACTIVATE CATEGORY

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    $stmt = mysqli_prepare(
        $connect,
        "UPDATE categories
        SET status = 'Inactive'
        WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {

        $message = "Category deactivated successfully.";
    } else {

        $error = "Failed to deactivate category.";
    }

    mysqli_stmt_close($stmt);
}


// ACTIVATE CATEGORY

if (isset($_GET['activate'])) {

    $id = (int) $_GET['activate'];

    $stmt = mysqli_prepare(
        $connect,
        "UPDATE categories
        SET status = 'Active'
        WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {

        $message = "Category activated successfully.";
    } else {

        $error = "Failed to activate category.";
    }

    mysqli_stmt_close($stmt);
}


// GET CATEGORY FOR EDIT

$editCategory = null;

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];

    $stmt = mysqli_prepare(
        $connect,
        "SELECT *
        FROM categories
        WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $resultEdit = mysqli_stmt_get_result($stmt);

    $editCategory = mysqli_fetch_assoc($resultEdit);

    mysqli_stmt_close($stmt);
}


// FETCH ALL CATEGORIES

$result = mysqli_query(
    $connect,
    "SELECT *
     FROM categories
     ORDER BY id ASC"
);

?>

<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <title>Category Management</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />
</head>

<body>

    <div class="container mt-5">

        <!-- HEADER -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h2>Category Management</h2>

            <a href="dashboard.php"
                class="btn btn-info">
                Dashboard
            </a>

        </div>


        <!-- SUCCESS MESSAGE -->

        <?php if ($message != ""): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if ($error != ""): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- ADD / EDIT CATEGORY FORM -->

        <div class="card mb-4">

            <div class="card-header">

                <?php if ($editCategory): ?>

                    <h5 class="mb-0">Edit Category</h5>

                <?php else: ?>

                    <h5 class="mb-0">Add Category</h5>

                <?php endif; ?>

            </div>


            <div class="card-body">

                <form method="POST">

                    <?php if ($editCategory): ?>

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $editCategory['id'] ?>">

                    <?php endif; ?>


                    <!-- CATEGORY NAME -->

                    <div class="mb-3">

                        <label class="form-label">
                            <b>Category Name</b>
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= $editCategory ? htmlspecialchars($editCategory['name']) : '' ?>"
                            required>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="mb-3">

                        <label class="form-label">
                            <b>Description</b>
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="3"><?= $editCategory ? htmlspecialchars($editCategory['description']) : '' ?></textarea>

                    </div>


                    <!-- STATUS -->

                    <div class="mb-3">

                        <label class="form-label">
                            <b>Status</b>
                        </label>

                        <select name="status"
                            class="form-select">

                            <option value="Active"
                                <?= ($editCategory && $editCategory['status'] == 'Active') ? 'selected' : '' ?>>
                                Active
                            </option>

                            <option value="Inactive"
                                <?= ($editCategory && $editCategory['status'] == 'Inactive') ? 'selected' : '' ?>>
                                Inactive
                            </option>

                        </select>

                    </div>


                    <?php if ($editCategory): ?>

                        <button
                            type="submit"
                            name="update_category"
                            class="btn btn-primary">
                            Update Category
                        </button>

                        <a
                            href="categories.php"
                            class="btn btn-secondary">
                            Cancel
                        </a>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="add_category"
                            class="btn btn-success">
                            Add Category
                        </button>

                    <?php endif; ?>

                </form>

            </div>

        </div>


        <!-- CATEGORY LIST -->

        <div class="card">

            <div class="card-header">

                <h5 class="mb-0">
                    All Categories
                </h5>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead class="table-dark">
                            <tr>
                                <th rowspan="2">ID</th>
                                <th rowspan="2">Category Name</th>
                                <th rowspan="2">Description</th>
                                <th rowspan="2">Status</th>
                                <th rowspan="2">Created Date</th>
                                <th colspan="2" class="text-center">Actions</th>
                            </tr>
                        </thead>


                        <tbody>

                            <?php if (mysqli_num_rows($result) > 0): ?>

                                <?php while ($category = mysqli_fetch_assoc($result)): ?>

                                    <tr>

                                        <td>
                                            <?= $category['id'] ?>
                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $category['name']
                                            ) ?>
                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $category['description']
                                            ) ?>
                                        </td>


                                        <td>

                                            <?php if (
                                                $category['status'] == 'Active'
                                            ): ?>

                                                <span class="badge bg-success">
                                                    Active
                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-secondary">
                                                    Inactive
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>
                                            <?= $category['created_at'] ?>
                                        </td>


                                        <!-- EDIT -->
                                        <td>
                                            <a
                                                href="?edit=<?= $category['id'] ?>"
                                                class="btn btn-sm btn-primary">
                                                Edit
                                            </a>
                                        </td>

                                        <!-- ACTIVATE / DEACTIVATE -->
                                        <td>
                                            <?php if ($category['status'] == 'Active'): ?>

                                                <a
                                                    href="?delete=<?= $category['id'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm(
                'Are you sure you want to deactivate this category?')">
                                                    Deactivate
                                                </a>

                                            <?php else: ?>

                                                <a
                                                    href="?activate=<?= $category['id'] ?>"
                                                    class="btn btn-sm btn-success">
                                                    Activate
                                                </a>

                                            <?php endif; ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="7" class="text-center">
                                        No categories found.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
</body>

</html>