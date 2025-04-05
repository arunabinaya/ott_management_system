<?php 
$page_title = "Manage Live TV Channels";
include("includes/header.php");
require("includes/lb_helper.php");
require("language/language.php");

$tableName = "tbl_live_tv_channel";   

// Search functionality
if (!isset($_GET['keyword'])) {
    $sql_query = "SELECT ch.*, l.language_name, c.category_name 
                  FROM tbl_live_tv_channel ch 
                  LEFT JOIN tbl_live_language l ON ch.language_id = l.id 
                  LEFT JOIN tbl_live_tv_category c ON ch.category_id = c.id 
                  ORDER BY ch.id ASC"; 
} else {
    $keyword = addslashes(trim($_GET['keyword']));
    $sql_query = "SELECT ch.*, l.language_name, c.category_name 
                  FROM tbl_live_tv_channel ch 
                  LEFT JOIN tbl_live_language l ON ch.language_id = l.id 
                  LEFT JOIN tbl_live_tv_category c ON ch.category_id = c.id 
                  WHERE ch.name LIKE '%$keyword%' 
                  ORDER BY ch.id DESC"; 
}
$result = mysqli_query($mysqli, $sql_query) or die(mysqli_error($mysqli));
?>

<!-- Start: main -->
<main id="nsofts_main">
    <div class="nsofts-container">
        <div class="card h-100">
            <div class="card-top d-md-inline-flex align-items-center justify-content-between py-3 px-4">
                <div class="d-inline-flex align-items-center text-decoration-none fw-semibold">
                    <span class="ps-2 lh-1"><?=$page_title ?></span>
                </div>
                <div class="d-flex mt-2 mt-md-0">
                    <form method="get" id="searchForm" action="" class="me-2">
                        <div class="input-group">
                            <input type="text" id="search_input" class="form-control" placeholder="Search here..." name="keyword" value="<?php if(isset($_GET['keyword'])){ echo $_GET['keyword'];} ?>" required="required">
                            <button class="btn btn-outline-default d-inline-flex align-items-center" type="submit">
                                <i class="ri-search-2-line"></i>
                            </button>
                        </div>
                    </form>
                    <a href="create_tv.php?add=yes" class="btn btn-primary d-inline-flex align-items-center justify-content-center">
                        <i class="ri-add-line"></i>
                        <span class="ps-1 text-nowrap d-none d-sm-block">Add Live TV</span>
                    </a>
                </div>
            </div>
            
            <div class="card-body p-4">
                <?php if (mysqli_num_rows($result) > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Image</th>
									<th scope="col">Channel ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Language</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while ($row = mysqli_fetch_array($result)) { ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td>
                                            <?php if ($row['image'] != "" && filter_var($row['image'], FILTER_VALIDATE_URL)) { ?>
                                                <img src="<?php echo $row['image']; ?>" alt="Channel Image" style="max-width: 100px; max-height: 50px;">
                                            <?php } elseif ($row['image'] != "" && file_exists("uploads/images/channels/" . $row['image'])) { ?>
                                                <img src="uploads/images/channels/<?php echo $row['image']; ?>" alt="Channel Image" style="max-width: 100px; max-height: 50px;">
                                            <?php } else { ?>
                                                <span>No Image</span>
                                            <?php } ?>
                                        </td>
										<td><?php echo $row['channel_id']; ?></td>
                                        <td><?php echo $row['name']; ?></td>
                                        <td><?php echo $row['language_name'] ? $row['language_name'] : 'Not Mapped'; ?></td>
                                        <td><?php echo $row['category_name'] ? $row['category_name'] : 'Not Mapped'; ?></td>
                                        <td>
                                            <div class="nsofts-switch d-flex align-items-center enable_disable" data-bs-toggle="tooltip" data-bs-placement="top" title="Enable / Disable">
                                                <input type="checkbox" id="enable_disable_check_<?= $i ?>" data-id="<?= $row['id'] ?>" data-table="<?=$tableName ?>" data-column="channel_status" class="cbx hidden btn_enable_disable" <?php if ($row['channel_status'] == 1) { echo 'checked'; } ?>>
                                                <label for="enable_disable_check_<?= $i ?>" class="nsofts-switch__label"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="create_tv.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                                <i class="ri-pencil-fill"></i>
                                            </a>
                                            <a href="javascript:void(0)" class="btn btn-danger btn-sm btn_delete" data-id="<?php echo $row['id']; ?>" data-table="<?=$tableName ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                <i class="ri-delete-bin-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php $i++; } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="p-5">
                        <h1 class="text-center">No data found</h1>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</main>
<!-- End: main -->
<?php include("includes/footer.php"); ?>