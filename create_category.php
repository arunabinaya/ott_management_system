<?php 
$page_title = (isset($_GET['id'])) ? 'Edit Category' : 'Create Category';
include("includes/header.php");
require("includes/lb_helper.php");
require("language/language.php");

$page_save = (isset($_GET['id'])) ? 'Save' : 'Create';

// Database connection
global $mysqli;

// Suggest the next Genre ID when creating a new genre
$suggested_category_id = '';
if (isset($_GET['add'])) {
    $qry = "SELECT MAX(cat_id) AS max_id FROM tbl_live_tv_category";
    $result = mysqli_query($mysqli, $qry);
    $row = mysqli_fetch_assoc($result);
    $suggested_category_id = ($row['max_id'] ?? 0) + 1;
}

// Fetch existing genre data for editing
$row = [];
if (isset($_GET['id'])) {
    $qry = "SELECT * FROM tbl_live_tv_category WHERE id = '" . mysqli_real_escape_string($mysqli, $_GET['id']) . "'";
    $result = mysqli_query($mysqli, $qry);
    $row = mysqli_fetch_assoc($result);
}

// Fetch languages for the dropdown
$languages_query = "SELECT id, language_name FROM tbl_live_language WHERE language_status = 1 ORDER BY language_name ASC";
$languages_result = mysqli_query($mysqli, $languages_query);

// Handle form submission for creating a new genre
if (isset($_POST['submit']) && isset($_GET['add'])) {
    $cat_id = cleanInput($_POST['category_id']);
    $language_id = cleanInput($_POST['language_id']);
    $category_name = cleanInput($_POST['category_name']);

    $qry = "INSERT INTO tbl_live_tv_category (cat_id, language_id, category_name) 
            VALUES ('$cat_id', '$language_id', '$category_name')";

    if (mysqli_query($mysqli, $qry)) {
        $_SESSION['msg'] = "10";
        $_SESSION['class'] = 'success';
        header("Location: manage_category.php");
        exit;
    } else {
        $_SESSION['msg'] = "Error: " . mysqli_error($mysqli);
        $_SESSION['class'] = 'danger';
    }
}

// Handle form submission for editing an existing genre
if (isset($_POST['submit']) && isset($_POST['id'])) {
    $id = mysqli_real_escape_string($mysqli, $_POST['id']);
    $cat_id = cleanInput($_POST['category_id']);
    $language_id = cleanInput($_POST['language_id']);
    $category_name = cleanInput($_POST['category_name']);

    $qry = "UPDATE tbl_live_tv_category SET 
            cat_id = '$cat_id',
            language_id = '$language_id',
            category_name = '$category_name'
            WHERE id = '$id'";

    if (mysqli_query($mysqli, $qry)) {
        $_SESSION['msg'] = "11";
        $_SESSION['class'] = 'success';
        header("Location: create_category.php?id=" . $_POST['id']);
        exit;
    } else {
        $_SESSION['msg'] = "Error: " . mysqli_error($mysqli);
        $_SESSION['class'] = 'danger';
    }
}
?>

<!-- Start: main -->
<main id="nsofts_main">
    <div class="nsofts-container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb align-items-center">
                <li class="breadcrumb-item d-inline-flex"><a href="dashboard.php"><i class="ri-home-4-fill"></i></a></li>
                <li class="breadcrumb-item d-inline-flex active" aria-current="page"><?php echo $page_title; ?></li>
            </ol>
        </nav>
            
        <div class="row g-4">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3"><?= $page_title ?></h5>
                        <form action="" name="addeditgenre" method="POST">
                            <input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>" />
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Category ID <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" name="category_id" class="form-control" 
                                           value="<?= isset($_GET['id']) ? $row['cat_id'] : '' ?>" 
                                           placeholder="<?= isset($_GET['add']) ? $suggested_category_id : '' ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Language <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select name="language_id" class="form-control" required>
                                        <option value="">Select Language</option>
                                        <?php while ($lang_row = mysqli_fetch_assoc($languages_result)) { ?>
                                            <option value="<?= $lang_row['id']; ?>" 
                                                <?= (isset($row['language_id']) && $row['language_id'] == $lang_row['id']) ? 'selected' : ''; ?>>
                                                <?= $lang_row['language_name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Category Name <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" name="category_name" class="form-control" 
                                           value="<?= isset($row['category_name']) ? $row['category_name'] : '' ?>" required>
                                </div>
                            </div>
                                                    
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label"> </label>
                                <div class="col-sm-10">
                                    <button type="submit" name="submit" class="btn btn-primary" style="min-width: 120px;"><?= $page_save ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- End: main -->

<?php include("includes/footer.php"); ?>
