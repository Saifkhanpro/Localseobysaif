<?php
/**
 * LocalSEO.pk — Front Controller
 * Version: 1.0.0
 * 
 * All requests are routed through this file.
 */

// Define initialization constant
define('LSEO_INIT', true);

// Start output buffering
ob_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Karachi');

// Check if installed
if (!file_exists(__DIR__ . '/config.php') || !defined('LSEO_INIT')) {
    if (file_exists(__DIR__ . '/install.php')) {
        header('Location: /install.php');
        exit;
    }
    die('Configuration file not found. Please run the installer.');
}

// Load configuration
require_once __DIR__ . '/config.php';

// Check installation status
if (defined('IS_INSTALLED') && IS_INSTALLED !== true) {
    if (file_exists(__DIR__ . '/install.php')) {
        header('Location: /install.php');
        exit;
    }
}

// Set timezone from config
if (defined('APP_TIMEZONE')) {
    date_default_timezone_set(APP_TIMEZONE);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200);

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'LSEO_SESSION');
session_start();

// Regenerate session ID periodically
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// Load core files
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Helpers.php';
require_once CORE_PATH . '/Security.php';
require_once CORE_PATH . '/CSRF.php';
require_once CORE_PATH . '/Validator.php';
require_once CORE_PATH . '/Auth.php';
require_once CORE_PATH . '/Model.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Cache.php';
require_once CORE_PATH . '/PageCache.php';
require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/version.php';

// Load models
require_once MODEL_PATH . '/Setting.php';

// Initialize core singletons
$db = \Core\Database::getInstance();
$security = new \Core\Security();
$auth = new \Core\Auth();

// Load autoloaded settings into memory
$settingModel = new \Models\Setting();
$GLOBALS['settings'] = $settingModel->getAutoloaded();

// Security: blocked IP check
$clientIP = $security->getClientIP();
if ($security->isIPBlocked($clientIP)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>403 — Access Denied</h1><p>Your IP has been blocked due to suspicious activity.</p></body></html>';
    exit;
}

// Security: log request for suspicious patterns
$security->checkRequest();

// Page caching: try to serve cached page for GET requests on frontend
$pageCache = null;
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isAdmin = strpos($requestUri, '/admin') === 0;
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$isAdmin && !$isAjax && !isset($_SESSION['user_id'])) {
    $pageCache = new \Core\PageCache();
    $cachedContent = $pageCache->get($requestUri);
    if ($cachedContent !== false) {
        header('X-Cache: HIT');
        echo $cachedContent;
        ob_end_flush();
        exit;
    }
    header('X-Cache: MISS');
}

// Initialize and run the router
$router = new \Core\Router();

// ========================================
// FRONTEND ROUTES
// ========================================

// Homepage
$router->get('/', 'frontend/HomeController@index');

// Blog
$router->get('/blog', 'frontend/BlogController@index');
$router->get('/blog/page/{page}', 'frontend/BlogController@index');
$router->get('/blog/{slug}', 'frontend/BlogController@show');

// Services
$router->get('/services', 'frontend/ServiceController@index');
$router->get('/services/{slug}', 'frontend/ServiceController@show');

// City Pages
$router->get('/local-seo/{slug}', 'frontend/CityController@show');

// Case Studies
$router->get('/case-studies', 'frontend/CaseStudyController@index');
$router->get('/case-studies/{slug}', 'frontend/CaseStudyController@show');

// Testimonials
$router->get('/testimonials', 'frontend/TestimonialController@index');

// Contact
$router->get('/contact', 'frontend/ContactController@index');
$router->post('/contact', 'frontend/ContactController@submit');

// Search
$router->get('/search', 'frontend/SearchController@index');

// Comments
$router->post('/comment', 'frontend/CommentController@store');

// Sitemap
$router->get('/sitemap', 'frontend/SitemapController@index');

// Robots
$router->get('/robots', 'frontend/RobotsController@index');

// RSS Feed
$router->get('/feed', 'frontend/BlogController@feed');

// Categories
$router->get('/category/{slug}', 'frontend/BlogController@category');
$router->get('/category/{slug}/page/{page}', 'frontend/BlogController@category');

// Tags
$router->get('/tag/{slug}', 'frontend/BlogController@tag');
$router->get('/tag/{slug}/page/{page}', 'frontend/BlogController@tag');

