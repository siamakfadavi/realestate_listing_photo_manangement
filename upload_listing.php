<?php
// ini_set('session.save_path', '/home2/siamak65/tmp_sessions');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$error_message = "";
$success_message = "";

// Get and validate listing ownership
$listing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT id FROM listings WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $listing_id, ':uid' => $_SESSION['user_id']]);
$listing = $stmt->fetch();
if (!$listing) {
    header("Location: dashboard.php?error=not_found");
    exit();
}

// File upload handler
// This PHP part remains the same. It will be called by the JavaScript AJAX request.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['images'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Invalid request. CSRF token mismatch."]);
        exit();
    }

    $upload_dir = __DIR__ . "/uploads/$listing_id/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $allowed_exts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $uploaded_files_count = 0;
    $error_files_count = 0;

    foreach ($_FILES['images']['tmp_name'] as $i => $tmp_name) {
        $original_name = $_FILES['images']['name'][$i];
        $error = $_FILES['images']['error'][$i];
        $tmp_path = $_FILES['images']['tmp_name'][$i];

        if ($error === UPLOAD_ERR_OK) {
            // Enforce 5 MB per-file limit
            $max_bytes = 5 * 1024 * 1024;
            if ($_FILES['images']['size'][$i] > $max_bytes) {
                $error_message .= "$original_name exceeds the 5 MB limit. ";
                $error_files_count++;
                continue;
            }

            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_exts)) {
                $error_message .= "Invalid file type for $original_name. ";
                $error_files_count++;
                continue;
            }

            // Validate real MIME type (not just the extension)
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimetype = $finfo->file($tmp_path);
            if (!in_array($mimetype, $allowed_mimes)) {
                $error_message .= "Rejected $original_name: not a real image file. ";
                $error_files_count++;
                continue;
            }

            $new_filename = uniqid('img_', true) . '.' . $ext;
            $dest_path = $upload_dir . $new_filename;
            $relative_path = "uploads/$listing_id/" . $new_filename;

            if (move_uploaded_file($tmp_path, $dest_path)) {
                $stmt = $pdo->prepare("INSERT INTO listing_images (listing_id, file_path, position, is_hidden) VALUES (:lid, :path, 0, 0)");
                $stmt->execute([
                    ':lid' => $listing_id,
                    ':path' => $relative_path
                ]);
                $uploaded_files_count++;
            } else {
                $error_message .= "Failed to save $original_name. ";
                $error_files_count++;
            }
        } else {
            $error_message .= "Upload error with $original_name: code $error. ";
            $error_files_count++;
        }
    }

    // Return a JSON response for AJAX
    header('Content-Type: application/json');
    if ($uploaded_files_count > 0 && $error_files_count === 0) {
        echo json_encode(['success' => true, 'message' => "$uploaded_files_count image(s) uploaded successfully."]);
    } elseif ($uploaded_files_count > 0 && $error_files_count > 0) {
        echo json_encode(['success' => true, 'message' => "$uploaded_files_count image(s) uploaded, $error_files_count errors. " . $error_message]);
    } else {
        echo json_encode(['success' => false, 'message' => "Upload failed. " . $error_message]);
    }
    exit(); // Important to exit after sending JSON response
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Listing Images</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .progress-bar-container {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            margin-top: 10px;
            height: 25px; /* Added height */
            overflow: hidden; /* Ensures inner bar stays contained */
        }
        .progress-bar {
            width: 0%;
            height: 100%; /* Changed to 100% to fill container */
            background-color: #4caf50;
            text-align: center;
            line-height: 25px; /* Vertically center text */
            color: white;
            border-radius: 5px; /* Match container's border-radius */
            transition: width 0.4s ease;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Upload Images for Listing #<?= htmlspecialchars($listing_id) ?></h2>
    <a href="dashboard.php" class="inline-block mb-6 text-blue-600 hover:underline">← Back to Dashboard</a>

    <div id="message-area">
        <?php if (!empty($success_message) && $_SERVER["REQUEST_METHOD"] !== "POST"): // Only show initial page load messages ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message) && $_SERVER["REQUEST_METHOD"] !== "POST"): // Only show initial page load messages ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
    </div>

    <form id="uploadForm" method="post" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div>
            <label class="block text-gray-700 font-bold mb-2">Select Images</label>
            <input type="file" name="images[]" id="imagesInput" accept="image/*" multiple required class="w-full border px-4 py-2 rounded" />
        </div>

        <div class="progress-bar-container" style="display: none;">
            <div class="progress-bar" id="progressBar">0%</div>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Upload Images
        </button>
    </form>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission

    const form = event.target;
    const formData = new FormData(form);
    const xhr = new XMLHttpRequest();
    const progressBarContainer = document.querySelector('.progress-bar-container');
    const progressBar = document.getElementById('progressBar');
    const messageArea = document.getElementById('message-area');
    const filesInput = document.getElementById('imagesInput');

    // Clear previous messages
    messageArea.innerHTML = '';

    if (filesInput.files.length === 0) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
        errorDiv.textContent = 'Please select at least one image.';
        messageArea.appendChild(errorDiv);
        return;
    }

    progressBarContainer.style.display = 'block'; // Show progress bar
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';

    xhr.open('POST', form.action, true);

    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percentComplete + '%';
            progressBar.textContent = percentComplete + '%';
        }
    });

    xhr.onload = function() {
        progressBarContainer.style.display = 'none'; // Hide progress bar on completion
        filesInput.value = ''; // Clear the file input

        let response;
        try {
            response = JSON.parse(xhr.responseText);
            const messageDiv = document.createElement('div');
            messageDiv.className = `px-4 py-3 rounded mb-4 border ${response.success ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'}`;
            messageDiv.textContent = response.message;
            messageArea.appendChild(messageDiv);

            if (response.success) {
                // Optionally, redirect or perform other actions on success
                // For example, after a delay:
                // setTimeout(() => { window.location.href = 'dashboard.php'; }, 2000);
            }
        } catch (err) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
            errorDiv.textContent = 'An unexpected error occurred. Please try again. Raw response: ' + xhr.responseText;
            messageArea.appendChild(errorDiv);
            console.error("Error parsing JSON response: ", err);
            console.error("Raw server response: ", xhr.responseText);
        }
    };

    xhr.onerror = function() {
        progressBarContainer.style.display = 'none'; // Hide progress bar on error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
        errorDiv.textContent = 'An error occurred during the upload. Please try again.';
        messageArea.appendChild(errorDiv);
    };

    xhr.send(formData);
});
</script>

</body>
</html>
