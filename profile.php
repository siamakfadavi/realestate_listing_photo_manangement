<?php
// Secure session
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

$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

try {
    // Fetch user info
    $stmt = $pdo->prepare("SELECT username, email, telephone FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }

    $username = $user['username'];
    $email = $user['email'];
    $telephone = $user['telephone'];
} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    die("A server error occurred. Please try again later.");
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header("Location: profile.php?error=invalid_request");
        exit();
    }

    $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
    $telephone = trim($_POST["telephone"] ?? '');
    $new_password = trim($_POST["new_password"] ?? '');
    $confirm_password = trim($_POST["confirm_password"] ?? '');

    if (!$email) {
        $error_message .= "Invalid email. ";
    }

    if (!preg_match('/^\+?[\d\s\-\(\)]{10,20}$/', $telephone)) {
        $error_message .= "Invalid telephone number. ";
    }

    if (!empty($new_password) || !empty($confirm_password)) {
        if (strlen($new_password) < 8) {
            $error_message .= "Password must be at least 8 characters. ";
        } elseif ($new_password !== $confirm_password) {
            $error_message .= "Passwords do not match. ";
        }
    }

    if (empty($error_message)) {
        try {
            $query = "UPDATE users SET email = :email, telephone = :telephone";
            if (!empty($new_password)) {
                $query .= ", password = :password";
            }
            $query .= " WHERE id = :user_id";

            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt->bindParam(':password', $hashed_password);
            }
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $success_message = "Profile updated successfully.";
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error_message = "A server error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Edit Profile</h2>
        <a href="dashboard.php" class="inline-block mb-6 text-blue-600 hover:underline">← Back to Dashboard</a>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <strong>Success:</strong> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="profile.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" class="border rounded w-full p-2 bg-gray-100" readonly>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" class="border rounded w-full p-2" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Telephone:</label>
                <input type="text" name="telephone" value="<?= htmlspecialchars($telephone) ?>" class="border rounded w-full p-2" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">New Password:</label>
                <input type="password" name="new_password" class="border rounded w-full p-2">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Confirm Password:</label>
                <input type="password" name="confirm_password" class="border rounded w-full p-2">
            </div>
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Update Profile
            </button>
        </form>
    </div>
</body>
</html>
