<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Student Skills Marketplace'?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* Button loading states */
        .btn.is-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn.is-loading .btn-text {
            visibility: hidden;
        }
        
        .btn.is-loading .btn-spinner {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-spinner {
            display: none;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .fa-spin-custom {
            animation: spin 1s linear infinite;
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
    
    <!-- Form button enhancement -->
    <script src="/js/ui/buttons.js"></script>
</body>
</html>
