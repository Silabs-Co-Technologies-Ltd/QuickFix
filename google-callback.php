<?php
/**
 * Google OAuth Callback Handler
 * Processes Google Sign-In responses
 */

header('Content-Type: application/json; charset=UTF-8');

// Environment configuration
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', APP_ENV !== 'production');

// Set error reporting
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// Set timezone
date_default_timezone_set('Africa/Lagos');

// Start session
session_start();

// Load configuration and classes
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/GoogleOAuth.php';

// Initialize database and classes
$config = require __DIR__ . '/config.php';

try {
    $db = new Database(
        $config['database']['host'],
        $config['database']['user'],
        $config['database']['pass'],
        $config['database']['name']
    );

    $userModel = new User($db);
    $googleOAuth = new GoogleOAuth(
        $config['google']['client_id'],
        $config['google']['client_secret'],
        $config['google']['redirect_url'],
        $db
    );

    // Check if credential is provided (from Google Sign-In button)
    if (empty($_POST['credential'])) {
        // Check for authorization code (from OAuth flow)
        if (empty($_GET['code'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No credential or code provided']);
            exit;
        }

        // Handle authorization code exchange
        $code = $_GET['code'];
        $tokenResult = $googleOAuth->getAccessToken($code);

        if (!$tokenResult['success']) {
            http_response_code(400);
            echo json_encode($tokenResult);
            exit;
        }

        $idToken = $tokenResult['data']['id_token'];
    } else {
        // Handle ID token from Sign-In button
        $idToken = $_POST['credential'];
    }

    // Process the callback
    $result = $googleOAuth->handleCallback($idToken, $userModel);

    if (!$result['success']) {
        http_response_code(400);
        echo json_encode($result);
        exit;
    }

    // Set session variables
    $user = $result['user'];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];

    // Log the activity
    error_log("User {$user['email']} logged in via Google OAuth");

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'user' => $user,
        'is_new' => $result['is_new'],
        'redirect' => '/dashboard.php'
    ]);

} catch (Exception $e) {
    error_log('Google OAuth callback error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => APP_DEBUG ? $e->getMessage() : 'An error occurred during authentication'
    ]);
}
?>
