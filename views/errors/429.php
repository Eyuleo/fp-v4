<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 Too Many Requests - Student Skills Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <h1 class="text-9xl font-bold text-gray-300">429</h1>
                <h2 class="text-3xl font-bold text-gray-900 mt-4">Too Many Requests</h2>
                <p class="text-gray-600 mt-4">
                    You've made too many requests. Please wait a moment and try again.
                </p>
            </div>

            <div class="space-y-3">
                <button onclick="location.reload()" class="block w-full bg-gray-200 text-gray-800 py-3 px-6 rounded-lg hover:bg-gray-300 transition">
                    Try Again
                </button>
                <a href="/" class="block w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition">
                    Go to Homepage
                </a>
            </div>
        </div>
    </div>
</body>
</html>
