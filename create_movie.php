<?php
$page_title = (isset($_GET['id'])) ? 'Edit Movie' : 'Create Movie';
include("includes/header.php");
require("includes/lb_helper.php");
require("language/language.php");

$page_save = (isset($_GET['id'])) ? 'Save' : 'Create';

$conn = $mysqli;

// Fetch existing movie data for editing
if (isset($_GET['id'])) {
    $qry = "SELECT * FROM tbl_movie_details WHERE id = '" . $_GET['id'] . "'";
    $result = mysqli_query($conn, $qry);
    $row = mysqli_fetch_assoc($result);
    
    $cast_ids = explode(',', $row['cast']);
    $cast_names = [];
    foreach ($cast_ids as $cast_id) {
        $cast_qry = "SELECT name FROM tbl_celebrity WHERE id = '$cast_id'";
        $cast_result = mysqli_query($conn, $cast_qry);
        $cast_row = mysqli_fetch_assoc($cast_result);
        $cast_names[] = $cast_row['name'];
    }
    $row['cast_names'] = implode(', ', $cast_names);
}

// Fetch all languages and genres
$languages_qry = "SELECT id, name FROM tbl_language WHERE status = 1";
$languages_result = mysqli_query($conn, $languages_qry);
$languages = [];
while ($lang = mysqli_fetch_assoc($languages_result)) {
    $languages[] = $lang;
}

$genres_qry = "SELECT genre_id, name FROM tbl_genre WHERE status = 1";
$genres_result = mysqli_query($conn, $genres_qry);
$genres = [];
while ($genre = mysqli_fetch_assoc($genres_result)) {
    $genres[] = $genre;
}

// Global variable to store TMDB data
$movie = null;

