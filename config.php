<?php
/**
 * LocalSEO.pk — Configuration File
 * Version: 1.0.0
 * 
 * This file is generated during installation.
 * Do NOT edit manually unless you know what you are doing.
 */

// Prevent direct access
if (!defined('LSEO_INIT')) {
    http_response_code(403);
    exit('Access denied.');
}

// ---- Database Configuration ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'localseo_db');
define('DB_USER', 'localseo_user');
define('DB_PASS', '');
define('DB_PREFIX', 'lseo_');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// ---- Application Configuration ----
define('APP_NAME', 'LocalSEO.pk');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://www.localseo.pk');
define('APP_ENV', 'production'); // production | development
define('APP_DEBUG', false);
define('APP_TIMEZONE', 'Asia/Karachi');

// ---- Path Configuration ----
define('ROOT_PATH', __DIR__);
define('CORE_PATH', ROOT_PATH . '/core');
define('MODEL_PATH', ROOT_PATH . '/models');
define('CONTROLLER_PATH', ROOT_PATH . '/controllers');
define('VIEW_PATH', ROOT_PATH . '/views');
define('ASSET_PATH', ROOT_PATH . '/assets');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('BACKUP_PATH', ROOT_PATH . '/backups');
define('TEMP_PATH', ROOT_PATH . '/temp');

// ---- Security Configuration ----
define('AUTH_SALT', ''); // Generated during install
define('CSRF_SALT', ''); // Generated during install
define('ENCRYPTION_KEY', ''); // Generated during install
define('SESSION_NAME', 'LSEO_SESSION');
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_SECURE', true);
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Strict');

// ---- Cookie Configuration ----
define('COOKIE_PREFIX', 'lseo_');
define('COOKIE_DOMAIN', '');
define('COOKIE_PATH', '/');
define('COOKIE_SECURE', true);
define('COOKIE_HTTPONLY', true);

// ---- Upload Configuration ----
define('MAX_UPLOAD_SIZE', 10485760); // 10MB
define('IMAGE_MAX_WIDTH', 1920);
define('IMAGE_QUALITY', 85);
define('THUMB_WIDTH', 300);
define('THUMB_HEIGHT', 300);
define('MEDIUM_WIDTH', 768);
define('MEDIUM_HEIGHT', 0);
define('LARGE_WIDTH', 1200);
define('LARGE_HEIGHT', 0);

// ---- Cache Configuration ----
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour default
define('PAGE_CACHE_ENABLED', true);
define('OBJECT_CACHE_ENABLED', true);
define('QUERY_CACHE_ENABLED', true);

// ---- Mail Configuration ----
define('MAIL_FROM_EMAIL', 'info@localseo.pk');
define('MAIL_FROM_NAME', 'LocalSEO.pk');

// ---- Update Configuration ----
define('UPDATE_SERVER_URL', 'https://updates.localseo.pk/manifest.json');
define('UPDATE_CHECK_INTERVAL', 43200); // 12 hours

// ---- Installed Flag ----
define('IS_INSTALLED', true);
