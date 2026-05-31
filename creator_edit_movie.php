<?php
// creator_edit_movie.php
// Creator Panel: edit an existing draft movie

session_start();
require_once 'DBconn.php';
require_once 'Movie.php';

// access control, creators and admins only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['creator', 'admin'])) {
    header('Location: index.php');
    exit;
}

// get movie id from url
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($movie_id <= 0) {
    header('Location: creator_movies.php');
    exit;
}

// load the movie and make sure it belongs to this creator
$movie = new Movie();
$found = $movie->initWithId($movie_id);

if (!$found || $movie->getCreatedBy() != $_SESSION['user_id']) {
    header('Location: creator_movies.php');
    exit;
}

// block editing if already published
if ($movie->getIsPublished()) {
    header('Location: creator_movies.php?err=published');
    exit;
}

$categories = [
    1 => 'Action',
    2 => 'Comedy',
    3 => 'Horror',
    4 => 'Sci-Fi',
    5 => 'Drama'
];

$errors  = [];

// keep form values, pre-fill with existing movie data on first load
$formData = [
    'title'             => $movie->getTitle(),
    'short_description' => $movie->getShortDesc(),
    'synopsis'          => $movie->getSynopsis(),
    'release_year'      => $movie->getReleaseYear(),
    'duration_min'      => $movie->getDurationMin(),
    'selected_cats'     => Movie::getCategoryIds($movie_id)
];

// current image and media, shown as existing files
$current_image = $movie->getImageUrl();
$current_media = $movie->getMediaUrl();

// -------------------------------------------------------
// POST: process form submission
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // collect text fields
    $formData['title']             = trim($_POST['title']              ?? '');
    $formData['short_description'] = trim($_POST['short_description']  ?? '');
    $formData['synopsis']          = trim($_POST['synopsis']           ?? '');
    $formData['release_year']      = trim($_POST['release_year']       ?? '');
    $formData['duration_min']      = trim($_POST['duration_min']       ?? '');
    $formData['selected_cats']     = $_POST['categories']              ?? [];

    // server-side validation
    if (empty($formData['title']))
        $errors[] = 'Title is required.';
    if (empty($formData['short_description']))
        $errors[] = 'Short description is required.';
    if (!empty($formData['release_year'])) {
        $yr = (int)$formData['release_year'];
        if ($yr < 1888 || $yr > 2100)
            $errors[] = 'Release year must be between 1888 and 2100.';
    }
    if (!empty($formData['duration_min']) && !is_numeric($formData['duration_min']))
        $errors[] = 'Duration must be a number.';

    // new image upload, keep existing if no new file chosen
    $image_url = $current_image;
    if (!empty($_FILES['image']['name'])) {
        $imgResult = uploadFile($_FILES['image'], 'images', ['jpg','jpeg','png','webp'], 5);
        if ($imgResult['error']) {
            $errors[] = $imgResult['error'];
        } else {
            $image_url = $imgResult['path'];
        }
    }

    // new media upload, keep existing if no new file chosen
    $media_url = $current_media;
    if (!empty($_FILES['media']['name'])) {
        $mediaResult = uploadFile($_FILES['media'], 'media', ['mp4','mp3','mov','avi'], 50);
        if ($mediaResult['error']) {
            $errors[] = $mediaResult['error'];
        } else {
            $media_url = $mediaResult['path'];
        }
    }

    // save if no errors
    if (empty($errors)) {
        $movie->setTitle($formData['title']);
        $movie->setShortDesc($formData['short_description']);
        $movie->setSynopsis($formData['synopsis']);
        $movie->setReleaseYear((int)$formData['release_year']);
        $movie->setDurationMin((int)$formData['duration_min']);
        $movie->setImageUrl($image_url);
        $movie->setMediaUrl($media_url);
        $movie->setCreatedBy($_SESSION['user_id']);

        $ok = $movie->updateMovie();

        if ($ok) {
            // update category links
            Movie::setCategories($movie_id, $formData['selected_cats']);
            header('Location: creator_movies.php?edited=1');
            exit;
        } else {
            $errors[] = 'Something went wrong saving your changes. Please try again.';
        }
    }

    // update previews to reflect new uploads on error redisplay
    $current_image = $image_url;
    $current_media = $media_url;
}