if (isset($_POST['submit'])) {
    $cast_ids = [];
    $cast_names = explode(',', $_POST['cast']);
    
    foreach ($cast_names as $cast_name) {
        $cast_name = trim($cast_name);
        $stmt = $conn->prepare("SELECT id, person_id FROM tbl_celebrity WHERE name = ?");
        $stmt->bind_param("s", $cast_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cast_ids[] = $row['id'];
        } else {
            $apiKey = '05902896074695709d7763505bb88b4d';
            $person_url = "https://api.themoviedb.org/3/search/person?api_key={$apiKey}&query=" . urlencode($cast_name);
            $person_data = json_decode(file_get_contents($person_url), true);
            
            $desc = "Celebrity description";
            $img = "default.jpg";
            $person_id = null;
            
            if (!empty($person_data['results'])) {
                $person = $person_data['results'][0];
                $person_id = $person['id'];
                
                $detail_url = "https://api.themoviedb.org/3/person/{$person_id}?api_key={$apiKey}";
                $detail_data = json_decode(file_get_contents($detail_url), true);
                
                $desc = !empty($detail_data['biography']) ? $detail_data['biography'] : "Celebrity description";
                if (!empty($person['profile_path'])) {
                    $img = "https://image.tmdb.org/t/p/original{$person['profile_path']}";
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO tbl_celebrity (name, description, image, person_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $cast_name, $desc, $img, $person_id);
            $stmt->execute();
            $cast_ids[] = $conn->insert_id;
        }
    }
    $cast_string = implode(',', $cast_ids);
    $languages_string = implode(',', $_POST['languages']);
    $genres_string = implode(',', $_POST['genres']);

    // Get TMDB ID from form or session (if set by search)
    $tmdb_id = cleanInput($_POST['tmdb_id']);
    if (!$tmdb_id && isset($_GET['id'])) {
        $tmdb_id = $row['tmdb_id']; // Use existing TMDB ID for edits
    }

    // Check for duplicate movie
    if ($tmdb_id) {
        $check_qry = "SELECT id FROM tbl_movie_details WHERE tmdb_id = ? AND status = 1";
        $stmt = $conn->prepare($check_qry);
        $stmt->bind_param("i", $tmdb_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0 && !isset($_GET['id'])) { // Only check for duplicates on create, not edit
            $_SESSION['msg'] = "Movie with TMDB ID $tmdb_id already exists!";
            $_SESSION['class'] = 'danger';
            header("Location: create_movie.php" . (isset($_GET['id']) ? "?id=" . $_GET['id'] : ""));
            exit;
        }
    }

    $data = array(
        'tmdb_id' => $tmdb_id,
        'backdrop' => cleanInput($_POST['backdrop']),
        'cast' => $cast_string,
        'description' => cleanInput($_POST['description']),
        'director' => cleanInput($_POST['director']),
        'duration' => cleanInput($_POST['duration']),
        'genres' => $genres_string,
        'is_adult' => $_POST['is_adult'],
        'is_anime' => $_POST['is_anime'],
        'is_premium' => $_POST['is_premium'],
        'is_premium_first' => $_POST['is_premium_first'],
        'languages' => $languages_string,
        'name' => cleanInput($_POST['name']),
        'poster' => cleanInput($_POST['poster']),
        'quality' => cleanInput($_POST['quality']),
        'rating' => cleanInput($_POST['rating']),
        'tags' => cleanInput($_POST['tags']),
        'trailer' => cleanInput($_POST['trailer']),
        'watching_count' => isset($row['watching_count']) ? $row['watching_count'] : '0',
        'year' => cleanInput($_POST['year'])
    );

    if (isset($_GET['id'])) {
        Update('tbl_movie_details', $data, "WHERE id = '" . $_POST['id'] . "'");
        $_SESSION['msg'] = "11";
        $_SESSION['class'] = 'success';
        header("Location: create_movie.php?id=" . $_POST['id']);
    } else {
        Insert('tbl_movie_details', $data);
        $_SESSION['msg'] = "10";
        $_SESSION['class'] = 'success';
        header("Location: manage_movies.php");
    }
    exit;
}
?>

<main id="nsofts_main">
    <div class="nsofts-container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb align-items-center">
                <li class="breadcrumb-item d-inline-flex"><a href="dashboard.php"><i class="ri-home-4-fill"></i></a></li>
                <li class="breadcrumb-item d-inline-flex active" aria-current="page"><?php echo $page_title ?></page></li>
            </ol>
        </nav>
            
        <div class="row g-4">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3"><?=$page_title ?></h5>
                        
                        <form action="" name="addeditmovie" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?=(isset($_GET['id'])) ? $_GET['id'] : ''?>" />
                            <input type="hidden" name="tmdb_id" id="tmdb_id" value="">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">TMDB Search</label>
                                        <div class="col-sm-9">
                                            <select id="searchType" class="form-control d-inline-block" style="width: 150px;">
                                                <option value="id">Search by TMDB ID</option>
                                            </select>
                                            <input type="text" id="tmdbInput" class="form-control d-inline-block" style="width: 200px;" placeholder="Enter movie TMDB ID">
                                            <button type="button" onclick="searchTMDB()" class="btn btn-primary">Search</button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Name <span class="text-danger">*</span></label>
                                        <div class="col-sm-9">
                                            <input type="text" name="name" id="name" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['name']; } ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Description <span class="text-danger">*</span></label>
                                        <div class="col-sm-9">
                                            <textarea name="description" id="description" class="form-control" rows="4" required><?php if (isset($_GET['id'])) { echo $row['description']; } ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Cast</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="cast" id="cast" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['cast_names']; } ?>" placeholder="Comma separated names">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Director</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="director" id="director" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['director']; } ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Duration</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="duration" id="duration" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['duration']; } ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Genres</label>
                                        <div class="col-sm-9">
                                            <select name="genres[]" id="genres" class="form-control" multiple>
                                                <?php foreach ($genres as $genre) { ?>
                                                    <option value="<?php echo $genre['genre_id']; ?>" 
                                                        <?php if (isset($row['genres']) && in_array($genre['genre_id'], explode(',', $row['genres']))) echo 'selected'; ?>>
                                                        <?php echo $genre['name']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Languages</label>
                                        <div class="col-sm-9">
                                            <select name="languages[]" id="languages" class="form-control" multiple>
                                                <?php foreach ($languages as $lang) { ?>
                                                    <option value="<?php echo $lang['id']; ?>" 
                                                        <?php if (isset($row['languages']) && in_array($lang['id'], explode(',', $row['languages']))) echo 'selected'; ?>>
                                                        <?php echo $lang['name']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Quality</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="quality" id="quality" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['quality']; } ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Rating</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="rating" id="rating" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['rating']; } ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Tags</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="tags" id="tags" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['tags']; } ?>" placeholder="Comma separated">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Trailer URL</label>
                                        <div class="col-sm-9">
                                            <input type="url" name="trailer" id="trailer" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['trailer']; } ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Year</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="year" id="year" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['year']; } ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Poster Preview <span class="text-danger">*</span></label>
                                        <div class="col-sm-9">
                                            <img src="<?php if (isset($_GET['id'])) { echo $row['poster']; } ?>" id="posterPreview" style="max-width: 200px; margin-bottom: 10px; <?php if (!isset($_GET['id'])) { echo 'display: none;'; } ?>">
                                            <input type="url" name="poster" id="poster" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['poster']; } ?>" placeholder="Poster URL" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Backdrop Preview <span class="text-danger">*</span></label>
                                        <div class="col-sm-9">
                                            <img src="<?php if (isset($_GET['id'])) { echo $row['backdrop']; } ?>" id="backdropPreview" style="max-width: 200px; margin-bottom: 10px; <?php if (!isset($_GET['id'])) { echo 'display: none;'; } ?>">
                                            <input type="url" name="backdrop" id="backdrop" class="form-control" value="<?php if (isset($_GET['id'])) { echo $row['backdrop']; } ?>" placeholder="Backdrop URL" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Is Adult </label>
                                        <div class="col-sm-9">
                                            <label class="nsofts-switch">
                                                <input type="checkbox" name="is_adult" id="is_adult" value="1" <?php if (isset($row['is_adult']) && $row['is_adult']) { echo 'checked'; } ?>>
                                                <span class="nsofts-switch-slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Is Anime</label>
                                        <div class="col-sm-9">
                                            <label class="nsofts-switch">
                                                <input type="checkbox" name="is_anime" id="is_anime" value="1" <?php if (isset($row['is_anime']) && $row['is_anime']) { echo 'checked'; } ?>>
                                                <span class="nsofts-switch-slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Premium</label>
                                        <div class="col-sm-9">
                                            <label class="nsofts-switch">
                                                <input type="checkbox" name="is_premium" id="is_premium" value="1" <?php if (isset($row['is_premium']) && $row['is_premium']) { echo 'checked'; } ?>>
                                                <span class="nsofts-switch-slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Premium First</label>
                                        <div class="col-sm-9">
                                            <label class="nsofts-switch">
                                                <input type="checkbox" name="is_premium_first" id="is_premium_first" value="1" <?php if (isset($row['is_premium_first']) && $row['is_premium_first']) { echo 'checked'; } ?>>
                                                <span class="nsofts-switch-slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label"></label>
                                        <div class="col-sm-9">
                                            <button type="submit" name="submit" class="btn btn-primary" style="min-width: 120px;"><?=$page_save?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    .nsofts-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    .nsofts-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .nsofts-switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }
    .nsofts-switch-slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .nsofts-switch-slider {
        background-color: #2196F3;
    }
    input:checked + .nsofts-switch-slider:before {
        transform: translateX(26px);
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let movie = null; // Global variable to store TMDB data

function searchTMDB() {
    const searchType = $('#searchType').val();
    const input = $('#tmdbInput').val();
    let url = `https://api.themoviedb.org/3/movie/${input}?api_key=05902896074695709d7763505bb88b4d&append_to_response=videos,credits`;

    $.getJSON(url, function(data) {
        movie = data; // Store the movie data globally
        populateForm(movie);
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('TMDB API Error:', textStatus, errorThrown);
        alert('Failed to fetch movie data. Please try again.');
    });
}

function populateForm(movie) {
    $('#name').val(movie.title);
    $('#description').val(movie.overview);
    $('#backdrop').val(`https://image.tmdb.org/t/p/original${movie.backdrop_path}`);
    $('#backdropPreview').attr('src', `https://image.tmdb.org/t/p/w200${movie.backdrop_path}`).show();
    $('#poster').val(`https://image.tmdb.org/t/p/original${movie.poster_path}`);
    $('#posterPreview').attr('src', `https://image.tmdb.org/t/p/w200${movie.poster_path}`).show();
    $('#cast').val(movie.credits?.cast.slice(0, 5).map(c => c.name).join(', ') || '');
    $('#director').val(movie.credits?.crew.find(c => c.job === 'Director')?.name || '');
    $('#duration').val(movie.runtime ? `${movie.runtime} min` : '');
    const genreIds = movie.genres.map(g => g.id);
    $('#genres').val(genreIds);
    $('#tmdb_id').val(movie.id); // Set TMDB ID
    $('#quality').val('HD');
    $('#rating').val(movie.vote_average);
    $('#tags').val(movie.tagline || '');
    const trailer = movie.videos?.results.find(v => v.type === 'Trailer');
    $('#trailer').val(trailer ? `https://www.youtube.com/watch?v=${trailer.key}` : '');
    $('#year').val(movie.release_date?.split('-')[0]);
}

// Handle checkbox values
$('input[type=checkbox]').each(function() {
    if (!this.checked) {
        $(this).val('0');
    }
    $(this).on('change', function() {
        $(this).val(this.checked ? '1' : '0');
    });
});

// Image preview updates
$('#backdrop').on('input', function() {
    $('#backdropPreview').attr('src', this.value).show();
});
$('#poster').on('input', function() {
    $('#posterPreview').attr('src', this.value).show();
});
</script>

<?php include("includes/footer.php"); ?>