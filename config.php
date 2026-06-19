<?php
/**
 * QuickFix Nearby - Configuration File
 * This file contains all environment-specific settings, database credentials,
 * and application-wide constants.
 */

// 1. Environment Settings
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'development' or 'production'
define('APP_DEBUG', getenv('APP_DEBUG') ?: true);

// 2. Database Configuration
// These values should be set in your environment variables for production
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'quickfix_db');
define('DB_CHARSET', 'utf8mb4');

// 3. Email Settings
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@quickfix.ng');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'QuickFix Nearby');
define('MAIL_ADMIN_EMAIL', getenv('MAIL_ADMIN_EMAIL') ?: 'admin@quickfix.ng');

// 4. Security Settings
define('AUTH_SALT', getenv('AUTH_SALT') ?: 'qf_secure_random_string_change_me');
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
define('PASSWORD_MIN_LENGTH', 6);

// 5. API & Integration
define('API_BASE_URL', getenv('API_BASE_URL') ?: 'https://api.quickfix.ng/v1');
define('MAPS_API_KEY', getenv('MAPS_API_KEY') ?: '');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'your-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'your-client-secret');
define('GOOGLE_REDIRECT_URL', getenv('GOOGLE_REDIRECT_URL') ?: 'http://localhost:8000/google-callback.php');

// 6. Feature Flags
define('ENABLE_REGISTRATION', true);
define('ENABLE_SOCIAL_LOGIN', true);
define('MAINTENANCE_MODE', false);

/**
 * Return configuration as an associative array for easy access
 */
return [
    'app' => [
        'env' => APP_ENV,
        'debug' => APP_DEBUG,
        'name' => 'QuickFix Nearby',
        'version' => '2.0.0',
        'url' => getenv('APP_URL') ?: 'http://localhost:8000',
    ],
    'database' => [
        'host' => DB_HOST,
        'port' => DB_PORT,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'name' => DB_NAME,
        'charset' => DB_CHARSET,
    ],
    'mail' => [
        'from' => MAIL_FROM_ADDRESS,
        'name' => MAIL_FROM_NAME,
        'admin' => MAIL_ADMIN_EMAIL,
    ],
    'auth' => [
        'salt' => AUTH_SALT,
        'session_timeout' => SESSION_LIFETIME,
        'min_password' => PASSWORD_MIN_LENGTH,
    ],
    'features' => [
        'registration' => ENABLE_REGISTRATION,
        'social_login' => ENABLE_SOCIAL_LOGIN,
        'maintenance' => MAINTENANCE_MODE,
    ],
    'google' => [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_url' => GOOGLE_REDIRECT_URL,
    ],
];
