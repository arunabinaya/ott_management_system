<?php 
$page_title = (isset($_GET['id'])) ? 'Edit Language' : 'Create Language';
include("includes/header.php");
require("includes/lb_helper.php");
require("language/language.php");

$page_save = (isset($_GET['id'])) ? 'Save' : 'Create';

// Suggest the next lang_id when creating a new language
$suggested_lang_id = '';
if (isset($_GET['add'])) {
    $qry = "SELECT MAX(lang_id) as max_id FROM tbl_live_language";
    $result = mysqli_query($mysqli, $qry);
    $row = mysqli_fetch_assoc($result);
    $last_lang_id = $row['max_id'];
    
    $suggested_lang_id = ($last_lang_id !== null) ? $last_lang_id + 1 : 1;
}

// Handle form submission for creating a new language
if (isset($_POST['submit']) && isset($_GET['add'])) {
    $image = $_POST['image_link']; // Default to link if provided
    if ($_FILES['image']['name'] != "" && $_FILES['image']['error'] == 0) {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image);
    }
    
    $data = array( 
        'lang_id' => cleanInput($_POST['lang_id']),
        'image' => $image,
        'language_name' => cleanInput($_POST['language_name'])
    );  
    
    $qry = Insert('tbl_live_language', $data);
    
    $_SESSION['msg'] = "10";
    $_SESSION['class'] = 'success';
    header("Location: manage_language.php");
    exit;
}

// Fetch existing language data for editing
if (isset($_GET['id'])) {
    $qry = "SELECT * FROM tbl_live_language WHERE id = '" . $_GET['id'] . "'";
    $result = mysqli_query($mysqli, $qry);
    $row = mysqli_fetch_assoc($result);
}

// Handle form submission for editing an existing language
if (isset($_POST['submit']) && isset($_POST['id'])) {
    $image = $_POST['image_link']; // Default to link if provided
    if ($_FILES['image']['name'] != "" && $_FILES['image']['error'] == 0) {
        if ($row['image'] != "" && file_exists("uploads/uploads/images/languages/" . $row['image'])) {
            unlink("uploads/" . $row['image']);
        }
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/images/languages/" . $image);
    } elseif ($image == "" && $row['image'] != "") {
        $image = $row['image']; // Keep existing image if no new upload or link
    }
    
    $data = array( 
        'lang_id' => cleanInput($_POST['lang_id']),
        'image' => $image,
        'language_name' => cleanInput($_POST['language_name'])
    );
    
    $language_edit = Update('tbl_live_language', $data, "WHERE id = '" . $_POST['id'] . "'");
    
    $_SESSION['msg'] = "11";
    $_SESSION['class'] = 'success';
    header("Location: create_language.php?id=" . $_POST['id']);
    exit;
}
?>

<!-- Start: main -->
<main id="nsofts_main">
    <div class="nsofts-container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb align-items-center">
                <li class="breadcrumb-item d-inline-flex"><a href="dashboard.php"><i class="ri-home-4-fill"></i></a></li>
                <li class="breadcrumb-item d-inline-flex active" aria-current="page"><?php echo (isset($page_title)) ? $page_title : "" ?></li>
            </ol>
        </nav>
            
        <div class="row g-4">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3"><?=$page_title ?></h5>
                        <form action="" name="addeditlanguage" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?=(isset($_GET['id'])) ? $_GET['id'] : ''?>" />
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Language Order (ID) <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="number" name="lang_id" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['lang_id']; } ?>" placeholder="<?php if (isset($_GET['add'])) { echo $suggested_lang_id; } ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Language Name <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" name="language_name" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['language_name']; } ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Image Upload <?php if (!isset($_GET['id'])) { echo '<span class="text-danger"></span>'; } ?></label>
                                <div class="col-sm-10">
                                    <input type="file" name="image" class="form-control" <?php if (!isset($_GET['id']))?> accept="image/*">
                                </div>
                            </div>
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Or Image URL</label>
                                <div class="col-sm-10">
                                    <input type="url" name="image_link" class="form-control" value="<?php if (isset($_GET['id']) && !file_exists("uploads/" . $row['image'])) { echo $row['image']; } ?>" placeholder="https://example.com/image.jpg">
                                </div>
                            </div>
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Image Preview</label>
                                <div class="col-sm-10">
                                    <?php if (isset($_GET['id']) && $row['image'] != "") { ?>
                                        <?php if (filter_var($row['image'], FILTER_VALIDATE_URL)) { ?>
                                            <img src="<?=$row['image']?>" alt="Language Image" style="max-width: 100px; margin-top: 10px;" id="preview_image">
                                        <?php } elseif (file_exists("uploads/" . $row['image'])) { ?>
                                            <img src="uploads/<?=$row['image']?>" alt="Language Image" style="max-width: 100px; margin-top: 10px;" id="preview_image">
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img src="" alt="Preview" style="max-width: 100px; margin-top: 10px; display: none;" id="preview_image">
                                    <?php } ?>
                                </div>
                            </div>
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label"> </label>
                                <div class="col-sm-10">
                                    <button type="submit" name="submit" class="btn btn-primary" style="min-width: 120px;"><?=$page_save?></button>
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

<script>
    // Preview image from file input
    document.querySelector('input[name="image"]').addEventListener('change', function(e) {
        const preview = document.getElementById('preview_image');
        const file = e.target.files[0];
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        }
    });

    // Preview image from URL input
    document.querySelector('input[name="image_link"]').addEventListener('input', function(e) {
        const preview = document.getElementById('preview_image');
        const url = e.target.value;
        if (url) {
            preview.src = url;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    });
</script>

<?php include("includes/footer.php"); ?>