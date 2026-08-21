<?php

require_once "../includes/auth.php";

$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce";

$connect = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$message = "";
$error = "";


if (isset($_POST['add_category'])) {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    if ($name === "") {

        $error = "Category name is required.";
    } else {

        $check = mysqli_prepare(
            $connect,
            "SELECT id FROM categories WHERE name = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $name
        );

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



if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    $stmt = mysqli_prepare(
        $connect,
        "UPDATE categories
         SET status = 'Inactive'
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    if (mysqli_stmt_execute($stmt)) {

        $message = "Category deactivated successfully.";
    } else {

        $error = "Failed to deactivate category.";
    }

    mysqli_stmt_close($stmt);
}




if (isset($_GET['activate'])) {

    $id = (int) $_GET['activate'];

    $stmt = mysqli_prepare(
        $connect,
        "UPDATE categories
         SET status = 'Active'
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    if (mysqli_stmt_execute($stmt)) {

        $message = "Category activated successfully.";
    } else {

        $error = "Failed to activate category.";
    }

    mysqli_stmt_close($stmt);
}




$editCategory = null;

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];

    $stmt = mysqli_prepare(
        $connect,
        "SELECT *
         FROM categories
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    $resultEdit = mysqli_stmt_get_result($stmt);

    $editCategory = mysqli_fetch_assoc($resultEdit);

    mysqli_stmt_close($stmt);
}




$result = mysqli_query(
    $connect,
    "SELECT *
     FROM categories
     ORDER BY id ASC"
);

?>


<?php include "includes/header.php"; ?>


<div class="page-header">

    <div
        class="d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="page-title">

                <i class="fa-solid fa-layer-group me-2"></i>

                Category Management

            </h2>

            <p class="text-muted mb-0 mt-1">

                Add, edit and manage product categories.

            </p>

        </div>


        <a
            href="dashboard.php"
            class="btn btn-info">

            <i class="fa-solid fa-gauge me-1"></i>

            Dashboard

        </a>

    </div>

</div>




<?php if ($message != ""): ?>

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert">

        <i class="fa-solid fa-circle-check me-2"></i>

        <?= htmlspecialchars($message) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>

<?php endif; ?>




<?php if ($error != ""): ?>

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert">

        <i class="fa-solid fa-circle-exclamation me-2"></i>

        <?= htmlspecialchars($error) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>

<?php endif; ?>



<div class="card shadow-sm mb-4">

    <div class="card-header bg-white">

        <?php if ($editCategory): ?>

            <h5 class="mb-0">

                <i class="fa-solid fa-pen me-2"></i>

                Edit Category

            </h5>

        <?php else: ?>

            <h5 class="mb-0">

                <i class="fa-solid fa-plus me-2"></i>

                Add Category

            </h5>

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




            <div class="mb-3">

                <label class="form-label">

                    <strong>Category Name</strong>

                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="Enter category name"
                    value="<?= $editCategory
                                ? htmlspecialchars($editCategory['name'])
                                : '' ?>"
                    required>

            </div>




            <div class="mb-3">

                <label class="form-label">

                    <strong>Description</strong>

                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="3"
                    placeholder="Enter category description"><?= $editCategory
                                                                    ? htmlspecialchars($editCategory['description'])
                                                                    : '' ?></textarea>

            </div>




            <div class="mb-3">

                <label class="form-label">

                    <strong>Status</strong>

                </label>

                <select
                    name="status"
                    class="form-select">

                    <option
                        value="Active"
                        <?= (
                            $editCategory &&
                            $editCategory['status'] == 'Active'
                        ) ? 'selected' : '' ?>>

                        Active

                    </option>

                    <option
                        value="Inactive"
                        <?= (
                            $editCategory &&
                            $editCategory['status'] == 'Inactive'
                        ) ? 'selected' : '' ?>>

                        Inactive

                    </option>

                </select>

            </div>




            <?php if ($editCategory): ?>

                <button
                    type="submit"
                    name="update_category"
                    class="btn btn-primary">

                    <i class="fa-solid fa-floppy-disk me-1"></i>

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

                    <i class="fa-solid fa-plus me-1"></i>

                    Add Category

                </button>

            <?php endif; ?>

        </form>

    </div>

</div>




<div class="card shadow-sm">

    <div class="card-header bg-white">

        <div
            class="d-flex justify-content-between align-items-center">

            <h5 class="mb-0">

                <i class="fa-solid fa-list me-2"></i>

                All Categories

            </h5>

            <span class="badge bg-primary">

                <?= mysqli_num_rows($result) ?> Categories

            </span>

        </div>

    </div>


    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead class="table-light">

                    <tr>

                        <th>ID</th>

                        <th>Category Name</th>

                        <th>Description</th>

                        <th>Status</th>

                        <th>Created Date</th>

                        <th class="text-center">
                            Edit
                        </th>

                        <th class="text-center">
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (mysqli_num_rows($result) > 0): ?>


                        <?php while (
                            $category = mysqli_fetch_assoc($result)
                        ): ?>

                            <tr>




                                <td>

                                    <?= $category['id'] ?>

                                </td>




                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $category['name']
                                        ) ?>

                                    </strong>

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

                                    <?= htmlspecialchars(
                                        $category['created_at']
                                    ) ?>

                                </td>




                                <td class="text-center">

                                    <a
                                        href="?edit=<?= $category['id'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit Category">

                                        <i class="fa-solid fa-pen"></i>

                                    </a>

                                </td>




                                <td class="text-center">

                                    <?php if (
                                        $category['status'] == 'Active'
                                    ): ?>

                                        <a
                                            href="?delete=<?= $category['id'] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Deactivate Category"
                                            onclick="return confirm(
                                                'Are you sure you want to deactivate this category?'
                                            );">

                                            <i class="fa-solid fa-ban"></i>

                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="?activate=<?= $category['id'] ?>"
                                            class="btn btn-sm btn-outline-success"
                                            title="Activate Category">

                                            <i class="fa-solid fa-check"></i>

                                        </a>

                                    <?php endif; ?>

                                </td>


                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5">

                                <i
                                    class="fa-solid fa-layer-group fa-3x text-muted mb-3">
                                </i>

                                <h5>
                                    No Categories Found
                                </h5>

                                <p class="text-muted">

                                    No categories have been added yet.

                                </p>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php include "includes/footer.php"; ?>


<?php

mysqli_close($connect);

?>