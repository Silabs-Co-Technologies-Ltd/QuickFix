<?php
/**
 * QuickFix Dashboard
 * User control panel
 */

session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | QuickFix Nearby</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepBlue: '#003d82',
                        brightGreen: '#00b896',
                        lightBlue: '#e8f4f8',
                        darkBlue: '#001f41'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 bg-deepBlue rounded-lg flex items-center justify-center text-white font-bold">QF</div>
                <h1 class="text-xl font-bold text-deepBlue">QuickFix</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">Welcome, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                <a href="/logout.php" class="text-sm font-bold text-red-600 hover:text-red-800">Sign Out</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-12">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            <h2 class="text-2xl font-black text-deepBlue mb-4">User Dashboard</h2>
            <p class="text-gray-600 mb-8">You are logged in as a <strong><?php echo ucfirst($user_role); ?></strong>.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 rounded-xl bg-lightBlue border border-blue-100">
                    <h3 class="font-bold text-deepBlue mb-2">Profile Settings</h3>
                    <p class="text-sm text-gray-600">Manage your personal information and preferences.</p>
                </div>
                <div class="p-6 rounded-xl bg-green-50 border border-green-100">
                    <h3 class="font-bold text-green-800 mb-2">Service History</h3>
                    <p class="text-sm text-gray-600">View your past service requests and status.</p>
                </div>
                <div class="p-6 rounded-xl bg-gray-50 border border-gray-200">
                    <h3 class="font-bold text-gray-800 mb-2">Support</h3>
                    <p class="text-sm text-gray-600">Need help? Contact our 24/7 support team.</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
