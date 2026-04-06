<?php
// Secure session setup
// ini_set('session.save_path', '/home2/siamak65/tmp_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Only accept POST requests to prevent CSRF via GET (e.g. <img> hotlinks)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

// Validate CSRF token
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    header("Location: dashboard.php?error=invalid_request");
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: dashboard.php?error=invalid_id");
    exit();
}

$listing_id = (int) $_POST['id'];
$user_id = $_SESSION['user_id'];

try {
    // Verify listing belongs to this user
    $stmt = $pdo->prepare("SELECT id FROM listings WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $listing_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }

    // Collect image file paths before deleting DB rows
    $img_stmt = $pdo->prepare("SELECT file_path FROM listing_images WHERE listing_id = :id");
    $img_stmt->execute([':id' => $listing_id]);
    $image_paths = $img_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Delete everything in a single transaction
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM listing_images WHERE listing_id = :id")
        ->execute([':id' => $listing_id]);

    $pdo->prepare("DELETE FROM listings WHERE id = :id AND user_id = :user_id")
        ->execute([':id' => $listing_id, ':user_id' => $user_id]);

    $pdo->commit();

    // Remove physical image files and the upload directory
    foreach ($image_paths as $path) {
        $full_path = __DIR__ . '/' . ltrim($path, '/');
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    $upload_dir = __DIR__ . "/uploads/$listing_id";
    if (is_dir($upload_dir)) {
        // rmdir only works on empty directories; glob handles any remaining files
        foreach (glob($upload_dir . '/*') as $f) {
            if (is_file($f)) unlink($f);
        }
        rmdir($upload_dir);
    }

    header("Location: dashboard.php?success=deleted");
    exit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Delete listing error: " . $e->getMessage());
    header("Location: dashboard.php?error=server");
    exit();
}
