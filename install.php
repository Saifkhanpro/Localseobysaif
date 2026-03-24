<?php
/**
 * LocalSEO.pk Installer
 * Version: 1.0.1
 * 
 * Standalone installer that includes the full schema and seeding logic.
 */
session_start();

// Disable timeouts for installation
set_time_limit(0);
ini_set('memory_limit', '256M');

define('LSEO_VERSION', '1.0.0');
define('ROOT_PATH', __DIR__);

$step = $_GET['action'] ?? 'step1';
$error = '';

// Check system requirements
$checks = [
    'php_version' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'pdo' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'gd' => extension_loaded('gd') || extension_loaded('imagick'),
    'curl' => extension_loaded('curl'),
    'config_writable' => is_writable(__DIR__) || (file_exists(__DIR__ . '/config.php') && is_writable(__DIR__ . '/config.php')),
    'cache_writable' => ensureWritable(__DIR__ . '/cache'),
    'uploads_writable' => ensureWritable(__DIR__ . '/uploads'),
];

$allGood = !in_array(false, $checks, true);

function ensureWritable($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return is_writable($dir);
}

// Process installation
if ($step === 'process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$allGood) {
        die("System requirements not met.");
    }
    
    $host = $_POST['db_host'] ?? 'localhost';
    $name = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    $prefix = $_POST['db_prefix'] ?? 'lseo_';
    
    $admin_user = $_POST['admin_user'] ?? 'admin';
    $admin_email = $_POST['admin_email'] ?? 'admin@example.com';
    $admin_pass = $_POST['admin_pass'] ?? '';
    
    if (empty($name) || empty($user) || empty($admin_pass)) {
        die("Please fill all required fields.");
    }
    
    try {
        // Connect to DB
        $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` COLLATE 'utf8mb4_unicode_ci'");
        $pdo->exec("USE `{$name}`");
        
        // Full Schema Implementation
        $queries = [
            // Users
            "CREATE TABLE IF NOT EXISTS `{$prefix}users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `display_name` VARCHAR(100),
                `first_name` VARCHAR(50),
                `last_name` VARCHAR(50),
                `role` ENUM('subscriber','contributor','author','editor','admin','super_admin') DEFAULT 'subscriber',
                `seo_role` ENUM('none','analyzer','manager') DEFAULT 'none',
                `avatar_id` INT NULL,
                `bio` TEXT,
                `website` VARCHAR(255),
                `social_facebook` VARCHAR(255),
                `social_twitter` VARCHAR(255),
                `social_linkedin` VARCHAR(255),
                `social_instagram` VARCHAR(255),
                `is_active` TINYINT(1) DEFAULT 1,
                `login_attempts` INT DEFAULT 0,
                `locked_until` DATETIME NULL,
                `last_login` DATETIME NULL,
                `last_login_ip` VARCHAR(45),
                `session_token` VARCHAR(64) NULL,
                `last_activity` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`role`), INDEX (`session_token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // Settings
            "CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` LONGTEXT,
                `is_autoload` TINYINT(1) DEFAULT 1,
                INDEX (`setting_group`), INDEX (`is_autoload`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // Media
            "CREATE TABLE IF NOT EXISTS `{$prefix}media` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `file_name` VARCHAR(255) NOT NULL,
                `original_name` VARCHAR(255),
                `file_path` VARCHAR(255) NOT NULL,
                `file_type` VARCHAR(50),
                `mime_type` VARCHAR(100),
                `file_size` BIGINT,
                `title` VARCHAR(255),
                `alt_text` VARCHAR(255),
                `caption` TEXT,
                `description` TEXT,
                `uploaded_by` INT,
                `width` INT,
                `height` INT,
                `is_optimized` TINYINT(1) DEFAULT 0,
                `optimized_size` BIGINT,
                `optimization_date` DATETIME,
                `webp_path` VARCHAR(255) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (`file_type`), INDEX (`uploaded_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // Posts
            "CREATE TABLE IF NOT EXISTS `{$prefix}posts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `author_id` INT NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `content` LONGTEXT,
                `excerpt` TEXT,
                `status` ENUM('draft', 'pending', 'private', 'published', 'trash') DEFAULT 'draft',
                `post_type` VARCHAR(20) DEFAULT 'post',
                `visibility` ENUM('public', 'private', 'password') DEFAULT 'public',
                `password` VARCHAR(255) NULL,
                `featured_image_id` INT NULL,
                `comment_status` ENUM('open', 'closed') DEFAULT 'open',
                `ping_status` ENUM('open', 'closed') DEFAULT 'open',
                `focus_keyword` VARCHAR(255),
                `secondary_keywords` TEXT,
                `seo_title` VARCHAR(255),
                `meta_description` TEXT,
                `robots_index` ENUM('default','index','noindex') DEFAULT 'default',
                `robots_follow` ENUM('default','follow','nofollow') DEFAULT 'default',
                `robots_advanced` VARCHAR(255),
                `canonical_url` VARCHAR(255),
                `primary_category_id` INT NULL,
                `readability_score` INT DEFAULT 0,
                `seo_score` INT DEFAULT 0,
                `schema_type` VARCHAR(50) DEFAULT 'Article',
                `schema_data` JSON NULL,
                `published_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`status`), INDEX (`post_type`), INDEX (`author_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // Pages
            "CREATE TABLE IF NOT EXISTS `{$prefix}pages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `author_id` INT NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `content` LONGTEXT,
                `status` ENUM('draft', 'pending', 'private', 'published', 'trash') DEFAULT 'draft',
                `visibility` ENUM('public', 'private', 'password') DEFAULT 'public',
                `password` VARCHAR(255) NULL,
                `featured_image_id` INT NULL,
                `parent_id` INT NULL,
                `template` VARCHAR(100) DEFAULT 'default',
                `order_index` INT DEFAULT 0,
                `focus_keyword` VARCHAR(255),
                `secondary_keywords` TEXT,
                `seo_title` VARCHAR(255),
                `meta_description` TEXT,
                `robots_index` ENUM('default','index','noindex') DEFAULT 'default',
                `robots_follow` ENUM('default','follow','nofollow') DEFAULT 'default',
                `robots_advanced` VARCHAR(255),
                `canonical_url` VARCHAR(255),
                `readability_score` INT DEFAULT 0,
                `seo_score` INT DEFAULT 0,
                `schema_type` VARCHAR(50) DEFAULT 'WebPage',
                `schema_data` JSON NULL,
                `published_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`status`), INDEX (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // Services
            "CREATE TABLE IF NOT EXISTS `{$prefix}services` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `short_description` TEXT,
                `content` LONGTEXT,
                `icon_class` VARCHAR(100),
                `featured_image_id` INT NULL,
                `status` ENUM('draft', 'published') DEFAULT 'draft',
                `order_index` INT DEFAULT 0,
                `price_range` VARCHAR(50),
                `focus_keyword` VARCHAR(255),
                `secondary_keywords` TEXT,
                `seo_title` VARCHAR(255),
                `meta_description` TEXT,
                `robots_index` ENUM('default','index','noindex') DEFAULT 'default',
                `robots_follow` ENUM('default','follow','nofollow') DEFAULT 'default',
                `robots_advanced` VARCHAR(255),
                `canonical_url` VARCHAR(255),
                `readability_score` INT DEFAULT 0,
                `seo_score` INT DEFAULT 0,
                `schema_type` VARCHAR(50) DEFAULT 'Service',
                `schema_data` JSON NULL,
                `published_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // City Pages
            "CREATE TABLE IF NOT EXISTS `{$prefix}city_pages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `city_name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `state_province` VARCHAR(100),
                `population` VARCHAR(50),
                `latitude` DECIMAL(10,8),
                `longitude` DECIMAL(11,8),
                `content` LONGTEXT,
                `featured_image_id` INT NULL,
                `status` ENUM('draft', 'published') DEFAULT 'draft',
                `map_embed_code` TEXT,
                `focus_keyword` VARCHAR(255),
                `secondary_keywords` TEXT,
                `seo_title` VARCHAR(255),
                `meta_description` TEXT,
                `robots_index` ENUM('default','index','noindex') DEFAULT 'default',
                `robots_follow` ENUM('default','follow','nofollow') DEFAULT 'default',
                `robots_advanced` VARCHAR(255),
                `canonical_url` VARCHAR(255),
                `readability_score` INT DEFAULT 0,
                `seo_score` INT DEFAULT 0,
                `schema_type` VARCHAR(50) DEFAULT 'LocalBusiness',
                `schema_data` JSON NULL,
                `published_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // Categories
            "CREATE TABLE IF NOT EXISTS `{$prefix}categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `parent_id` INT NULL,
                `seo_title` VARCHAR(255),
                `meta_description` TEXT,
                `focus_keyword` VARCHAR(255),
                `robots_index` ENUM('default','index','noindex') DEFAULT 'default',
                `robots_follow` ENUM('default','follow','nofollow') DEFAULT 'default',
                `canonical_url` VARCHAR(255),
                `order_index` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // Leads
            "CREATE TABLE IF NOT EXISTS `{$prefix}leads` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `full_name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(150),
                `phone` VARCHAR(50),
                `whatsapp_number` VARCHAR(50),
                `city` VARCHAR(100),
                `service_needed` VARCHAR(100),
                `message` TEXT,
                `status` ENUM('new', 'contacted', 'qualified', 'converted', 'closed') DEFAULT 'new',
                `ip_address` VARCHAR(45),
                `user_agent` TEXT,
                `source_url` VARCHAR(255),
                `utm_source` VARCHAR(50),
                `utm_medium` VARCHAR(50),
                `utm_campaign` VARCHAR(100),
                `notes` TEXT,
                `assigned_to` INT NULL,
                `is_read` TINYINT(1) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // Redirects
            "CREATE TABLE IF NOT EXISTS `{$prefix}redirects` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `old_url` VARCHAR(255) NOT NULL,
                `new_url` VARCHAR(255) NOT NULL,
                `type` INT DEFAULT 301,
                `match_type` ENUM('exact','exact_ignore','contains','begins_with','regex') DEFAULT 'exact',
                `is_active` TINYINT(1) DEFAULT 1,
                `hits` INT DEFAULT 0,
                `last_accessed` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`old_url`), INDEX (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            // Revisions
            "CREATE TABLE IF NOT EXISTS `{$prefix}revisions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `post_id` INT NOT NULL,
                `author_id` INT NOT NULL,
                `content` LONGTEXT,
                `title` VARCHAR(255),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (`post_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];
        
        foreach ($queries as $sql) { $pdo->exec($sql); }
        
        // Seed Admin User
        $hashedPass = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, email, password, display_name, role, seo_role, is_active) VALUES (?, ?, ?, 'Administrator', 'super_admin', 'manager', 1)");
        $stmt->execute([$admin_user, $admin_email, $hashedPass]);
        
        // Seed Settings
        $settings = [
            ['general', 'site_title', 'LocalSEO.pk'],
            ['general', 'site_description', 'The leading local SEO agency'],
            ['general', 'site_url', 'http://' . $_SERVER['HTTP_HOST']],
            ['general', 'timezone', 'Asia/Karachi'],
            ['general', 'admin_email', $admin_email],
            ['seo', 'seo_business_name', 'LocalSEO.pk'],
            ['seo', 'seo_module_sitemap', '1'],
            ['seo', 'seo_module_redirections', '1'],
            ['seo', 'seo_module_schema', '1'],
            ['seo', 'seo_module_local', '1'],
            ['performance', 'cache_enabled', '1'],
            ['performance', 'cache_minify_html', '1'],
            ['performance', 'cache_minify_css', '1'],
            ['performance', 'cache_minify_js', '1'],
            ['performance', 'cache_combine_css', '1'],
            ['performance', 'cache_lazy_load', '1'],
            ['performance', 'image_convert_to_webp', '1'],
            ['updates', 'last_update_check', '0'],
            ['updates', 'update_check_interval', '43200'],
            ['updates', 'update_notify_email', '1'],
            ['security', 'security_headers_enabled', '1'],
            ['security', 'login_max_attempts', '5'],
            ['security', 'login_lockout_duration', '15']
        ];
        $sStmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}settings` (setting_group, setting_key, setting_value) VALUES (?, ?, ?)");
        foreach ($settings as $s) { $sStmt->execute($s); }
        
        // Write config.php
        $config = "<?php\n\n";
        $config .= "if (!defined('LSEO_INIT')) exit;\n\n";
        $config .= "define('DB_HOST', '{$host}');\n";
        $config .= "define('DB_NAME', '{$name}');\n";
        $config .= "define('DB_USER', '{$user}');\n";
        $config .= "define('DB_PASS', '{$pass}');\n";
        $config .= "define('DB_PREFIX', '{$prefix}');\n\n";
        $config .= "define('APP_URL', 'http://' . \$_SERVER['HTTP_HOST']);\n";
        $config .= "define('APP_ENV', 'production');\n";
        $config .= "define('APP_DEBUG', false);\n\n";
        $config .= "define('APP_KEY', '" . bin2hex(random_bytes(32)) . "');\n";
        $config .= "define('PASSWORD_SALT', '" . bin2hex(random_bytes(16)) . "');\n\n";
        $config .= "define('IS_INSTALLED', true);\n\n";
        $config .= "define('ROOT_PATH', __DIR__);\n";
        $config .= "define('CORE_PATH', ROOT_PATH . '/core');\n";
        $config .= "define('MODEL_PATH', ROOT_PATH . '/models');\n";
        $config .= "define('CONTROLLER_PATH', ROOT_PATH . '/controllers');\n";
        $config .= "define('VIEW_PATH', ROOT_PATH . '/views');\n";
        $config .= "define('CACHE_PATH', ROOT_PATH . '/cache');\n";
        $config .= "define('UPLOAD_PATH', ROOT_PATH . '/uploads');\n";
        
        file_put_contents(__DIR__ . '/config.php', $config);
        
        header("Location: /install.php?action=step2");
        exit;
        
    } catch (Exception $e) {
        $error = "Database Error: " . $e->getMessage();
        $step = 'step1';
    }
}

if ($step === 'step2') {
    echo '<!DOCTYPE html><html><head><title>Install Complete</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"></head><body class="bg-light">';
    echo '<div class="container mt-5 text-center"><div class="card p-5 border-0 shadow-lg rounded-4 mx-auto" style="max-width: 600px;">';
    echo '<i class="bi bi-patch-check-fill text-success" style="font-size: 5rem;"></i>';
    echo '<h1 class="mt-4 fw-bolder">Setup Completed!</h1>';
    echo '<p class="lead text-muted">Your LocalSEO.pk CMS v' . LSEO_VERSION . ' is ready to dominate the SERPs.</p>';
    echo '<div class="alert alert-warning small"><i class="bi bi-shield-lock me-2"></i> Security Tip: Delete <code>install.php</code> before proceeding.</div>';
    echo '<a href="/admin/login" class="btn btn-primary btn-lg mt-3 rounded-pill px-5 fw-bold w-100 py-3 shadow">Access Admin Dashboard</a>';
    echo '</div></div></body></html>';
    exit;
}

// Minimal Step 1 UI inline if view doesn't exist or to keep it standalone
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>LocalSEO.pk - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; } .card { border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.05); }</style>
</head>
<body>
<div class="container py-5">
    <div class="card mx-auto overflow-hidden" style="max-width: 900px;">
        <div class="row g-0">
            <div class="col-md-4 bg-primary text-white p-5 d-flex flex-column justify-content-center align-items-center text-center">
                <i class="bi bi-lightning-charge-fill display-1 mb-4"></i>
                <h2 class="fw-bold">LocalSEO.pk</h2>
                <p class="opacity-75">Enterprise Grade CMS for Local SEO DOMINATION.</p>
            </div>
            <div class="col-md-8 p-5">
                <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                
                <?php if (!$allGood): ?>
                    <h4 class="mb-4">System Check</h4>
                    <div class="list-group mb-4 shadow-sm">
                        <?php foreach($checks as $key => $passed): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <?= str_replace('_', ' ', ucfirst($key)) ?>
                                <?php if($passed): ?><i class="bi bi-check-circle-fill text-success"></i><?php else: ?><i class="bi bi-x-circle-fill text-danger"></i><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <form method="POST" action="?action=process">
                        <h4 class="mb-4 fw-bold">Database Configuration</h4>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Host</label><input type="text" name="db_host" class="form-control" value="localhost"></div>
                            <div class="col-md-6"><label class="form-label">DB Name</label><input type="text" name="db_name" class="form-control" placeholder="localseo_db" required></div>
                            <div class="col-md-6"><label class="form-label">User</label><input type="text" name="db_user" class="form-control" placeholder="root" required></div>
                            <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="db_pass" class="form-control"></div>
                        </div>
                        <h4 class="mt-5 mb-4 fw-bold">Administrator Setup</h4>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">User</label><input type="text" name="admin_user" class="form-control" value="admin"></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="admin_email" class="form-control" placeholder="admin@localseo.pk" required></div>
                            <div class="col-12"><label class="form-label">Password</label><input type="password" name="admin_pass" class="form-control" required></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-5 py-3 rounded-pill fw-bold">START INSTALLATION</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
