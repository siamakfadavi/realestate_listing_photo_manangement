<?php
/**
 * install.php — One-time setup wizard.
 * Writes db_config_repsmall.php, creates tables, creates admin user, then self-deletes.
 */

// ── Guard: redirect if already installed ─────────────────────────────────────
$config_path = dirname(__DIR__) . '/db_config_repsmall.php';
if (file_exists($config_path)) {
    header("Location: login.php");
    exit();
}

$errors  = [];
$success = false;

// ── POST: process installation ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect inputs
    $db_host       = trim($_POST['db_host']       ?? 'localhost');
    $db_name       = trim($_POST['db_name']       ?? '');
    $db_user       = trim($_POST['db_user']       ?? '');
    $db_pass       =       $_POST['db_pass']      ?? '';
    $admin_user    = trim($_POST['admin_user']    ?? '');
    $admin_email   = trim($_POST['admin_email']   ?? '');
    $admin_pass    =       $_POST['admin_pass']   ?? '';
    $admin_confirm =       $_POST['admin_confirm'] ?? '';

    // ── Validate ──────────────────────────────────────────────────────────────
    if (empty($db_host))  $errors[] = "Database host is required.";
    if (empty($db_name))  $errors[] = "Database name is required.";
    if (empty($db_user))  $errors[] = "Database username is required.";

    if (empty($admin_user))        $errors[] = "Admin username is required.";
    elseif (strlen($admin_user) < 3) $errors[] = "Admin username must be at least 3 characters.";

    if (!empty($admin_email) && !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid admin email address.";
    }

    if (strlen($admin_pass) < 8)      $errors[] = "Admin password must be at least 8 characters.";
    if ($admin_pass !== $admin_confirm) $errors[] = "Admin passwords do not match.";

    // ── Test DB connection ────────────────────────────────────────────────────
    if (empty($errors)) {
        try {
            $pdo = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                $db_user,
                $db_pass
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $errors[] = "Database connection failed: " . $e->getMessage();
        }
    }

    // ── Create tables ─────────────────────────────────────────────────────────
    if (empty($errors)) {
        $tables = [
            "CREATE TABLE IF NOT EXISTS `users` (
                `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                `username`       VARCHAR(80)     NOT NULL,
                `email`          VARCHAR(180)    NOT NULL DEFAULT '',
                `telephone`      VARCHAR(30)     NOT NULL DEFAULT '',
                `password`       VARCHAR(255)    NOT NULL,
                `role`           VARCHAR(20)     NOT NULL DEFAULT 'user',
                `login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `locked_until`   DATETIME        NULL     DEFAULT NULL,
                `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `listings` (
                `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                `user_id`          INT UNSIGNED  NOT NULL,
                `property_address` VARCHAR(255)  NOT NULL,
                `postal_code`      VARCHAR(20)   NOT NULL,
                `city`             VARCHAR(100)  NOT NULL,
                `video_links`      TEXT          NULL DEFAULT NULL,
                `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `listing_images` (
                `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                `listing_id` INT UNSIGNED  NOT NULL,
                `file_path`  VARCHAR(500)  NOT NULL,
                `caption`    VARCHAR(255)  NULL DEFAULT NULL,
                `position`   INT           NOT NULL DEFAULT 0,
                `is_hidden`  TINYINT(1)   NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `idx_listing_id` (`listing_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        try {
            foreach ($tables as $sql) {
                $pdo->exec($sql);
            }
        } catch (PDOException $e) {
            $errors[] = "Failed to create tables: " . $e->getMessage();
        }
    }

    // ── Create admin user ─────────────────────────────────────────────────────
    if (empty($errors)) {
        try {
            $hashed = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt   = $pdo->prepare(
                "INSERT INTO users (username, email, telephone, password, role)
                 VALUES (:u, :e, '', :p, 'admin')"
            );
            $stmt->execute([':u' => $admin_user, ':e' => $admin_email, ':p' => $hashed]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $errors[] = "Username '$admin_user' already exists in the database.";
            } else {
                $errors[] = "Failed to create admin user: " . $e->getMessage();
            }
        }
    }

    // ── Write db_config_repsmall.php outside the web root ──────────────────────────
    if (empty($errors)) {
        $config_content = "<?php\n" .
            "define('DB_HOST', " . var_export($db_host, true) . ");\n" .
            "define('DB_NAME', " . var_export($db_name, true) . ");\n" .
            "define('DB_USER', " . var_export($db_user, true) . ");\n" .
            "define('DB_PASS', " . var_export($db_pass, true) . ");\n";

        if (file_put_contents($config_path, $config_content) === false) {
            $errors[] = "Could not write configuration file to <code>$config_path</code>. " .
                        "Please check that PHP has write permission to that directory.";
        }
    }

    // ── All done: self-delete and show success ────────────────────────────────
    if (empty($errors)) {
        $success = true;
        @unlink(__FILE__); // Remove install.php — it is no longer needed
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installation Wizard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }

        .gradient-bg {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 50%, #1e40af 100%);
        }
        .card {
            backdrop-filter: blur(10px);
        }
        .step-divider {
            border-color: #e5e7eb;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.5s ease forwards; }

        @keyframes draw-check {
            to { stroke-dashoffset: 0; }
        }
        .check-path {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: draw-check 0.6s 0.3s ease forwards;
        }
        @keyframes countdown-ring {
            from { stroke-dashoffset: 0; }
            to   { stroke-dashoffset: 126; }
        }
        .countdown-ring {
            stroke-dasharray: 126;
            stroke-dashoffset: 0;
            animation: countdown-ring 4s 0.8s linear forwards;
        }

        input:focus { outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.2); }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-6">

<?php if ($success): ?>
    <!-- ── Success screen ─────────────────────────────────────────────────── -->
    <div class="animate-fade-in bg-white rounded-2xl shadow-2xl p-10 max-w-md w-full text-center card">

        <!-- Animated checkmark -->
        <div class="flex justify-center mb-6">
            <div class="relative w-24 h-24">
                <svg class="w-24 h-24" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="36" fill="#dcfce7" stroke="#22c55e" stroke-width="3"/>
                    <path class="check-path" d="M24 40 l12 12 l20 -20"
                          fill="none" stroke="#16a34a" stroke-width="4"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <!-- Countdown ring -->
                <svg class="absolute inset-0 w-24 h-24 -rotate-90" viewBox="0 0 80 80">
                    <circle class="countdown-ring" cx="40" cy="40" r="20"
                            fill="none" stroke="#2563eb" stroke-width="3"
                            stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Installation Complete!</h1>
        <p class="text-gray-500 mb-2">Your site is ready. The installer has been deleted.</p>
        <p class="text-sm text-gray-400 mb-8">Redirecting to login in <span id="countdown" class="font-semibold text-blue-600">5</span> seconds…</p>

        <!-- Tables created summary -->
        <div class="bg-gray-50 rounded-lg p-4 text-left mb-6 text-sm text-gray-600 space-y-1">
            <div class="flex items-center gap-2"><span class="text-green-500">✓</span> Table <code class="bg-gray-100 px-1 rounded">users</code> created</div>
            <div class="flex items-center gap-2"><span class="text-green-500">✓</span> Table <code class="bg-gray-100 px-1 rounded">listings</code> created</div>
            <div class="flex items-center gap-2"><span class="text-green-500">✓</span> Table <code class="bg-gray-100 px-1 rounded">listing_images</code> created</div>
            <div class="flex items-center gap-2"><span class="text-green-500">✓</span> Admin account created</div>
            <div class="flex items-center gap-2"><span class="text-green-500">✓</span> Configuration file written</div>
            <div class="flex items-center gap-2"><span class="text-green-500">✓</span> <code class="bg-gray-100 px-1 rounded">install.php</code> deleted</div>
        </div>

        <a href="login.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
            Go to Login Now →
        </a>
    </div>

    <script>
        let secs = 5;
        const el = document.getElementById('countdown');
        setInterval(() => {
            secs--;
            el.textContent = secs;
            if (secs <= 0) window.location.href = 'login.php';
        }, 1000);
    </script>

<?php else: ?>
    <!-- ── Install form ───────────────────────────────────────────────────── -->
    <div class="animate-fade-in w-full max-w-lg">

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white">Installation Wizard</h1>
            <p class="text-blue-200 mt-1">Set up your listings application</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden card">

            <!-- Error banner -->
            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border-b border-red-200 px-6 py-4">
                <p class="text-red-700 font-semibold text-sm mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $err): ?>
                        <li class="text-red-600 text-sm"><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" class="p-8 space-y-6">

                <!-- Section: Database -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-shrink-0 w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">1</div>
                        <h2 class="text-base font-bold text-gray-800 uppercase tracking-wide">Database Connection</h2>
                    </div>
                    <div class="space-y-4 pl-10">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Host</label>
                                <input type="text" name="db_host"
                                       value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"
                                       placeholder="localhost"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm transition-all focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Database Name</label>
                                <input type="text" name="db_name"
                                       value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>"
                                       placeholder="my_database"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm transition-all focus:border-blue-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">DB Username</label>
                                <input type="text" name="db_user"
                                       value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>"
                                       placeholder="db_username"
                                       autocomplete="off"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm transition-all focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">DB Password</label>
                                <input type="password" name="db_pass"
                                       placeholder="••••••••"
                                       autocomplete="new-password"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm transition-all focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="step-divider">

                <!-- Section: Admin Account -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-shrink-0 w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">2</div>
                        <h2 class="text-base font-bold text-gray-800 uppercase tracking-wide">Admin Account</h2>
                    </div>
                    <div class="space-y-4 pl-10">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                                <input type="text" name="admin_user"
                                       value="<?= htmlspecialchars($_POST['admin_user'] ?? '') ?>"
                                       placeholder="admin"
                                       autocomplete="off"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm transition-all focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="email" name="admin_email"
                                       value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>"
                                       placeholder="admin@example.com"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm transition-all focus:border-blue-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                                <input type="password" name="admin_pass"
                                       placeholder="Min. 8 characters"
                                       autocomplete="new-password"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm transition-all focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
                                <input type="password" name="admin_confirm"
                                       placeholder="••••••••"
                                       autocomplete="new-password"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm transition-all focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info note -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-700 flex gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>The database must already exist. This wizard will create the required tables inside it and write your credentials to a config file <strong>outside</strong> the web root.</span>
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-3 px-6 rounded-lg transition-colors text-base shadow-md shadow-blue-200">
                    Install Now →
                </button>

            </form>
        </div>

        <p class="text-center text-blue-200 text-xs mt-6">This page is only accessible before installation is complete.</p>
    </div>
<?php endif; ?>

</body>
</html>