// Pages (catch-all for custom pages — must be last among frontend)
$router->get('/{slug}', 'frontend/PageController@show');

// ========================================
// ADMIN AUTH ROUTES
// ========================================
$router->get('/admin/login', 'admin/AuthController@loginForm');
$router->post('/admin/login', 'admin/AuthController@login');
$router->get('/admin/logout', 'admin/AuthController@logout');
$router->get('/admin/forgot-password', 'admin/AuthController@forgotForm');
$router->post('/admin/forgot-password', 'admin/AuthController@forgotPassword');
$router->get('/admin/reset-password/{token}', 'admin/AuthController@resetForm');
$router->post('/admin/reset-password', 'admin/AuthController@resetPassword');

// ========================================
// ADMIN DASHBOARD ROUTES
// ========================================
$router->get('/admin', 'admin/DashboardController@index');
$router->get('/admin/dashboard', 'admin/DashboardController@index');

// Admin: Posts
$router->get('/admin/posts', 'admin/PostController@index');
$router->get('/admin/posts/create', 'admin/PostController@create');
$router->post('/admin/posts/store', 'admin/PostController@store');
$router->get('/admin/posts/edit/{id}', 'admin/PostController@edit');
$router->post('/admin/posts/update/{id}', 'admin/PostController@update');
$router->post('/admin/posts/delete/{id}', 'admin/PostController@delete');
$router->post('/admin/posts/bulk', 'admin/PostController@bulk');
$router->get('/admin/posts/revisions/{id}', 'admin/PostController@revisions');
$router->post('/admin/posts/restore-revision/{id}', 'admin/PostController@restoreRevision');

// Admin: Categories
$router->get('/admin/categories', 'admin/CategoryController@index');
$router->post('/admin/categories/store', 'admin/CategoryController@store');
$router->get('/admin/categories/edit/{id}', 'admin/CategoryController@edit');
$router->post('/admin/categories/update/{id}', 'admin/CategoryController@update');
$router->post('/admin/categories/delete/{id}', 'admin/CategoryController@delete');

// Admin: Tags
$router->get('/admin/tags', 'admin/TagController@index');
$router->post('/admin/tags/store', 'admin/TagController@store');
$router->get('/admin/tags/edit/{id}', 'admin/TagController@edit');
$router->post('/admin/tags/update/{id}', 'admin/TagController@update');
$router->post('/admin/tags/delete/{id}', 'admin/TagController@delete');

// Admin: Pages
$router->get('/admin/pages', 'admin/PageController@index');
$router->get('/admin/pages/create', 'admin/PageController@create');
$router->post('/admin/pages/store', 'admin/PageController@store');
$router->get('/admin/pages/edit/{id}', 'admin/PageController@edit');
$router->post('/admin/pages/update/{id}', 'admin/PageController@update');
$router->post('/admin/pages/delete/{id}', 'admin/PageController@delete');

// Admin: Media
$router->get('/admin/media', 'admin/MediaController@index');
$router->get('/admin/media/upload', 'admin/MediaController@uploadForm');
$router->post('/admin/media/upload', 'admin/MediaController@upload');
$router->post('/admin/media/update/{id}', 'admin/MediaController@update');
$router->post('/admin/media/delete/{id}', 'admin/MediaController@delete');
$router->get('/admin/media/optimize', 'admin/MediaController@optimizeIndex');
$router->post('/admin/media/optimize/{id}', 'admin/MediaController@optimizeSingle');
$router->post('/admin/media/optimize-bulk', 'admin/MediaController@optimizeBulk');
$router->get('/admin/api/media/browse', 'admin/MediaController@browse');

// Admin: Services
$router->get('/admin/services', 'admin/ServiceController@index');
$router->get('/admin/services/create', 'admin/ServiceController@create');
$router->post('/admin/services/store', 'admin/ServiceController@store');
$router->get('/admin/services/edit/{id}', 'admin/ServiceController@edit');
$router->post('/admin/services/update/{id}', 'admin/ServiceController@update');
$router->post('/admin/services/delete/{id}', 'admin/ServiceController@delete');

