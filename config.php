<?php
/**
 * QuickFix Configuration File
 * Environment-specific settings for deployment
 */

// Database configuration (optional - for future use)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'quickfix');

// Email configuration
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@quickfix.ng');
define('MAIL_ADMIN', getenv('MAIL_ADMIN') ?: 'admin@quickfix.ng');

// API endpoints
define('API_BASE_URL', getenv('API_BASE_URL') ?: 'https://api.quickfix.ng');

// Feature flags
define('ENABLE_ANALYTICS', getenv('ENABLE_ANALYTICS') ?: 'true');
define('ENABLE_NOTIFICATIONS', getenv('ENABLE_NOTIFICATIONS') ?: 'false');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_FORM_SIZE', 10485760); // 10MB in bytes

// Return configuration array
return [
    'database' => [
        'host' => DB_HOST,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'name' => DB_NAME,
    ],
    'email' => [
        'from' => MAIL_FROM,
        'admin' => MAIL_ADMIN,
    ],
    'api' => [
        'base_url' => API_BASE_URL,
    ],
    'features' => [
        'analytics' => ENABLE_ANALYTICS === 'true',
        'notifications' => ENABLE_NOTIFICATIONS === 'true',
    ],
];