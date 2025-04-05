<?php 
$page_title = (isset($_GET['id'])) ? 'Edit App Genre' : 'Create App Genre';
include("includes/header.php");
require("includes/lb_helper.php");
require("language/language.php");

$page_save = (isset($_GET['id'])) ? 'Save' : 'Create';


// Handle form submission for creating a new Genre
if (isset($_POST['submit']) && isset($_GET['add'])) {
    
    $data = array(
	    'genre_id' => cleanInput($_POST['genre_id']),
        'name' => cleanInput($_POST['name'])
    );  
    
    $qry = Insert('tbl_genre', $data);
    
    $_SESSION['msg'] = "10";
    $_SESSION['class'] = 'success';
    header("Location: manage_app_genre.php");
    exit;
}

// Fetch existing language data for editing
if (isset($_GET['id'])) {
    $qry = "SELECT * FROM tbl_genre WHERE id = '" . $_GET['id'] . "'";
    $result = mysqli_query($mysqli, $qry);
    $row = mysqli_fetch_assoc($result);
}

// Handle form submission for editing an existing language
if (isset($_POST['submit']) && isset($_POST['id'])) {
   
    $data = array( 
	    'genre_id' => cleanInput($_POST['genre_id']),
        'name' => cleanInput($_POST['name'])
    );
    
    $language_edit = Update('tbl_genre', $data, "WHERE id = '" . $_POST['id'] . "'");
    
    $_SESSION['msg'] = "11";
    $_SESSION['class'] = 'success';
    header("Location: create_app_genre.php?id=" . $_POST['id']);
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
                        <form action="" name="addeditgenre" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?=(isset($_GET['id'])) ? $_GET['id'] : ''?>" />
                            
							
							 <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">TMDB GENRE(ID) <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="number" name="genre_id" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['genre_id']; } ?>" required>
									</div>
                             </div>
                         
                            
                            
                            <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Genre Name <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" name="name" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['name']; } ?>" required>
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

<?php include("includes/footer.php"); ?>