// Admin: City Pages
$router->get('/admin/city-pages', 'admin/CityController@index');
$router->get('/admin/city-pages/create', 'admin/CityController@create');
$router->post('/admin/city-pages/store', 'admin/CityController@store');
$router->get('/admin/city-pages/edit/{id}', 'admin/CityController@edit');
$router->post('/admin/city-pages/update/{id}', 'admin/CityController@update');
$router->post('/admin/city-pages/delete/{id}', 'admin/CityController@delete');

// Admin: Case Studies
$router->get('/admin/case-studies', 'admin/CaseStudyController@index');
$router->get('/admin/case-studies/create', 'admin/CaseStudyController@create');
$router->post('/admin/case-studies/store', 'admin/CaseStudyController@store');
$router->get('/admin/case-studies/edit/{id}', 'admin/CaseStudyController@edit');
$router->post('/admin/case-studies/update/{id}', 'admin/CaseStudyController@update');
$router->post('/admin/case-studies/delete/{id}', 'admin/CaseStudyController@delete');

// Admin: Testimonials
$router->get('/admin/testimonials', 'admin/TestimonialController@index');
$router->get('/admin/testimonials/create', 'admin/TestimonialController@create');
$router->post('/admin/testimonials/store', 'admin/TestimonialController@store');
$router->get('/admin/testimonials/edit/{id}', 'admin/TestimonialController@edit');
$router->post('/admin/testimonials/update/{id}', 'admin/TestimonialController@update');
$router->post('/admin/testimonials/delete/{id}', 'admin/TestimonialController@delete');

// Admin: Team
$router->get('/admin/team', 'admin/TeamController@index');
$router->get('/admin/team/create', 'admin/TeamController@create');
$router->post('/admin/team/store', 'admin/TeamController@store');
$router->get('/admin/team/edit/{id}', 'admin/TeamController@edit');
$router->post('/admin/team/update/{id}', 'admin/TeamController@update');
$router->post('/admin/team/delete/{id}', 'admin/TeamController@delete');

// Admin: Comments
$router->get('/admin/comments', 'admin/CommentController@index');
$router->post('/admin/comments/approve/{id}', 'admin/CommentController@approve');
$router->post('/admin/comments/spam/{id}', 'admin/CommentController@spam');
$router->post('/admin/comments/trash/{id}', 'admin/CommentController@trash');
$router->post('/admin/comments/delete/{id}', 'admin/CommentController@delete');
$router->post('/admin/comments/reply/{id}', 'admin/CommentController@reply');
$router->post('/admin/comments/bulk', 'admin/CommentController@bulk');

// Admin: Contacts / Leads
$router->get('/admin/leads', 'admin/LeadController@index');
$router->get('/admin/leads/view/{id}', 'admin/LeadController@show');
$router->post('/admin/leads/star/{id}', 'admin/LeadController@star');
$router->post('/admin/leads/notes/{id}', 'admin/LeadController@updateNotes');
$router->post('/admin/leads/delete/{id}', 'admin/LeadController@delete');
$router->get('/admin/leads/export', 'admin/LeadController@export');

// Admin: Users
$router->get('/admin/users', 'admin/UserController@index');
$router->get('/admin/users/create', 'admin/UserController@create');
$router->post('/admin/users/store', 'admin/UserController@store');
$router->get('/admin/users/edit/{id}', 'admin/UserController@edit');
$router->post('/admin/users/update/{id}', 'admin/UserController@update');
$router->post('/admin/users/delete/{id}', 'admin/UserController@delete');

// Admin: Profile
$router->get('/admin/profile', 'admin/ProfileController@index');
$router->post('/admin/profile/update', 'admin/ProfileController@update');
$router->post('/admin/profile/password', 'admin/ProfileController@changePassword');
$router->post('/admin/profile/avatar', 'admin/ProfileController@updateAvatar');

// Admin: Redirects
$router->get('/admin/redirects', 'admin/RedirectController@index');
$router->post('/admin/redirects/store', 'admin/RedirectController@store');
$router->post('/admin/redirects/update/{id}', 'admin/RedirectController@update');
$router->post('/admin/redirects/delete/{id}', 'admin/RedirectController@delete');
$router->post('/admin/redirects/import', 'admin/RedirectController@import');
$router->get('/admin/redirects/export', 'admin/RedirectController@export');

