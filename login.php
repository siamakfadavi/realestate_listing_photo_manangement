<?php
ini_set('display_errors', 0);
error_reporting(0);

// Set custom session save path
// ini_set('session.save_path', '/home2/siamak65/tmp_sessions');

// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'db_connect.php';

$error_message = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, "password", FILTER_UNSAFE_RAW);

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            /*
             * Brute-force protection requires two extra columns on the users table.
             * Run this SQL once on your database before deploying:
             *
             *   ALTER TABLE users
             *       ADD COLUMN login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
             *       ADD COLUMN locked_until   DATETIME NULL DEFAULT NULL;
             */
            $stmt = $pdo->prepare(
                "SELECT id, username, password, role, login_attempts, locked_until
                 FROM users WHERE username = :username"
            );
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if account is temporarily locked
            if ($user && $user['locked_until'] && new DateTime() < new DateTime($user['locked_until'])) {
                $error_message = "Too many failed attempts. Account locked for 15 minutes.";

            } elseif ($user && password_verify($password, $user['password'])) {
                // Reset failed attempt counter on successful login
                $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = :id")
                    ->execute([':id' => $user['id']]);

                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'] ?? 'user';

                header("Location: dashboard.php");
                exit();

            } else {
                // Increment failed attempts; lock after 5
                if ($user) {
                    $attempts     = $user['login_attempts'] + 1;
                    $locked_until = $attempts >= 5
                        ? date('Y-m-d H:i:s', strtotime('+15 minutes'))
                        : null;
                    $pdo->prepare(
                        "UPDATE users SET login_attempts = :a, locked_until = :l WHERE id = :id"
                    )->execute([':a' => $attempts, ':l' => $locked_until, ':id' => $user['id']]);
                }
                $error_message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = "A server error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded shadow-md max-w-md w-full">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Login</h2>
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        <form method="post" action="login.php" class="space-y-4">
            <div>
                <label for="username" class="block text-gray-700 font-bold mb-1">Username</label>
                <input type="text" id="username" name="username" class="w-full px-3 py-2 border rounded" required>
            </div>
            <div>
                <label for="password" class="block text-gray-700 font-bold mb-1">Password</label>
                <input type="password" id="password" name="password" class="w-full px-3 py-2 border rounded" required>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full font-bold hover:bg-blue-700">
                Login
            </button>
        </form>
    </div>
</body>
</html>
