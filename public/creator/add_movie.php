<?php
// creator/add_movie.php
// Creator Panel: form to add a new movie (draft, not published yet)

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Movie.php';

// access control, creators and admins only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['creator', 'admin'])) {
    header('Location: /index.php');
    exit;
}

$categories = [
    1 => 'Action',
    2 => 'Comedy',
    3 => 'Horror',
    4 => 'Sci-Fi',
    5 => 'Drama'
];

$errors   = [];
$success  = false;

// keep form values on error
$formData = [
    'title'             => '',
    'short_description' => '',
    'synopsis'          => '',
    'release_year'      => '',
    'duration_min'      => '',
    'selected_cats'     => []
];

// -------------------------------------------------------
// POST: process form submission
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // collect text fields
    $formData['title']             = trim($_POST['title']           ?? '');
    $formData['short_description'] = trim($_POST['short_description'] ?? '');
    $formData['synopsis']          = trim($_POST['synopsis']         ?? '');
    $formData['release_year']      = trim($_POST['release_year']     ?? '');
    $formData['duration_min']      = trim($_POST['duration_min']     ?? '');
    $formData['selected_cats']     = $_POST['categories']            ?? [];

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

    // image upload, required
    $image_url = '';
    if (empty($_FILES['image']['name'])) {
        $errors[] = 'A poster image is required.';
    } else {
        $imgResult = uploadFile($_FILES['image'], 'images', ['jpg','jpeg','png','webp'], 5);
        if ($imgResult['error']) {
            $errors[] = $imgResult['error'];
        } else {
            $image_url = $imgResult['path'];
        }
    }

    // media upload, optional
    $media_url = '';
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
        $movie = new Movie();
        $movie->setTitle($formData['title']);
        $movie->setShortDesc($formData['short_description']);
        $movie->setSynopsis($formData['synopsis']);
        $movie->setReleaseYear((int)$formData['release_year']);
        $movie->setDurationMin((int)$formData['duration_min']);
        $movie->setImageUrl($image_url);
        $movie->setMediaUrl($media_url);
        $movie->setCreatedBy($_SESSION['user_id']);

        $newId = $movie->addMovie();

        if ($newId) {
            // Save category links
            if (!empty($formData['selected_cats'])) {
                Movie::setCategories($newId, $formData['selected_cats']);
            }
            header('Location: movies.php?added=1');
            exit;
        } else {
            $errors[] = 'Something went wrong saving the movie. Please try again.';
        }
    }
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

    // Store under the public web root so the file is reachable at /assets/uploads/...
    $webDir = 'assets/uploads/' . $folder;   // saved in DB; served from '/' + this
    $fsDir  = __DIR__ . '/../' . $webDir;     // public/assets/uploads/<folder>
    if (!is_dir($fsDir)) mkdir($fsDir, 0755, true);

    // unique filename to avoid collisions
    $newName = uniqid('', true) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $fsDir . '/' . $newName))
        return ['path' => '', 'error' => 'File upload failed. Check folder permissions.'];

    return ['path' => $webDir . '/' . $newName, 'error' => ''];
}