// Admin: Security
$router->get('/admin/security', 'admin/SecurityController@log');
$router->get('/admin/security/blocked-ips', 'admin/SecurityController@blockedIPs');
$router->post('/admin/security/block-ip', 'admin/SecurityController@blockIP');
$router->post('/admin/security/unblock-ip/{id}', 'admin/SecurityController@unblockIP');
$router->get('/admin/security/settings', 'admin/SecurityController@settings');
$router->post('/admin/security/settings', 'admin/SecurityController@saveSettings');

// Admin: Tools
$router->get('/admin/tools', 'admin/ToolController@index');
$router->get('/admin/tools/export', 'admin/ToolController@exportForm');
$router->post('/admin/tools/export', 'admin/ToolController@export');
$router->get('/admin/tools/import', 'admin/ToolController@importForm');
$router->post('/admin/tools/import', 'admin/ToolController@import');
$router->get('/admin/tools/site-health', 'admin/ToolController@siteHealth');
$router->get('/admin/tools/backup', 'admin/ToolController@backupForm');
$router->post('/admin/tools/backup/create', 'admin/ToolController@createBackup');
$router->post('/admin/tools/backup/download/{file}', 'admin/ToolController@downloadBackup');
$router->post('/admin/tools/backup/delete/{file}', 'admin/ToolController@deleteBackup');
$router->get('/admin/tools/personal-data', 'admin/ToolController@personalData');
$router->post('/admin/tools/personal-data/export', 'admin/ToolController@exportPersonalData');
$router->post('/admin/tools/personal-data/erase', 'admin/ToolController@erasePersonalData');

// Admin: Settings
$router->get('/admin/settings', 'admin/SettingController@general');
$router->get('/admin/settings/{tab}', 'admin/SettingController@show');
$router->post('/admin/settings/{tab}', 'admin/SettingController@save');

// Admin: Cache
$router->get('/admin/cache', 'admin/CacheController@index');
$router->post('/admin/cache/clear-all', 'admin/CacheController@clearAll');
$router->post('/admin/cache/clear/{type}', 'admin/CacheController@clearType');
$router->post('/admin/cache/preload', 'admin/CacheController@preload');
$router->post('/admin/cache/save', 'admin/CacheController@saveSettings');

// Admin: Speed
$router->get('/admin/speed', 'admin/SpeedController@index');
$router->post('/admin/speed/save', 'admin/SpeedController@save');
$router->post('/admin/speed/cleanup', 'admin/SpeedController@databaseCleanup');

// Admin: Sitemap
$router->get('/admin/sitemap', 'admin/SitemapController@index');
$router->post('/admin/sitemap/generate', 'admin/SitemapController@generate');
$router->post('/admin/sitemap/save', 'admin/SitemapController@save');

// Admin: Updates
$router->get('/admin/updates', 'admin/UpdateController@index');
$router->post('/admin/updates/check', 'admin/UpdateController@check');
$router->post('/admin/updates/install', 'admin/UpdateController@install');
$router->get('/admin/updates/changelog', 'admin/UpdateController@changelog');
$router->get('/admin/updates/history', 'admin/UpdateController@history');
$router->post('/admin/updates/rollback/{id}', 'admin/UpdateController@rollback');
$router->get('/admin/updates/settings', 'admin/UpdateController@settings');
$router->post('/admin/updates/settings', 'admin/UpdateController@saveSettings');

// Admin: Environment
$router->get('/admin/environment', 'admin/EnvironmentController@index');
$router->get('/admin/environment/php-settings', 'admin/EnvironmentController@phpSettings');
$router->post('/admin/environment/php-settings', 'admin/EnvironmentController@savePhpSettings');
$router->get('/admin/environment/htaccess', 'admin/EnvironmentController@htaccess');
$router->post('/admin/environment/htaccess', 'admin/EnvironmentController@saveHtaccess');
$router->get('/admin/environment/database', 'admin/EnvironmentController@database');
$router->post('/admin/environment/database/optimize', 'admin/EnvironmentController@optimizeDatabase');
$router->post('/admin/environment/database/repair', 'admin/EnvironmentController@repairDatabase');
$router->get('/admin/environment/report', 'admin/EnvironmentController@report');

