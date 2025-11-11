<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Skills Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Student Skills Marketplace - Admin</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">Admin: <?php echo e($_SESSION['user_email'])?></span>
                        <a href="/auth/logout" class="text-blue-600 hover:text-blue-700">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Admin Dashboard</h2>
                    <p class="text-gray-600">Welcome to the admin dashboard! This is where you'll manage users, services, and disputes.</p>
                    <p class="text-gray-600 mt-2">Dashboard functionality will be implemented in later tasks.</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
