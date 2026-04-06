<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error_message = "Invalid listing ID.";
    header("Location: listings.php?error=" . urlencode($error_message));
    exit();
}
$listing_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

try {
    $stmt = $pdo->prepare("SELECT property_address, postal_code, city, video_links FROM listings WHERE id = :listing_id");
    $stmt->bindParam(':listing_id', $listing_id);
    $stmt->execute();
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$listing) {
        $error_message = "Listing not found.";
        header("Location: dashboard.php?error=" . urlencode($error_message));
        exit();
    }

    $stmt = $pdo->prepare("SELECT file_path, caption, is_hidden FROM listing_images WHERE listing_id = :listing_id ORDER BY position ASC");
    $stmt->bindParam(':listing_id', $listing_id);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error fetching listing details: " . $e->getMessage();
    header("Location: listings.php?error=" . urlencode($error_message));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Photo Gallery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/css/lightgallery-bundle.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-4xl mx-auto">
        <div class="flex justify-between items-start mb-4 flex-wrap">
            <div>
                <h1 class="text-3xl font-semibold text-gray-800"><?php echo htmlspecialchars($listing['property_address']); ?></h1>
                <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($listing['city']); ?>, <?php echo htmlspecialchars($listing['postal_code']); ?></p>
            </div>
            <div class="flex space-x-4 mt-4">
                <a href="view_listing.php?id=<?php echo $listing_id; ?>" class="tab text-blue-600 hover:text-blue-800 font-semibold py-2 px-4 rounded whitespace-nowrap">Slideshow</a>
                <span class="tab font-semibold py-2 px-4 text-gray-500 cursor-default whitespace-nowrap bg-gray-200">Photo Gallery</span>
                <?php if (!empty($listing['video_links'])): ?>
                    <a href="video_listing.php?id=<?php echo $listing_id; ?>" class="tab text-blue-600 hover:text-blue-800 font-semibold py-2 px-4 rounded whitespace-nowrap">Video</a>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Photo Gallery</h2>

        <?php if (count($images) > 0): ?>
            <div id="gallery" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($images as $image): ?>
                    <?php if (!$image['is_hidden']): ?>
                        <a href="<?php echo htmlspecialchars($image['file_path']); ?>"
                           data-lg-size="800-600"
                           data-sub-html="<?php echo htmlspecialchars($image['caption']); ?>">
                            <img src="<?php echo htmlspecialchars($image['file_path']); ?>"
                                 alt="<?php echo htmlspecialchars($image['caption']); ?>"
                                 class="rounded-md cursor-pointer hover:shadow-lg transition-shadow duration-200"
                                 style="max-height: 200px; width: 100%; object-fit: cover;">
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500">No images available for this listing.</p>
        <?php endif; ?>
    </div>

    <!-- LightGallery Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/lightgallery.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/zoom/lg-zoom.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/thumbnail/lg-thumbnail.umd.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            lightGallery(document.getElementById('gallery'), {
                selector: 'a',
                plugins: [lgZoom, lgThumbnail],
                licenseKey: '0000-0000-0000-0000',
                speed: 500,
                download: false,
                actualSize: false,
                width: '800px',
                height: '600px'
            });
        });
    </script>
</body>
</html>
