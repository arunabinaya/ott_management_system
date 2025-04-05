<?php 
$page_title = (isset($_GET['id'])) ? 'Edit Live TV Channel' : 'Create Live TV Channel';
include("includes/header.php");
require("includes/lb_helper.php");
require("language/language.php");

$page_save = (isset($_GET['id'])) ? 'Save' : 'Create';


// Get next available stream_id
$next_channel_id = 1;
$channel_id_qry = "SELECT MAX(CAST(channel_id AS UNSIGNED)) as max_id FROM tbl_live_tv_channel";
$channel_id_result = mysqli_query($mysqli, $channel_id_qry);
if ($channel_id_result && $row = mysqli_fetch_assoc($channel_id_result)) {
    $next_channel_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
}

// Handle AJAX request for categories
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ob_clean();
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $language_id = $input['language_id'] ?? '';
    
    $response = ['status' => 'error', 'message' => '', 'categories' => []];
    
    if (empty($language_id)) {
        $response['message'] = 'No language ID provided';
    } elseif (!$mysqli) {
        $response['message'] = 'Database connection failed';
    } else {
        $qry = "SELECT id, category_name FROM tbl_live_tv_category WHERE language_id = ? AND category_status = '1'";
        $stmt = mysqli_prepare($mysqli, $qry);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $language_id);
            $success = mysqli_stmt_execute($stmt);
            
            if ($success) {
                $result = mysqli_stmt_get_result($stmt);
                $categories = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $categories[] = [
                        'id' => $row['id'],
                        'category_name' => $row['category_name']
                    ];
                }
                $response = [
                    'status' => 'success',
                    'categories' => $categories,
                    'message' => 'Found ' . count($categories) . ' categories'
                ];
            } else {
                $response['message'] = 'Query execution failed: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Query preparation failed: ' . mysqli_error($mysqli);
        }
    }
    
    echo json_encode($response);
    exit;
}


// Fetch languages
$language_qry = "SELECT * FROM tbl_live_language WHERE language_status='1'";
$language_result = mysqli_query($mysqli, $language_qry);

// Fetch existing channel data for editing
if (isset($_GET['id'])) {
    $qry = "SELECT * FROM tbl_live_tv_channel WHERE id = '" . $_GET['id'] . "'";
    $result = mysqli_query($mysqli, $qry) or die(mysqli_error($mysqli));
    $row = mysqli_fetch_assoc($result);
}

