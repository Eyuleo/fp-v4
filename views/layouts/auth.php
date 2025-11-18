<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Student Skills Marketplace'?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Custom styles -->
    <style>
        /* Form Loading States */
        .btn-loading {
            position: relative;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .btn-loading .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Disabled button states */
        button:disabled,
        button[disabled] {
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Student Skills Marketplace</h1>
                <p class="mt-2 text-sm text-gray-600">Connect. Collaborate. Create.</p>
            </div>

            <!-- Main Content -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <?php if (isset($_SESSION['alert'])): ?>
                    <div class="mb-6 p-4 rounded-md <?php echo $_SESSION['alert']['type'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'?>">
                        <?php echo e($_SESSION['alert']['message'])?>
                    </div>
                    <?php unset($_SESSION['alert']); ?>
                <?php endif; ?>

                <?php echo $content?>
            </div>

            <!-- Footer Links -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <a href="/" class="hover:text-gray-900">Back to Home</a>
            </div>
        </div>
    </div>
    
    <!-- Form Loading Script -->
    <script src="/js/form-loading.js"></script>
</body>
</html>
