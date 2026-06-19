<?php
/**
 * QuickFix Login Page
 * Handles user authentication
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-UA-Compatible: IE=edge');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Environment configuration
define('APP_NAME', 'QuickFix Nearby');
define('APP_VERSION', '2.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// Set error reporting based on environment
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Set timezone
date_default_timezone_set('Africa/Lagos');

// Start session
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

// Load configuration and classes
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';

// Initialize database and user class
$config = require __DIR__ . '/config.php';
$db = new Database(
    $config['database']['host'],
    $config['database']['user'],
    $config['database']['pass'],
    $config['database']['name']
);
$userModel = new User($db);

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize form variables
$form_success = false;
$form_error = null;
$form_data = [
    'email' => ''
];

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $form_error = 'Security validation failed. Please try again.';
    } else {
        $email = htmlspecialchars(strip_tags(trim($_POST['email'] ?? '')), ENT_QUOTES, 'UTF-8');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Store form data for display
        $form_data['email'] = $email;

        // Attempt login
        $result = $userModel->login($email, $password);

        if ($result['success']) {
            $user = $result['user'];

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Set remember me cookie (7 days)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (7 * 24 * 60 * 60), '/', '', true, true);
                // In production, store this token in database for validation
            }

            $form_success = true;

            // Redirect to dashboard after 1 second
            header('Refresh: 1; url=/dashboard.php');
        } else {
            $form_error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#003d82">
    <meta name="description" content="Sign in to your QuickFix Nearby account.">
    <meta name="keywords" content="login, signin, services, professionals, Nigeria">
    <title><?php echo APP_NAME; ?> | Sign In</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/logo.png">
    <link rel="icon" type="image/png" href="/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepBlue: '#003d82',
                        brightGreen: '#00b896',
                        lightBlue: '#e8f4f8',
                        darkBlue: '#001f41'
                    },
                    boxShadow: {
                        soft: '0 24px 70px rgba(0, 61, 130, 0.12)',
                        lg: '0 20px 25px -5px rgba(0, 61, 130, 0.1)'
                    }
                }
            }
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif; }

        .hero-gradient {
            background: linear-gradient(135deg, #003d82 0%, #005fa3 50%, #00b896 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(0, 184, 150, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-input:focus {
            outline: none;
            border-color: #00b896;
            box-shadow: 0 0 0 3px rgba(0, 184, 150, 0.1);
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased">
    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 border-b border-gray-100 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/60">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-3 focus:outline-none focus:ring-2 focus:ring-brightGreen focus:ring-offset-2 rounded-lg" aria-label="<?php echo APP_NAME; ?> home">
                <span class="grid h-11 w-11 place-items-center overflow-hidden rounded-lg bg-deepBlue shadow-lg flex-shrink-0">
                    <img src="/logo.png" alt="<?php echo APP_NAME; ?> logo" class="h-full w-full object-cover" onerror="this.style.background='linear-gradient(135deg, #003d82, #00b896)'">
                </span>
                <span>
                    <span class="block text-lg font-bold tracking-tight text-deepBlue">QuickFix</span>
                    <span class="block text-xs font-semibold uppercase tracking-widest text-brightGreen">Professional Services</span>
                </span>
            </a>

            <div class="flex items-center gap-3">
                <a href="/register.php" class="rounded-full bg-deepBlue px-5 py-2.5 text-sm font-bold text-white transition hover:bg-darkBlue focus:outline-none focus:ring-2 focus:ring-brightGreen focus:ring-offset-2">
                    Sign Up
                </a>
            </div>
        </div>
    </header>

    <main>
        <!-- Login Section -->
        <section class="min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <!-- Success Alert -->
                <?php if ($form_success): ?>
                    <div class="alert mb-6 rounded-lg bg-green-50 border border-green-200 p-4">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-green-800">Login successful!</p>
                                <p class="text-xs text-green-700 mt-1">Redirecting to dashboard...</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Alert -->
                <?php if ($form_error): ?>
                    <div class="alert mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-red-800"><?php echo htmlspecialchars($form_error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <div class="fade-in bg-white rounded-2xl shadow-soft p-8 border border-gray-100">
                    <div class="mb-8">
                        <h1 class="text-3xl font-black text-deepBlue">Welcome Back</h1>
                        <p class="text-gray-600 text-sm mt-2">Sign in to your QuickFix account</p>
                    </div>

                    <form method="POST" action="/login.php" class="space-y-5">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2" for="email">Email Address *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-input w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 placeholder-gray-400 transition"
                                placeholder="your@email.com"
                                value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                required
                                maxlength="255"
                            >
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-bold text-gray-700" for="password">Password *</label>
                                <a href="/forgot-password.php" class="text-xs text-brightGreen hover:text-green-700 font-bold">Forgot?</a>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 placeholder-gray-400 transition"
                                placeholder="Enter your password"
                                required
                                maxlength="255"
                            >
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="rounded border-gray-300 text-brightGreen focus:ring-brightGreen"
                            >
                            <label for="remember" class="text-sm text-gray-600">Remember me for 7 days</label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="w-full rounded-lg bg-deepBlue px-6 py-3 font-bold text-white transition hover:bg-darkBlue focus:outline-none focus:ring-2 focus:ring-brightGreen focus:ring-offset-2 mt-6"
                        >
                            Sign In
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="relative mt-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Or continue with</span>
                        </div>
                    </div>

                    <!-- Google Sign-In Button -->
                    <div class="mt-6">
                        <div id="g_id_onload"
                            data-client_id="<?php echo htmlspecialchars(GOOGLE_CLIENT_ID); ?>"
                            data-callback="handleCredentialResponse"
                            data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signin_with" data-shape="rectangular" data-logo_alignment="left" style="display: flex; justify-content: center;"></div>
                    </div>

                    <!-- Sign Up Link -->
                    <p class="text-center text-sm text-gray-600 mt-6">
                        Don't have an account?
                        <a href="/register.php" class="text-brightGreen hover:text-green-700 font-bold">Create one</a>
                    </p>
                </div>

                <!-- Additional Info -->
                <div class="mt-8 space-y-4">
                    <div class="rounded-lg bg-lightBlue p-4 text-center">
                        <p class="text-xs text-deepBlue">
                            <strong>Need help?</strong> Contact our support team at support@quickfix.ng
                        </p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 text-center border border-gray-200">
                        <p class="text-xs text-gray-600">
                            <strong>Demo Credentials:</strong> Use any email and password (min 6 chars) to test
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-darkBlue py-8 text-white text-center text-sm text-gray-400">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
    </footer>

    <script>
        // Handle Google Sign-In response
        function handleCredentialResponse(response) {
            // Show loading state
            const signinButton = document.querySelector('.g_id_signin');
            if (signinButton) {
                signinButton.style.opacity = '0.5';
                signinButton.style.pointerEvents = 'none';
            }

            // Send the JWT token to the backend
            const formData = new FormData();
            formData.append('credential', response.credential);

            fetch('/google-callback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.href = data.redirect || '/dashboard.php';
                    }, 1000);
                } else {
                    showAlert('error', data.message || 'Authentication failed');
                    if (signinButton) {
                        signinButton.style.opacity = '1';
                        signinButton.style.pointerEvents = 'auto';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred during authentication');
                if (signinButton) {
                    signinButton.style.opacity = '1';
                    signinButton.style.pointerEvents = 'auto';
                }
            });
        }

        // Helper function to show alerts
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert mb-6 rounded-lg border p-4 ' + (type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200');
            const icon = type === 'success' ? 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' : 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z';
            alertDiv.innerHTML = '<div class="flex items-center gap-3"><svg class="h-5 w-5 ' + (type === 'success' ? 'text-green-600' : 'text-red-600') + '" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="' + icon + '" clip-rule="evenodd" /></svg><p class="text-sm font-bold ' + (type === 'success' ? 'text-green-800' : 'text-red-800') + '">' + message + '</p></div>';
            const formContainer = document.querySelector('.fade-in');
            if (formContainer) {
                formContainer.insertBefore(alertDiv, formContainer.firstChild);
            }
        }

        // Password visibility toggle
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.createElement('button');
        togglePasswordBtn.type = 'button';
        togglePasswordBtn.className = 'absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700';
        togglePasswordBtn.innerHTML = '👁️';
        togglePasswordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        });

        // Accessibility: Dismiss on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>
