<?php
$page_title = "Manage Movies";
include("includes/header.php");
require("includes/lb_helper.php");

$tableName = "tbl_movie_details";

// Pagination settings
$limit = 10; // Number of movies per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search functionality
$where_clause = "";
if (isset($_GET['keyword']) && !empty(trim($_GET['keyword']))) {
    $keyword = addslashes(trim($_GET['keyword']));
    $where_clause = " WHERE name LIKE '%$keyword%' OR description LIKE '%$keyword%'";
}

// Fetch total number of movies for pagination
$total_qry = "SELECT COUNT(*) as total FROM tbl_movie_details" . $where_clause;
$total_result = mysqli_query($mysqli, $total_qry);
$total_row = mysqli_fetch_assoc($total_result);
$total_movies = $total_row['total'];
$total_pages = ceil($total_movies / $limit);

// Fetch movies for the current page
$qry = "SELECT * FROM tbl_movie_details" . $where_clause . " ORDER BY id DESC LIMIT $start, $limit";
$result = mysqli_query($mysqli, $qry) or die(mysqli_error($mysqli));

// Pagination range settings
$max_pages_to_show = 5; // Show up to 5 pages at a time
$half_range = floor($max_pages_to_show / 2);
$start_page = max(1, $page - $half_range);
$end_page = min($total_pages, $start_page + $max_pages_to_show - 1);
if ($end_page - $start_page + 1 < $max_pages_to_show) {
    $start_page = max(1, $end_page - $max_pages_to_show + 1);
}
?>

<!-- Start: main -->
<main id="nsofts_main">
    <div class="nsofts-container">
        <div class="card h-100">
            <div class="card-top d-md-inline-flex align-items-center justify-content-between py-3 px-4">
                <div class="d-inline-flex align-items-center text-decoration-none fw-semibold">
                    <span class="ps-2 lh-1"><?php echo $page_title; ?></span>
                </div>
                <div class="d-flex mt-2 mt-md-0">
                    <form method="get" id="searchForm" action="" class="me-2">
                        <div class="input-group">
                            <input type="text" id="search_input" class="form-control" placeholder="Search here..." 
                                   name="keyword" value="<?php if(isset($_GET['keyword'])){ echo htmlspecialchars($_GET['keyword']);} ?>" required="required">
                            <button class="btn btn-outline-default d-inline-flex align-items-center" type="submit">
                                <i class="ri-search-2-line"></i>
                            </button>
                        </div>
                    </form>
                    <a href="create_movie.php?add=yes" class="btn btn-primary d-inline-flex align-items-center justify-content-center">
                        <i class="ri-add-line"></i>
                        <span class="ps-1 text-nowrap d-none d-sm-block">Add Movie</span>
                    </a>
                </div>
            </div>

            <div class="card-body p-4">
                <?php if (mysqli_num_rows($result) > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">Poster</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while ($row = mysqli_fetch_assoc($result)) { ?>
                                    <tr>
                                        <td style="width: 20%;">
                                            <img src="<?php echo filter_var($row['poster'], FILTER_VALIDATE_URL) ? $row['poster'] : 'uploads/images/movies/' . $row['poster']; ?>" 
                                                 alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                                 style="width: 300px; height: 450px; object-fit: fit;" class="img-thumbnail movie-poster">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <div class="nsofts-switch d-flex align-items-center enable_disable" data-bs-toggle="tooltip" data-bs-placement="top" title="Enable / Disable">
                                                <input type="checkbox" id="enable_disable_check_<?php echo $i; ?>" 
                                                       data-id="<?php echo $row['id']; ?>" 
                                                       data-table="<?php echo $tableName; ?>" 
                                                       data-column="status" 
                                                       class="cbx hidden btn_enable_disable" 
                                                       <?php if ($row['status']) { echo 'checked'; } ?>>
                                                <label for="enable_disable_check_<?php echo $i; ?>" class="nsofts-switch__label"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                        id="actionsDropdown<?php echo $row['id']; ?>" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    Options
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="actionsDropdown<?php echo $row['id']; ?>">
                                                    <li><a class="dropdown-item" href="create_movie.php?id=<?php echo $row['id']; ?>">Edit</a></li>
                                                    <li><a class="dropdown-item" href="manage_quality.php?id=<?php echo $row['id']; ?>">Manage Quality</a></li>
                                                    <li><a class="dropdown-item text-danger btn_delete" href="javascript:void(0)" 
                                                           data-id="<?php echo $row['id']; ?>" 
                                                           data-table="<?php echo $tableName; ?>" 
                                                           data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">Delete</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php $i++; } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1) { ?>
                        <nav aria-label="Page navigation" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=1<?php echo isset($_GET['keyword']) ? '&keyword=' . urlencode($_GET['keyword']) : ''; ?>" aria-label="First">
                                        <span aria-hidden="true">First</span>
                                    </a>
                                </li>
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['keyword']) ? '&keyword=' . urlencode($_GET['keyword']) : ''; ?>" aria-label="Previous">
                                        <span aria-hidden="true">«</span>
                                    </a>
                                </li>
                                <?php for ($i = $start_page; $i <= $end_page; $i++) { ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['keyword']) ? '&keyword=' . urlencode($_GET['keyword']) : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php } ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['keyword']) ? '&keyword=' . urlencode($_GET['keyword']) : ''; ?>" aria-label="Next">
                                        <span aria-hidden="true">»</span>
                                    </a>
                                </li>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo isset($_GET['keyword']) ? '&keyword=' . urlencode($_GET['keyword']) : ''; ?>" aria-label="Last">
                                        <span aria-hidden="true">Last</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php } ?>
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

