<?php
    require_once __DIR__ . '/../../../src/Services/FileService.php';
    $fileService = new FileService();

    // Pre-sign requirement files
    $signedRequirementFiles = [];
    if (! empty($order['requirement_files']) && is_array($order['requirement_files'])) {
        foreach ($order['requirement_files'] as $f) {
            if (! empty($f['path'])) {
                $f['signed_url']          = $fileService->generateSignedUrl($f['path'], 1800);
                $signedRequirementFiles[] = $f;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliver Order - Student Skills Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-3xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-6">
                <a href="/orders/<?php echo $order['id'] ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    ← Back to Order
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Deliver Order</h1>
                <p class="text-gray-600 mt-2">Order #<?php echo $order['id'] ?> -<?php echo e($order['service_title']) ?></p>
            </div>

            <!-- Order Requirements -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Requirements</h2>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($order['requirements']) ?></p>
                </div>

                <?php if (! empty($signedRequirementFiles)): ?>
                    <div class="mt-4">
                        <h3 class="font-medium text-gray-900 mb-2">Requirement Files:</h3>
                        <ul class="space-y-2">
                            <?php foreach ($signedRequirementFiles as $file): ?>
                                <li>
                                    <a href="<?php echo e($file['signed_url']) ?>"
                                       class="text-blue-600 hover:text-blue-800 flex items-center"
                                       download>
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <?php echo e($file['original_name']) ?>
                                        <span class="text-gray-500 text-sm ml-2">(<?php echo number_format(($file['size'] ?? 0) / 1024, 2) ?> KB)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Delivery Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Submit Your Work</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo e($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="/orders/<?php echo $order['id'] ?>/deliver" method="POST" enctype="multipart/form-data" data-loading>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?>">

                    <!-- Delivery Message -->
                    <div class="mb-6">
                        <label for="delivery_message" class="block text-sm font-medium text-gray-700 mb-2">
                            Delivery Message <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="delivery_message"
                            name="delivery_message"
                            rows="6"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Describe what you've delivered and any important notes for the client..."
                        ><?php echo isset($_POST['delivery_message']) ? e($_POST['delivery_message']) : '' ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">
                            Explain what you've completed and provide any instructions for the client.
                        </p>
                    </div>

                    <!-- Delivery Files -->
                    <div class="mb-6">
                        <label for="delivery_files" class="block text-sm font-medium text-gray-700 mb-2">
                            Delivery Files <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="file"
                            id="delivery_files"
                            name="delivery_files[]"
                            multiple
                            required
                            accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip,.txt,.psd,.ai,.fig"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <p class="text-sm text-gray-500 mt-1">
                            Upload your completed work files. Maximum 25MB total. Accepted formats: images, PDFs, documents, archives.
                        </p>
                    </div>

                    <!-- File Preview -->
                    <div id="file-preview" class="mb-6 hidden">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h3>
                        <ul id="file-list" class="space-y-1 text-sm text-gray-600"></ul>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between">
                        <a href="/orders/<?php echo $order['id'] ?>" class="text-gray-600 hover:text-gray-800">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Submit Delivery
                        </button>
                    </div>
                </form>
            </div>

            <!-- Help Text -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-medium text-blue-900 mb-2">Delivery Tips:</h3>
                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                    <li>Make sure all deliverables match the order requirements</li>
                    <li>Include source files if applicable</li>
                    <li>Provide clear instructions on how to use/access your work</li>
                    <li>Double-check file quality before submitting</li>
                    <li>The client can request revisions if needed (up to                                                                          <?php echo $order['max_revisions'] ?> times)</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // File preview functionality
        document.getElementById('delivery_files').addEventListener('change', function(e) {
            const files = e.target.files;
            const fileList = document.getElementById('file-list');
            const filePreview = document.getElementById('file-preview');

            if (files.length > 0) {
                filePreview.classList.remove('hidden');
                fileList.innerHTML = '';

                let totalSize = 0;
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    totalSize += file.size;

                    const li = document.createElement('li');
                    li.textContent = `${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                    fileList.appendChild(li);
                }

                const maxSize = 25 * 1024 * 1024; // 25MB
                if (totalSize > maxSize) {
                    const warning = document.createElement('li');
                    warning.className = 'text-red-600 font-medium';
                    warning.textContent = `⚠ Total size exceeds 25MB limit (${(totalSize / 1024 / 1024).toFixed(2)} MB)`;
                    fileList.appendChild(warning);
                }
            } else {
                filePreview.classList.add('hidden');
            }
        });
    </script>
</body>
</html>