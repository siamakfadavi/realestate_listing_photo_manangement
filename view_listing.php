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
    <title><?php echo htmlspecialchars($listing['property_address']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        #slideshow {
            position: relative;
            height: 500px;
            overflow: hidden;
            z-index: 0;
        }
        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: contain;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            z-index: 0;
        }
        .slide.active {
            opacity: 1;
            z-index: 1;
        }
        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            width: auto;
            padding: 12px;
            margin-top: -22px;
            color: white;
            font-weight: bold;
            font-size: 24px;
            user-select: none;
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            z-index: 10;
        }
        .prev { left: 0; }
        .next { right: 0; }

        .thumb-bar-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            position: relative;
        }
        .thumb-scroll {
            display: flex;
            gap: 10px;
            overflow: hidden;
            max-width: 560px;
        }
        .thumb {
            height: 50px;
            width: 50px;
            border-radius: 5px;
            cursor: pointer;
            object-fit: cover;
            opacity: 0.6;
            transition: opacity 0.3s ease;
        }
        .thumb:hover, .thumb.active-thumb {
            opacity: 1;
            border: 2px solid blue;
        }
        .thumb-nav {
            cursor: pointer;
            font-size: 20px;
            background-color: rgba(0,0,0,0.1);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
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
            <a href="view_listing.php?id=<?php echo $listing_id; ?>" class="tab text-blue-600 font-semibold py-2 px-4 rounded bg-gray-200 cursor-default">Slideshow</a>
            <a href="gallery_listing.php?id=<?php echo $listing_id; ?>" class="tab text-blue-600 hover:text-blue-800 font-semibold py-2 px-4 rounded">Photo Gallery</a>
            <?php if (!empty($listing['video_links'])): ?>
                <a href="video_listing.php?id=<?php echo $listing_id; ?>" class="tab text-blue-600 hover:text-blue-800 font-semibold py-2 px-4 rounded">Video</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-6">
        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Slideshow</h2>
        <?php
        $visibleImages = array_values(array_filter($images, fn($img) => !$img['is_hidden']));
        if (count($visibleImages) > 0): ?>
            <div id="slideshow">
                <?php foreach ($visibleImages as $index => $image): ?>
                    <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="" class="h-full mx-auto">
                        <?php if ($image['caption']): ?>
                            <p class="caption text-white text-center bg-black/50 py-2 w-full absolute bottom-0 left-0"><?php echo htmlspecialchars($image['caption']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <a class="prev" onclick="plusSlides(-1)">❮</a>
                <a class="next" onclick="plusSlides(1)">❯</a>
            </div>
            <div class="thumb-bar-container">
                <div class="thumb-nav" onclick="scrollThumbnails(-1)">❮</div>
                <div class="thumb-scroll" id="thumbScroll">
                    <?php foreach ($visibleImages as $index => $image): ?>
                        <img src="<?php echo htmlspecialchars($image['file_path']); ?>" class="thumb <?php echo $index === 0 ? 'active-thumb' : ''; ?>" onclick="currentSlide(<?php echo $index + 1; ?>)">
                    <?php endforeach; ?>
                </div>
                <div class="thumb-nav" onclick="scrollThumbnails(1)">❯</div>
            </div>
        <?php else: ?>
            <p class="text-gray-500">No images available for this listing.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    let slideIndex = 1;
    let timeoutId = null;
    const slides = document.getElementsByClassName("slide");
    const thumbs = document.getElementsByClassName("thumb");

    function plusSlides(n) {
        clearTimeout(timeoutId);
        showSlides(slideIndex += n);
    }

    function currentSlide(n) {
        clearTimeout(timeoutId);
        slideIndex = n;
        showSlides(slideIndex);
    }

    function showSlides(n) {
        if (n > slides.length) slideIndex = 1;
        if (n < 1) slideIndex = slides.length;

        for (let i = 0; i < slides.length; i++) {
            slides[i].classList.remove("active");
        }
        for (let i = 0; i < thumbs.length; i++) {
            thumbs[i].classList.remove("active-thumb");
        }

        slides[slideIndex - 1].classList.add("active");
        thumbs[slideIndex - 1].classList.add("active-thumb");

        timeoutId = setTimeout(() => plusSlides(1), 3000);
    }

    function scrollThumbnails(direction) {
        const thumbScroll = document.getElementById("thumbScroll");
        const scrollAmount = 60 * 3; // Scroll approx 3 thumbnails at a time
        thumbScroll.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
    }

    document.addEventListener("DOMContentLoaded", () => {
        if (slides.length > 0) showSlides(slideIndex);
    });
</script>
</body>
</html>
