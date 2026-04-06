<?php
// Set custom session path and start session
// ini_set('session.save_path', '/home2/siamak65/tmp_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header("Location: dashboard.php?error=invalid_request");
        exit();
    }

    $address = trim($_POST['property_address'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $city = trim($_POST['city'] ?? '');

    if (empty($address) || empty($postal_code) || empty($city)) {
        $error_message = "All fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO listings (user_id, property_address, postal_code, city, created_at) VALUES (:user_id, :address, :postal_code, :city, NOW())");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':postal_code', $postal_code);
            $stmt->bindParam(':city', $city);
            $stmt->execute();

            $success_message = "Listing created successfully.";
        } catch (PDOException $e) {
            $error_message = "Error creating listing: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Create Listing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Create New Listing</h2>
        <a href="dashboard.php" class="inline-block mb-6 text-blue-600 hover:underline">← Back to Dashboard</a>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Property Address</label>
                <input type="text" name="property_address" class="w-full border px-4 py-2 rounded" required />
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Postal Code</label>
                <input type="text" name="postal_code" class="w-full border px-4 py-2 rounded" required />
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">City</label>
                <input type="text" name="city" class="w-full border px-4 py-2 rounded" required />
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create Listing
            </button>
        </form>
    </div>
</body>
</html>
