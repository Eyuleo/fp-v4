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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }
        
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

    <!-- Form button enhancement -->
    <script src="/js/ui/buttons.js"></script>

    <?php echo $additionalScripts ?? '' ?>
</body>
</html>
