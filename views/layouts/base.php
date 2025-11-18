<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $title ?? 'Student Skills Marketplace' ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }
        
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

    <?php echo $additionalHead ?? '' ?>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Navigation -->
    <?php require __DIR__ . '/../partials/navigation.php'; ?>

    <?php if (isset($showAlert) && $showAlert): ?>
        <?php require __DIR__ . '/../partials/alert.php'; ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-grow pt-16">
        <?php echo $content ?? '' ?>
    </main>

    <!-- Footer -->
    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <!-- Form Loading Script -->
    <script src="/js/form-loading.js"></script>
    
    <?php echo $additionalScripts ?? '' ?>
</body>
</html>