// Handle form submission
if (isset($_POST['submit'])) {
    $image = $_POST['image_link'];
    if ($_FILES['image']['name'] != "" && $_FILES['image']['error'] == 0) {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/images/channels/" . $image);
    } elseif (isset($_GET['id']) && $image == "") {
        $image = $row['image'];
    }

    // Process drm_headers
    $drm_headers = [];
    if (isset($_POST['drm_header_key'])) {
        foreach ($_POST['drm_header_key'] as $index => $key) {
            if (!empty($key) && !empty($_POST['drm_header_value'][$index])) {
                $drm_headers[$key] = $_POST['drm_header_value'][$index];
            }
        }
    }
    $drm_headers_json = json_encode($drm_headers);

    // Process drm_license_headers
    $drm_license_headers = [];
    if (isset($_POST['drm_license_header_key'])) {
        foreach ($_POST['drm_license_header_key'] as $index => $key) {
            if (!empty($key) && !empty($_POST['drm_license_header_value'][$index])) {
                $drm_license_headers[$key] = $_POST['drm_license_header_value'][$index];
            }
        }
    }
    $drm_license_headers_json = json_encode($drm_license_headers);

    $data = array( 
	    'channel_id' => cleanInput($_POST['channel_id']),
        'language_id' => cleanInput($_POST['language_id']),
        'category_id' => cleanInput($_POST['category_id']),
        'description' => cleanInput($_POST['description']),
        'image' => $image,
        'name' => cleanInput($_POST['name']),
        'url' => cleanInput($_POST['url']),
        'drm_headers' => $drm_headers_json,
        'drm_type' => cleanInput($_POST['drm_type']),
        'licence_url' => cleanInput($_POST['licence_url']),
        'drm_licence_headers' => $drm_license_headers_json
    );

    if (isset($_GET['add'])) {
        $qry = Insert('tbl_live_tv_channel', $data);
        $_SESSION['msg'] = "10";
        header("Location: manage_tv.php");
    } else {
        $qry = Update('tbl_live_tv_channel', $data, "WHERE id = '" . $_POST['id'] . "'");
        $_SESSION['msg'] = "11";
        header("Location: create_tv.php?id=" . $_POST['id']);
    }
    $_SESSION['class'] = 'success';
    exit;
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
        
        <div class="card h-100">
            <div class="card-body p-4">
                <h5 class="mb-3"><?=$page_title ?></h5>
                <form action="" name="addedit_channel" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?=(isset($_GET['id'])) ? $_GET['id'] : ''?>">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
						    <div class="mb-3">
                                <label>Channel ID <span class="text-danger">*</span></label>
                                <input type="text" name="channel_id" class="form-control" value="<?php if (isset($_GET['id'])) echo $row['channel_id']; ?>" placeholder="<?php echo !isset($_GET['id']) ? $next_channel_id : ''; ?>" required>
                            </div>
							
                            <div class="mb-3">
                                <label>Language <span class="text-danger">*</span></label>
                                <select name="language_id" id="language_select" class="form-control" required>
                                    <option value="">Select Language</option>
                                    <?php 
                                    mysqli_data_seek($language_result, 0);
                                    while($lang_row = mysqli_fetch_assoc($language_result)) { ?>
                                    <option value="<?php echo $lang_row['id']; ?>" 
                                    <?php if(isset($_GET['id']) && $row['language_id'] == $lang_row['id']) echo 'selected';?>>
                                    <?php echo $lang_row['language_name']; ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_select" class="form-control" required>
                                    <option value="">Select Category</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Description <span class="text-danger"></span></label>
                                <textarea name="description" class="form-control" required><?php if (isset($_GET['id'])) echo $row['description']; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label>Image Upload <?php if (!isset($_GET['id'])) echo '<span class="text-danger"></span>'; ?></label>
                                <input type="file" name="image" class="form-control" <?php if (!isset($_GET['id'])); ?> accept="image/*">
                            </div>

                            <div class="mb-3">
                                <label>Or Image URL</label>
                                <input type="url" name="image_link" class="form-control" value="<?php if (isset($_GET['id']) && !file_exists("uploads/images/channels/" . $row['image'])) echo $row['image']; ?>" placeholder="https://example.com/image.jpg">
                            </div>

                            <div class="mb-3">
                                <label>Image Preview</label><br>
                                <img src="<?php if (isset($_GET['id'])) echo (filter_var($row['image'], FILTER_VALIDATE_URL) ? $row['image'] : "uploads/images/channels/" . $row['image']); ?>" alt="Preview" style="max-width: 200px; <?php if (!isset($_GET['id']) || !$row['image']) echo 'display:none;'; ?>" id="preview_image">
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?php if (isset($_GET['id'])) echo $row['name']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label>URL <span class="text-danger">*</span></label>
                                <input type="url" name="url" class="form-control" value="<?php if (isset($_GET['id'])) echo $row['url']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label>DRM Headers</label>
                                <div id="drm_headers_container">
                                    <?php if (isset($_GET['id']) && $row['drm_headers']) {
                                        $headers = json_decode($row['drm_headers'], true);
                                        foreach ($headers as $key => $value) { ?>
                                            <div class="input-group mb-2">
                                                <input type="text" name="drm_header_key[]" class="form-control" value="<?php echo $key; ?>" placeholder="Key (e.g., User-Agent)">
                                                <input type="text" name="drm_header_value[]" class="form-control" value="<?php echo $value; ?>" placeholder="Value">
                                                <button type="button" class="btn btn-danger remove-header">-</button>
                                            </div>
                                        <?php }
                                    } else { ?>
                                        <div class="input-group mb-2">
                                            <input type="text" name="drm_header_key[]" class="form-control" placeholder="Key (e.g., User-Agent)">
                                            <input type="text" name="drm_header_value[]" class="form-control" placeholder="Value">
                                            <button type="button" class="btn btn-danger remove-header">-</button>
                                        </div>
                                        <div class="input-group mb-2">
                                            <input type="text" name="drm_header_key[]" class="form-control" placeholder="Key (e.g., Referer)">
                                            <input type="text" name="drm_header_value[]" class="form-control" placeholder="Value">
                                            <button type="button" class="btn btn-danger remove-header">-</button>
                                        </div>
                                    <?php } ?>
                                </div>
                                <button type="button" class="btn btn-secondary" id="add_drm_header">Add Header</button>
                            </div>

                            <div class="mb-3">
                                <label>DRM Type</label>
                                <select name="drm_type" class="form-control">
                                    <option value="">None</option>
                                    <option value="CLEARKEY" <?php if (isset($_GET['id']) && $row['drm_type'] == 'CLEARKEY') echo 'selected'; ?>>Clearkey</option>
                                    <option value="PLAYREADY" <?php if (isset($_GET['id']) && $row['drm_type'] == 'PLAYREADY') echo 'selected'; ?>>Playready</option>
                                    <option value="WIDEVINE" <?php if (isset($_GET['id']) && $row['drm_type'] == 'WIDEVINE') echo 'selected'; ?>>Widevine</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>License URL</label>
                                <input type="url" name="licence_url" class="form-control" value="<?php if (isset($_GET['id'])) echo $row['licence_url']; ?>">
                            </div>

                            <div class="mb-3">
                                <label>DRM License Headers</label>
                                <div id="drm_license_headers_container">
                                    <?php if (isset($_GET['id']) && $row['drm_licence_headers']) {
                                        $license_headers = json_decode($row['drm_licence_headers'], true);
                                        foreach ($license_headers as $key => $value) { ?>
                                            <div class="input-group mb-2">
                                                <input type="text" name="drm_license_header_key[]" class="form-control" value="<?php echo $key; ?>" placeholder="Key">
                                                <input type="text" name="drm_license_header_value[]" class="form-control" value="<?php echo $value; ?>" placeholder="Value">
                                                <button type="button" class="btn btn-danger remove-header">-</button>
                                            </div>
                                        <?php }
                                    } else { ?>
                                        <div class="input-group mb-2">
                                            <input type="text" name="drm_license_header_key[]" class="form-control" placeholder="Key">
                                            <input type="text" name="drm_license_header_value[]" class="form-control" placeholder="Value">
                                            <button type="button" class="btn btn-danger remove-header">-</button>
                                        </div>
                                        <div class="input-group mb-2">
                                            <input type="text" name="drm_license_header_key[]" class="form-control" placeholder="Key">
                                            <input type="text" name="drm_license_header_value[]" class="form-control" placeholder="Value">
                                            <button type="button" class="btn btn-danger remove-header">-</button>
                                        </div>
                                    <?php } ?>
                                </div>
                                <button type="button" class="btn btn-secondary" id="add_drm_license_header">Add Header</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-center">
                        <button type="submit" name="submit" class="btn btn-primary" style="min-width: 120px;"><?=$page_save?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<!-- End: main -->

<script>
document.addEventListener('DOMContentLoaded', function() { // Opening {
    const languageSelect = document.getElementById('language_select');
    const categorySelect = document.getElementById('category_select');
    
    function updateCategories() {
        const selectedLanguage = languageSelect.value;
        console.log('Selected Language:', selectedLanguage); // Debug
        categorySelect.innerHTML = '<option value="">Loading Categories...</option>';

        if (!selectedLanguage) {
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            return;
        }

        fetch('<?php echo basename($_SERVER['PHP_SELF']); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({language_id: selectedLanguage})
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data); // Debug
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            if (data.status === 'success' && data.categories && data.categories.length > 0) {
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.text = category.category_name;
                    <?php if(isset($_GET['id'])) { ?>
                        if (category.id.toString() === '<?php echo $row['category_id']; ?>') {
                            option.selected = true;
                        }
                    <?php } ?>
                    categorySelect.appendChild(option);
                });
            } else {
                categorySelect.innerHTML = '<option value="">No categories available: ' + (data.message || 'No data') + '</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            categorySelect.innerHTML = '<option value="">Failed to load categories: ' + error.message + '</option>';
        });
    }
    
    languageSelect.addEventListener('change', updateCategories);
    updateCategories();

// Image preview
document.querySelector('input[name="image"]').addEventListener('change', function(e) {
    const preview = document.getElementById('preview_image');
    const file = e.target.files[0];
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    }
});
document.querySelector('input[name="image_link"]').addEventListener('input', function(e) {
    const preview = document.getElementById('preview_image');
    const url = e.target.value;
    preview.src = url;
    preview.style.display = url ? 'block' : 'none';
});

