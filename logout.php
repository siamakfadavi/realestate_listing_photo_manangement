<?php
// Clear session and destroy
// ini_set('session.save_path', '/home2/siamak65/tmp_sessions');
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Optional: also clear auth cookies (if you use them)
setcookie("auth_user", "", time() - 3600, "/");
setcookie("auth_token", "", time() - 3600, "/");
setcookie("auth_id", "", time() - 3600, "/");

// Redirect to login page
header("Location: login.php");
exit();