$page_title = 'Add Movie — Creator Panel';
$active_nav = 'creator';
$container_class = 'app-container--narrow';
require __DIR__ . '/../../includes/header.php';
?>

  <div class="page-heading">
    <h1>Add New Movie</h1>
    <p>Your movie will be saved as a draft. You can publish it from My Movies.</p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert-errors">
      <strong>Please fix the following:</strong>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="addMovieForm" novalidate>

    <!-- Basic Info -->
    <div class="glass-panel form-card">
      <h3>Basic Information</h3>

      <div class="form-group">
        <label for="title">Title <span class="req">*</span></label>
        <input type="text" id="title" name="title" class="form-control" maxlength="255"
               value="<?= htmlspecialchars($formData['title']) ?>"
               onblur="validateRequired(this, 'titleErr', 'Title is required')"
               onkeyup="handleKeyUp()">
        <span class="err-label" id="titleErr"></span>
      </div>

      <div class="form-group">
        <label for="short_description">
          Short Description <span class="req">*</span>
          <small style="font-weight:normal;color:var(--ink-faint)">(shown on home page, max 500 chars)</small>
        </label>
        <textarea id="short_description" name="short_description" class="form-control" rows="3" maxlength="500"
                  onblur="validateRequired(this, 'shortDescErr', 'Short description is required')"
                  onkeyup="updateCharCount('short_description', 'shortDescCount', 500); handleKeyUp()"
                  ><?= htmlspecialchars($formData['short_description']) ?></textarea>
        <div class="char-count" id="shortDescCount">0 / 500</div>
        <span class="err-label" id="shortDescErr"></span>
      </div>

      <div class="form-group">
        <label for="synopsis">
          Synopsis
          <small style="font-weight:normal;color:var(--ink-faint)">(full description on movie page)</small>
        </label>
        <textarea id="synopsis" name="synopsis" class="form-control" rows="5"
                  onkeyup="handleKeyUp()"
                  ><?= htmlspecialchars($formData['synopsis']) ?></textarea>
      </div>

      <div class="inline-group">
        <div class="form-group">
          <label for="release_year">Release Year</label>
          <input type="number" id="release_year" name="release_year" class="form-control"
                 min="1888" max="2100" placeholder="e.g. 2023"
                 value="<?= htmlspecialchars($formData['release_year']) ?>"
                 onblur="validateYear()" onkeyup="handleKeyUp()">
          <span class="err-label" id="yearErr"></span>
        </div>
        <div class="form-group">
          <label for="duration_min">Duration (minutes)</label>
          <input type="number" id="duration_min" name="duration_min" class="form-control"
                 min="1" max="999" placeholder="e.g. 120"
                 value="<?= htmlspecialchars($formData['duration_min']) ?>"
                 onblur="validateDuration()" onkeyup="handleKeyUp()">
          <span class="err-label" id="durationErr"></span>
        </div>
      </div>
    </div>

    <!-- Categories -->
    <div class="glass-panel form-card">
      <h3>Categories</h3>
      <div class="cat-grid">
        <?php foreach ($categories as $id => $name): ?>
          <label class="cat-item">
            <input type="checkbox" name="categories[]" value="<?= $id ?>"
              <?= in_array($id, $formData['selected_cats']) ? 'checked' : '' ?>>
            <?= htmlspecialchars($name) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Media -->
    <div class="glass-panel form-card">
      <h3>Media</h3>

      <div class="form-group">
        <label for="image">Poster Image <span class="req">*</span>
          <small style="font-weight:normal;color:var(--ink-faint)">(JPG, PNG, WEBP — max 5MB)</small>
        </label>
        <input type="file" id="image" name="image" class="form-control"
               accept=".jpg,.jpeg,.png,.webp"
               onchange="previewImage(this)" onblur="validateImage()">
        <span class="err-label" id="imageErr"></span>
        <div class="file-preview" id="imagePreview">
          <img id="imageThumb" src="" alt="preview">
          <div class="fname" id="imageName"></div>
        </div>
      </div>

      <div class="form-group">
        <label for="media">Trailer / Video / Audio
          <small style="font-weight:normal;color:var(--ink-faint)">(MP4, MP3, MOV — max 50MB, optional)</small>
        </label>
        <input type="file" id="media" name="media" class="form-control"
               accept=".mp4,.mp3,.mov,.avi"
               onchange="showMediaName(this)">
        <span class="err-label" id="mediaErr"></span>
        <div class="file-preview" id="mediaPreview">
          <div class="fname" id="mediaName"></div>
        </div>
      </div>
    </div>

    <!-- Buttons -->
    <div style="display:flex;gap:12px;margin-top:8px">
      <button type="submit" class="glass-btn glass-btn--accent" id="btnSubmit" disabled>Save as Draft</button>
      <a href="/creator/movies.php" class="glass-btn">Cancel</a>
    </div>

  </form>

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

function validateImage() {
    const field = document.getElementById('image');
    const err   = document.getElementById('imageErr');
    if (!field.files || field.files.length === 0) {
        field.classList.add('error-field');
        err.textContent = 'A poster image is required.';
        return false;
    }
    field.classList.remove('error-field');
    err.textContent = '';
    return true;
}

// enable submit only when required fields are filled

function handleKeyUp() {
    const title    = document.getElementById('title').value.trim();
    const shortDesc = document.getElementById('short_description').value.trim();
    const image    = document.getElementById('image');
    const hasImage = image.files && image.files.length > 0;

    document.getElementById('btnSubmit').disabled =
        !(title && shortDesc && hasImage);
}

// also enable submit when image is chosen

document.getElementById('image').addEventListener('change', handleKeyUp);

// character counter

function updateCharCount(fieldId, countId, max) {
    const len  = document.getElementById(fieldId).value.length;
    const el   = document.getElementById(countId);
    el.textContent = len + ' / ' + max;
    el.classList.toggle('over', len > max);
}

// image preview

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const thumb   = document.getElementById('imageThumb');
    const name    = document.getElementById('imageName');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            thumb.src        = e.target.result;
            name.textContent = input.files[0].name;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
        validateImage();
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

// AJAX: check if title already exists as user types (onblur)

function checkTitleAjax() {
    const titleField = document.getElementById('title');
    const err        = document.getElementById('titleErr');
    const title      = titleField.value.trim();

    if (title === '') return;

    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var response = JSON.parse(this.responseText);
            if (response.exists) {
                titleField.classList.add('error-field');
                err.textContent = 'A movie with this title already exists.';
                document.getElementById('btnSubmit').disabled = true;
            } else {
                titleField.classList.remove('error-field');
                if (err.textContent.includes('already exists')) {
                    err.textContent = '';
                }
                handleKeyUp();
            }
        }
    };
    xmlhttp.open("GET", "ajax_check_title.php?title=" + encodeURIComponent(title), true);
    xmlhttp.send();
}

// init char counts on page load

window.onload = function() {
    updateCharCount('short_description', 'shortDescCount', 500);
    handleKeyUp();
    // attach AJAX check to title field onblur
    document.getElementById('title').addEventListener('blur', checkTitleAjax);
};
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
