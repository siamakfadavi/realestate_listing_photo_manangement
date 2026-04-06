<?php
/**
 * Main entry point.
 * Checks for the installation wizard, and routes the user accordingly.
 */
if (file_exists(__DIR__ . '/install.php')) {
    // If the installer hasn't been run yet, direct them there.
    header("Location: install.php");
    exit();
} else {
    // Already installed (installer self-deleted), take them to login.
    header("Location: login.php");
    exit();
}
