<?php
// config/config.php
define('APP_NAME', 'Texon Dashboard');
define('APP_URL', 'http://collegesportsdirectory.com/texon_dashboard'); // Change this to your actual URL
define('COMPANY_DOMAIN', 'texontowel.com');
define('APP_PATH', dirname(__DIR__));
define('DEBUG_MODE', true); // Set to false in production

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'collegesportsdir_live');
define('DB_USER', 'collegesportsdir_live');
define('DB_PASS', 'kKn^8fsZnOoH');
define('DB_PREFIX', 'texon_');

// Session settings
define('SESSION_LIFETIME', 86400); // 24 hours
define('COOKIE_PATH', '/');
define('COOKIE_DOMAIN', '');
define('COOKIE_SECURE', false); // Set to true if using HTTPS
define('COOKIE_HTTP', true);

// API configurations
define('BRIGHTPEARL_API_URL', 'https://ws-use.brightpearl.com/');
define('BRIGHTPEARL_ACCOUNT_CODE', 'texon');

define('KLAVIYO_API_URL', 'https://a.klaviyo.com/api');
define('KLAVIYO_API_VERSION', '2023-09-15');
?>