// -------------------------------------------------------
// helper: handle file upload safely
// returns ['path' => '...', 'error' => ''] or ['path' => '', 'error' => '...']
// -------------------------------------------------------
function uploadFile($file, $folder, array $allowedExt, $maxMB) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt))
        return ['path' => '', 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExt)];

    if ($file['size'] > $maxMB * 1024 * 1024)
        return ['path' => '', 'error' => "File too large. Maximum size is {$maxMB}MB."];

    // create folder if it does not exist
    if (!is_dir($folder)) mkdir($folder, 0755, true);

    // unique filename to avoid collisions
    $newName = uniqid('', true) . '.' . $ext;
    $dest    = $folder . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest))
        return ['path' => '', 'error' => 'File upload failed. Check folder permissions.'];

    return ['path' => $dest, 'error' => ''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movie – Creator Panel</title>
    <style>
        * { box-sizing: border-box; }
        body      { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
        header    { background: #1a1a2e; color: #fff; padding: 16px 24px;
                    display: flex; justify-content: space-between; align-items: center; }
        header a  { color: #e0b0ff; text-decoration: none; margin-left: 16px; }

        .container { max-width: 780px; margin: 36px auto; padding: 0 20px 60px; }
        h2         { color: #1a1a2e; margin-bottom: 6px; }
        .subtitle  { color: #666; font-size: 14px; margin-bottom: 28px; }

        .card      { background: #fff; border-radius: 10px; padding: 28px 32px;
                     box-shadow: 0 2px 10px rgba(0,0,0,.08); margin-bottom: 24px; }
        .card h3   { margin: 0 0 20px; font-size: 16px; color: #1a1a2e;
                     border-bottom: 2px solid #e8e8f0; padding-bottom: 10px; }

        .form-group          { margin-bottom: 18px; }
        label                { display: block; font-size: 14px; font-weight: bold;
                               color: #333; margin-bottom: 6px; }
        label .req           { color: #dc3545; margin-left: 2px; }
        input[type=text],
        input[type=number],
        textarea,
        input[type=file]     { width: 100%; padding: 10px 12px; border: 1px solid #ccc;
                               border-radius: 6px; font-size: 14px; font-family: Arial, sans-serif;
                               transition: border-color .2s; }
        input:focus,
        textarea:focus       { outline: none; border-color: #6f42c1; }
        input.error-field,
        textarea.error-field { border-color: #dc3545; background: #fff8f8; }
        textarea             { resize: vertical; }

        .char-count          { text-align: right; font-size: 12px; color: #999; margin-top: 3px; }
        .char-count.over     { color: #dc3545; }

        .inline-group        { display: flex; gap: 16px; }
        .inline-group .form-group { flex: 1; }

        .err-label           { display: block; font-size: 12px; color: #dc3545;
                               margin-top: 4px; min-height: 16px; }

        /* categories */
        .cat-grid            { display: flex; flex-wrap: wrap; gap: 10px; }
        .cat-item            { display: flex; align-items: center; gap: 7px;
                               background: #f0eeff; border-radius: 20px; padding: 6px 14px;
                               cursor: pointer; border: 2px solid transparent;
                               transition: border-color .15s, background .15s; font-size: 14px; }
        .cat-item:has(input:checked) { border-color: #6f42c1; background: #e4dbff; }
        .cat-item input      { width: 15px; height: 15px; accent-color: #6f42c1; cursor: pointer; }

        /* existing file display */
        .existing-file       { display: flex; align-items: center; gap: 12px;
                               background: #f8f9fa; border: 1px solid #dee2e6;
                               border-radius: 6px; padding: 10px 14px; margin-bottom: 10px; }
        .existing-file img   { width: 70px; height: 46px; object-fit: cover; border-radius: 4px; }
        .existing-file span  { font-size: 13px; color: #555; }
        .existing-file .lbl  { font-size: 12px; color: #888; display: block; }

        /* file preview after new selection */
        .file-preview        { margin-top: 10px; display: none; }
        .file-preview img    { max-width: 160px; max-height: 100px;
                               border-radius: 6px; border: 1px solid #ddd; }
        .file-preview .fname { font-size: 13px; color: #555; margin-top: 5px; }

        /* alerts */
        .alert-errors        { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
                               border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
        .alert-errors ul     { margin: 8px 0 0 0; padding-left: 20px; }
        .alert-errors li     { margin-bottom: 4px; font-size: 14px; }

        /* buttons */
        .btn-row             { display: flex; gap: 12px; margin-top: 8px; }
        .btn                 { padding: 11px 26px; border-radius: 6px; font-size: 15px;
                               cursor: pointer; border: none; font-family: Arial, sans-serif; }
        .btn-save            { background: #6f42c1; color: #fff; }
        .btn-save:hover      { background: #5a32a3; }
        .btn-save:disabled   { background: #b39ddb; cursor: not-allowed; }
        .btn-cancel          { background: #e9ecef; color: #333; text-decoration: none;
                               display: inline-flex; align-items: center; }
        .btn-cancel:hover    { background: #dee2e6; }
    </style>
</head>
<body>

<header>
    <span>🎬 Creator Panel</span>
    <div>
        <a href="creator_movies.php">My Movies</a>
        <a href="creator_add_movie.php">+ Add Movie</a>
        <a href="index.php">Home</a>
        <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'creator'); ?>)</a>
    </div>
</header>

<div class="container">
    <h2>Edit Movie</h2>
    <p class="subtitle">
        Editing: <strong><?php echo htmlspecialchars($movie->getTitle()); ?></strong>
        &nbsp;—&nbsp; Changes are saved as a draft until you publish.
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert-errors">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="editMovieForm" novalidate>

        <!-- basic info -->
        <div class="card">
            <h3>Basic Information</h3>

            <div class="form-group">
                <label for="title">Title <span class="req">*</span></label>
                <input type="text" id="title" name="title" maxlength="255"
                       value="<?php echo htmlspecialchars($formData['title']); ?>"
                       onblur="validateRequired(this, 'titleErr', 'Title is required')"
                       onkeyup="handleKeyUp()">
                <span class="err-label" id="titleErr"></span>
            </div>

            <div class="form-group">
                <label for="short_description">
                    Short Description <span class="req">*</span>
                    <small style="font-weight:normal;color:#888">(shown on home page, max 500 chars)</small>
                </label>
                <textarea id="short_description" name="short_description"
                          rows="3" maxlength="500"
                          onblur="validateRequired(this, 'shortDescErr', 'Short description is required')"
                          onkeyup="updateCharCount('short_description', 'shortDescCount', 500); handleKeyUp()"
                          ><?php echo htmlspecialchars($formData['short_description']); ?></textarea>
                <div class="char-count" id="shortDescCount">0 / 500</div>
                <span class="err-label" id="shortDescErr"></span>
            </div>

            <div class="form-group">
                <label for="synopsis">
                    Synopsis
                    <small style="font-weight:normal;color:#888">(full description on movie page)</small>
                </label>
                <textarea id="synopsis" name="synopsis"
                          rows="5"><?php echo htmlspecialchars($formData['synopsis']); ?></textarea>
            </div>

            <div class="inline-group">
                <div class="form-group">
                    <label for="release_year">Release Year</label>
                    <input type="number" id="release_year" name="release_year"
                           min="1888" max="2100" placeholder="e.g. 2023"
                           value="<?php echo htmlspecialchars($formData['release_year']); ?>"
                           onblur="validateYear()"
                           onkeyup="handleKeyUp()">
                    <span class="err-label" id="yearErr"></span>
                </div>
                <div class="form-group">
                    <label for="duration_min">Duration (minutes)</label>
                    <input type="number" id="duration_min" name="duration_min"
                           min="1" max="999" placeholder="e.g. 120"
                           value="<?php echo htmlspecialchars($formData['duration_min']); ?>"
                           onblur="validateDuration()"
                           onkeyup="handleKeyUp()">
                    <span class="err-label" id="durationErr"></span>
                </div>
            </div>
        </div>

        <!-- categories -->
        <div class="card">
            <h3>Categories</h3>
            <div class="cat-grid">
                <?php foreach ($categories as $id => $name): ?>
                    <label class="cat-item">
                        <input type="checkbox" name="categories[]" value="<?php echo $id; ?>"
                            <?php echo in_array($id, $formData['selected_cats']) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- media -->
        <div class="card">
            <h3>Media</h3>

            <div class="form-group">
                <label for="image">
                    Poster Image
                    <small style="font-weight:normal;color:#888">(leave blank to keep existing)</small>
                </label>

                <!-- show existing poster -->
                <?php if ($current_image): ?>
                    <div class="existing-file">
                        <img src="<?php echo htmlspecialchars($current_image); ?>" alt="current poster">
                        <div>
                            <span class="lbl">Current poster</span>
                            <span><?php echo htmlspecialchars(basename($current_image)); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <input type="file" id="image" name="image"
                       accept=".jpg,.jpeg,.png,.webp"
                       onchange="previewImage(this)">
                <span class="err-label" id="imageErr"></span>
                <div class="file-preview" id="imagePreview">
                    <img id="imageThumb" src="" alt="new preview">
                    <div class="fname" id="imageName"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="media">
                    Trailer / Video / Audio
                    <small style="font-weight:normal;color:#888">(leave blank to keep existing)</small>
                </label>

                <!-- show existing media filename if set -->
                <?php if ($current_media): ?>
                    <div class="existing-file">
                        <span>📎</span>
                        <div>
                            <span class="lbl">Current media file</span>
                            <span><?php echo htmlspecialchars(basename($current_media)); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <input type="file" id="media" name="media"
                       accept=".mp4,.mp3,.mov,.avi"
                       onchange="showMediaName(this)">
                <span class="err-label" id="mediaErr"></span>
                <div class="file-preview" id="mediaPreview">
                    <div class="fname" id="mediaName"></div>
                </div>
            </div>
        </div>

        <!-- buttons -->
        <div class="btn-row">
            <button type="submit" class="btn btn-save" id="btnSubmit">
                Save Changes
            </button>
            <a href="creator_movies.php" class="btn btn-cancel">Cancel</a>
        </div>

    </form>
</div>

<script>
// validation helpers

function validateRequired(field, errId, msg) {
    const err = document.getElementById(errId);
    if (field.value.trim() === '') {
        field.classList.add('error-field');
        err.textContent = msg;
        return false;
    }
    field.classList.remove('error-field');
    err.textContent = '';
    return true;
}

function validateYear() {
    const field = document.getElementById('release_year');
    const err   = document.getElementById('yearErr');
    const val   = field.value.trim();
    if (val === '') { field.classList.remove('error-field'); err.textContent = ''; return true; }
    const yr = parseInt(val);
    if (isNaN(yr) || yr < 1888 || yr > 2100) {
        field.classList.add('error-field');
        err.textContent = 'Enter a year between 1888 and 2100.';
        return false;
    }
    field.classList.remove('error-field');
    err.textContent = '';
    return true;
}

function validateDuration() {
    const field = document.getElementById('duration_min');
    const err   = document.getElementById('durationErr');
    const val   = field.value.trim();
    if (val === '') { field.classList.remove('error-field'); err.textContent = ''; return true; }
    if (isNaN(val) || parseInt(val) < 1) {
        field.classList.add('error-field');
        err.textContent = 'Duration must be a positive number.';
        return false;
    }
    field.classList.remove('error-field');
    err.textContent = '';
    return true;
}

// enable submit only when required text fields are filled
// image is optional on edit since an existing one may already exist

function handleKeyUp() {
    const title     = document.getElementById('title').value.trim();
    const shortDesc = document.getElementById('short_description').value.trim();
    document.getElementById('btnSubmit').disabled = !(title && shortDesc);
}

// character counter

function updateCharCount(fieldId, countId, max) {
    const len = document.getElementById(fieldId).value.length;
    const el  = document.getElementById(countId);
    el.textContent = len + ' / ' + max;
    el.classList.toggle('over', len > max);
}

// image preview for newly chosen file

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const thumb   = document.getElementById('imageThumb');
    const name    = document.getElementById('imageName');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            thumb.src             = e.target.result;
            name.textContent      = input.files[0].name;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function showMediaName(input) {
    const preview = document.getElementById('mediaPreview');
    const name    = document.getElementById('mediaName');
    if (input.files && input.files[0]) {
        name.textContent      = '📎 ' + input.files[0].name;
        preview.style.display = 'block';
    }
}

// init char counts and button state on page load

window.onload = function() {
    updateCharCount('short_description', 'shortDescCount', 500);
    handleKeyUp();
};
</script>

</body>
</html>
