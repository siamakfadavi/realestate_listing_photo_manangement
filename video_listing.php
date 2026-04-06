<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}
$listing_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

try {
    $stmt = $pdo->prepare("SELECT property_address, postal_code, city, video_links FROM listings WHERE id = :listing_id");
    $stmt->bindParam(':listing_id', $listing_id);
    $stmt->execute();
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$listing || empty($listing['video_links'])) {
        $error_message = "No video available for this listing.";
        header("Location: view_listing.php?id=$listing_id");
        exit();
    }

    // Extract YouTube video ID
    $video_id = '';
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/))([\w\-]+)/', $listing['video_links'], $matches)) {
        $video_id = $matches[1];
    }

} catch (PDOException $e) {
    error_log("Video listing error: " . $e->getMessage());
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Video</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        iframe {
            width: 100%;
            height: 450px;
            max-height: 600px;
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
                <a href="gallery_listing.php?id=<?php echo $listing_id; ?>" class="tab text-blue-600 hover:text-blue-800 font-semibold py-2 px-4 rounded whitespace-nowrap">Photo Gallery</a>
                <span class="tab font-semibold py-2 px-4 text-gray-500 cursor-default whitespace-nowrap bg-gray-200">Video</span>
            </div>
        </div>

        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Video</h2>

        <?php if ($video_id): ?>
            <div class="aspect-w-16 aspect-h-9">
                <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_id); ?>"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                </iframe>
            </div>
        <?php else: ?>
            <p class="text-gray-500">Invalid YouTube link.</p>
        <?php endif; ?>
    </div>
</body>
</html>
