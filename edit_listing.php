<?php
ini_set('display_errors', 0);
error_reporting(0);

// Secure session handling
// ini_set('session.save_path', '/home2/siamak65/tmp_sessions');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$listing_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$listing_id) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $listing_id, ':uid' => $_SESSION['user_id']]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$listing) {
    header("Location: dashboard.php?error=not_found");
    exit();
}

$property_address = $listing['property_address'];
$postal_code = $listing['postal_code'];
$city = $listing['city'];
$video_links = $listing['video_links'];
$success_message = $error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header("Location: edit_listing.php?id=$listing_id&error=invalid_request");
        exit();
    }
    $property_address = trim($_POST['property_address']);
    $postal_code      = trim($_POST['postal_code']);
    $city             = trim($_POST['city']);
    $video_links      = trim($_POST['video_links'] ?? '');

    if (isset($_POST['delete_video'])) $video_links = null;

    if (!$property_address || !$postal_code || !$city) {
        $error_message = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE listings SET property_address = :pa, postal_code = :pc, city = :c, video_links = :v WHERE id = :id AND user_id = :uid");
        $stmt->execute([
            ':pa' => $property_address, ':pc' => $postal_code, ':c' => $city,
            ':v' => $video_links, ':id' => $listing_id, ':uid' => $_SESSION['user_id']
        ]);
        $success_message = "Listing updated successfully.";
    }

    if (!empty($_POST['image_ids'])) {
        foreach ($_POST['image_ids'] as $i => $image_id) {
            $pos = intval($_POST["position_$image_id"] ?? $i);
            $hide = isset($_POST["hidden_$image_id"]) ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE listing_images SET position = :pos, is_hidden = :hide WHERE id = :iid AND listing_id = :lid");
            $stmt->execute([':pos' => $pos, ':hide' => $hide, ':iid' => $image_id, ':lid' => $listing_id]);
        }
    }

    if (!empty($_POST['delete_image_ids'])) {
        foreach ($_POST['delete_image_ids'] as $id) {
            $stmt = $pdo->prepare("SELECT file_path FROM listing_images WHERE id = :id AND listing_id = :lid");
            $stmt->execute([':id' => $id, ':lid' => $listing_id]);
            $file = $stmt->fetchColumn();
            if ($file && file_exists($file)) unlink($file);
            $pdo->prepare("DELETE FROM listing_images WHERE id = :id AND listing_id = :lid")
                ->execute([':id' => $id, ':lid' => $listing_id]);
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = :id");
    $stmt->execute([':id' => $listing_id]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
    $property_address = $listing['property_address'];
    $postal_code = $listing['postal_code'];
    $city = $listing['city'];
    $video_links = $listing['video_links'];
}

$stmt = $pdo->prepare("SELECT id, file_path, position, is_hidden FROM listing_images WHERE listing_id = :lid ORDER BY position ASC");
$stmt->execute([':lid' => $listing_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Listing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6 font-sans">
<div class="bg-white rounded-lg shadow-md p-8 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">Edit Listing</h2>
    <a href="dashboard.php" class="inline-block mb-6 text-blue-600 hover:underline">← Back to Dashboard</a>
    <?php if ($success_message): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded"><?= $success_message ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-4 rounded"><?= $error_message ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <label class="block mb-2 text-sm font-bold text-gray-700">Property Address</label>
        <input type="text" name="property_address" value="<?= htmlspecialchars($property_address) ?>" class="w-full mb-4 px-3 py-2 border rounded">

        <label class="block mb-2 text-sm font-bold text-gray-700">Postal Code</label>
        <input type="text" name="postal_code" value="<?= htmlspecialchars($postal_code) ?>" class="w-full mb-4 px-3 py-2 border rounded">

        <label class="block mb-2 text-sm font-bold text-gray-700">City</label>
        <input type="text" name="city" value="<?= htmlspecialchars($city) ?>" class="w-full mb-4 px-3 py-2 border rounded">

        <label class="block mb-2 text-sm font-bold text-gray-700">YouTube Video URL (optional)</label>
        <input type="url" name="video_links" value="<?= htmlspecialchars($video_links ?? '') ?>" class="w-full px-3 py-2 border rounded mb-2">
        <?php if (!empty($video_links)): ?>
            <button name="delete_video" value="1" class="mb-4 bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">Delete Video Link</button>
        <?php endif; ?>

        <label class="block mb-2 text-sm font-bold text-gray-700">Images</label>
        <ul id="image-list" class="flex flex-wrap gap-4 mb-6">
            <?php foreach ($images as $img): ?>
                <li data-id="<?= $img['id'] ?>" class="relative w-[150px] border rounded shadow-sm overflow-hidden">
                    <img src="<?= htmlspecialchars($img['file_path']) ?>" class="w-full object-cover">
                    <div class="absolute top-0 left-0 p-1 flex gap-1">
                        <input type="checkbox" id="hidden_<?= $img['id'] ?>" name="hidden_<?= $img['id'] ?>" value="1" <?= $img['is_hidden'] ? 'checked' : '' ?>>
                        <label for="hidden_<?= $img['id'] ?>" class="text-white text-xs bg-black/60 px-1 rounded cursor-pointer">Toggle</label>
                        <button type="button" onclick="deleteImage(<?= $img['id'] ?>)" class="bg-red-500/70 text-white text-xs rounded px-1 ml-auto">Delete</button>
                    </div>
                    <input type="hidden" name="image_ids[]" value="<?= $img['id'] ?>">
                    <input type="hidden" name="position_<?= $img['id'] ?>" value="<?= $img['position'] ?>">
                </li>
            <?php endforeach; ?>
        </ul>

        <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-semibold px-6 py-2 rounded">Update Listing</button>
    </form>
</div>

<script>
new Sortable(document.getElementById('image-list'), {
    animation: 150,
    onEnd: function () {
        const items = document.querySelectorAll('[data-id]');
        items.forEach((el, idx) => {
            const id = el.dataset.id;
            const posInput = el.querySelector('input[name="position_' + id + '"]');
            if (posInput) posInput.value = idx;
        });
    }
});

function deleteImage(id) {
    if (confirm("Are you sure you want to delete this image?")) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "delete_image_ids[]";
        input.value = id;
        document.querySelector("form").appendChild(input);
        document.querySelector('[data-id="' + id + '"]').remove();
    }
}
</script>
</body>
</html>