// Add and remove DRM headers
document.getElementById('add_drm_header').addEventListener('click', function() {
    const container = document.getElementById('drm_headers_container');
    const newInput = document.createElement('div');
    newInput.className = 'input-group mb-2';
    newInput.innerHTML = `
        <input type="text" name="drm_header_key[]" class="form-control" placeholder="Key">
        <input type="text" name="drm_header_value[]" class="form-control" placeholder="Value">
        <button type="button" class="btn btn-danger remove-header">-</button>
    `;
    container.appendChild(newInput);
});
document.getElementById('drm_headers_container').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-header')) {
        e.target.parentElement.remove();
    }
});

// Add and remove DRM license headers
document.getElementById('add_drm_license_header').addEventListener('click', function() {
    const container = document.getElementById('drm_license_headers_container');
    const newInput = document.createElement('div');
    newInput.className = 'input-group mb-2';
    newInput.innerHTML = `
        <input type="text" name="drm_license_header_key[]" class="form-control" placeholder="Key">
        <input type="text" name="drm_license_header_value[]" class="form-control" placeholder="Value">
        <button type="button" class="btn btn-danger remove-header">-</button>
    `;
    container.appendChild(newInput);
});
document.getElementById('drm_license_headers_container').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-header')) {
        e.target.parentElement.remove();
    }
});
});
</script>

<?php include("includes/footer.php"); ?>