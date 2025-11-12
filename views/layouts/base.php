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

    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }
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

    <?php echo $additionalScripts ?? '' ?>
</body>
</html>