// Admin: SEO
$router->get('/admin/seo', 'admin/SEOController@dashboard');
$router->get('/admin/seo/setup', 'admin/SEOController@setupWizard');
$router->post('/admin/seo/setup', 'admin/SEOController@saveSetup');
$router->get('/admin/seo/modules', 'admin/SEOController@modules');
$router->post('/admin/seo/modules', 'admin/SEOController@saveModules');
$router->get('/admin/seo/general', 'admin/SEOController@general');
$router->post('/admin/seo/general', 'admin/SEOController@saveGeneral');
$router->get('/admin/seo/local', 'admin/SEOController@local');
$router->post('/admin/seo/local', 'admin/SEOController@saveLocal');
$router->get('/admin/seo/schema-templates', 'admin/SEOController@schemaTemplates');
$router->post('/admin/seo/schema-templates', 'admin/SEOController@saveSchemaTemplate');
$router->get('/admin/seo/redirections', 'admin/SEOController@redirections');
$router->post('/admin/seo/redirections/store', 'admin/SEOController@storeRedirect');
$router->post('/admin/seo/redirections/import', 'admin/SEOController@importRedirects');
$router->get('/admin/seo/redirections/export', 'admin/SEOController@exportRedirects');
$router->get('/admin/seo/404-monitor', 'admin/SEOController@monitor404');
$router->post('/admin/seo/404-monitor/resolve', 'admin/SEOController@resolve404');
$router->post('/admin/seo/404-monitor/redirect', 'admin/SEOController@create404Redirect');
$router->post('/admin/seo/404-monitor/cleanup', 'admin/SEOController@cleanup404');
$router->get('/admin/seo/internal-links', 'admin/SEOController@internalLinks');
$router->get('/admin/seo/auto-linking', 'admin/SEOController@autoLinking');
$router->post('/admin/seo/auto-linking/store', 'admin/SEOController@storeAutoLink');
$router->post('/admin/seo/auto-linking/update/{id}', 'admin/SEOController@updateAutoLink');
$router->post('/admin/seo/auto-linking/delete/{id}', 'admin/SEOController@deleteAutoLink');
$router->get('/admin/seo/analytics', 'admin/SEOController@analytics');
$router->post('/admin/seo/analytics/connect', 'admin/SEOController@connectAnalytics');
$router->post('/admin/seo/analytics/sync', 'admin/SEOController@syncAnalytics');
$router->get('/admin/seo/image-seo', 'admin/SEOController@imageSeo');
$router->post('/admin/seo/image-seo/bulk-alt', 'admin/SEOController@bulkUpdateAlt');
$router->get('/admin/seo/instant-indexing', 'admin/SEOController@instantIndexing');
$router->post('/admin/seo/instant-indexing/submit', 'admin/SEOController@submitForIndexing');
$router->get('/admin/seo/import-export', 'admin/SEOController@importExport');
$router->post('/admin/seo/import', 'admin/SEOController@importSeoData');
$router->get('/admin/seo/export', 'admin/SEOController@exportSeoData');

// Admin: SEO API (AJAX)
$router->post('/admin/api/seo/analyze', 'admin/SEOController@analyzeContent');
$router->get('/admin/api/seo/keyword-check', 'admin/SEOController@checkKeywordUsage');
$router->get('/admin/api/seo/link-suggestions', 'admin/SEOController@getLinkSuggestions');
$router->get('/admin/api/seo/schema-preview', 'admin/SEOController@schemaPreview');
$router->post('/admin/api/seo/auto-meta', 'admin/SEOController@generateMeta');

// Admin: API endpoints (AJAX)
$router->post('/admin/api/autosave', 'admin/PostController@autosave');
$router->get('/admin/api/stats', 'admin/DashboardController@stats');

// Error routes
$router->get('/error/403', 'frontend/PageController@error403');
$router->get('/error/404', 'frontend/PageController@error404');
$router->get('/error/500', 'frontend/PageController@error500');

// Dispatch the request
$router->dispatch();

// Cache the output for frontend GET requests
if ($pageCache && $_SERVER['REQUEST_METHOD'] === 'GET' && !$isAdmin && http_response_code() === 200) {
    $output = ob_get_contents();
    $pageCache->set($requestUri, $output);
}

ob_end_flush();
