<?php
// ini_set('session.save_path', '/home2/siamak65/tmp_sessions');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$username = $_SESSION['username'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination variables
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

try {
    $search_query = "";
    if ($search !== '') {
        $search_query = " AND property_address LIKE :search ";
    }

    // Get total count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = :user_id $search_query");
    $count_stmt->bindParam(':user_id', $_SESSION['user_id']);
    if ($search !== '') {
        $like_search = "%$search%";
        $count_stmt->bindParam(':search', $like_search);
    }
    $count_stmt->execute();
    $total_listings = $count_stmt->fetchColumn();
    $total_pages = ceil($total_listings / $per_page);

    // Get listings for current page
    $stmt = $pdo->prepare("SELECT id, property_address, postal_code, city, created_at FROM listings WHERE user_id = :user_id $search_query ORDER BY created_at DESC LIMIT :start, :per_page");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    if ($search !== '') {
        $stmt->bindParam(':search', $like_search);
    }
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Dashboard listings error: " . $e->getMessage());
    die("A server error occurred. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <script>
        // Delete is now a POST form with CSRF token (see table below).
    </script>
</head>
<body class="bg-gray-100 p-6">

    <div class="flex flex-col items-center mb-6 space-y-4">
        <div class="text-center">
            <h1 class="text-2xl font-semibold text-gray-800">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        </div>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="profile.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit Profile</a>
            <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Logout</a>
            <a href="create_listing.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Create Listing</a>
        </div>
    </div>

    <div class="flex justify-center">
        <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-6xl">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 text-center">Your Listings</h2>

            <!-- Search box -->
            <form method="GET" action="" class="mb-4 flex justify-center">
                <input type="text" name="search" placeholder="Search address..." value="<?php echo htmlspecialchars($search); ?>" class="border px-4 py-2 rounded-l w-80" />
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-r">Search</button>
            </form>

            <?php if (empty($listings)): ?>
                <p class="text-gray-600 mb-4 text-center">You have not created any listings yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto hidden md:table">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left">ID</th>
                                <th class="px-4 py-2 text-left">Address</th>
                                <th class="px-4 py-2 text-left">City</th>
                                <th class="px-4 py-2 text-left">Postal Code</th>
                                <th class="px-4 py-2 text-left">Date Created</th>
                                <th class="px-4 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listings as $listing): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2 font-mono"><?php echo $listing['id']; ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($listing['property_address']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($listing['city']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($listing['postal_code']); ?></td>
                                    <td class="px-4 py-2"><?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></td>
                                    <td class="px-4 py-2 text-right space-x-2">
                                        <a href="view_listing.php?id=<?php echo $listing['id']; ?>" target="_blank" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">View</a>
                                        <a href="upload_listing.php?id=<?php echo $listing['id']; ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">Upload Images</a>
                                        <a href="edit_listing.php?id=<?php echo $listing['id']; ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-sm">Edit</a>
                                        <form method="POST" action="delete_listing.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this listing?')">
                                            <input type="hidden" name="id" value="<?php echo $listing['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="flex justify-center items-center space-x-2 mt-6">
                        <?php if ($page > 1): ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=1" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded">First</a>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded">Previous</a>
                        <?php endif; ?>

                        <?php
                        // Windowed pagination: show at most ±2 pages around current
                        $window = 2;
                        $shown  = [];
                        for ($i = 1; $i <= $total_pages; $i++) {
                            if ($i === 1 || $i === $total_pages ||
                                ($i >= $page - $window && $i <= $page + $window)) {
                                $shown[] = $i;
                            }
                        }
                        $prev_shown = null;
                        foreach ($shown as $i):
                            if ($prev_shown !== null && $i - $prev_shown > 1): ?>
                                <span class="px-2 py-1 text-gray-400">…</span>
                            <?php endif; ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
                               class="px-3 py-1 <?php echo ($i === $page) ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded">
                                <?php echo $i; ?>
                            </a>
                        <?php $prev_shown = $i; endforeach; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded">Next</a>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $total_pages; ?>" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded">Last</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex justify-center mt-6">
                <a href="create_listing.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Create New Listing</a>
            </div>
        </div>
    </div>
</body>
</html>
