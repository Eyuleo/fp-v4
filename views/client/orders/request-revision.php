<?php
    require_once __DIR__ . '/../../../src/Services/FileService.php';
    $fileService = new FileService();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Revision - Student Skills Marketplace</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Request Revision</h1>
                <p class="text-gray-600 mt-2">Order #<?php echo $order['id'] ?> -<?php echo e($order['service_title']) ?></p>
            </div>

            <!-- Revision Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="font-medium text-blue-900 mb-1">Revision Request</h3>
                        <p class="text-sm text-blue-800">
                            You have used                                          <?php echo $order['revision_count'] ?> of<?php echo $order['max_revisions'] ?> available revisions.
                            <?php if ($order['revision_count'] < $order['max_revisions']): ?>
                                You have<?php echo $order['max_revisions'] - $order['revision_count'] ?> revision(s) remaining.
                            <?php else: ?>
                                This is your last revision.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Delivered Work -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Delivered Work</h2>

                <div class="mb-4">
                    <h3 class="font-medium text-gray-900 mb-2">Delivery Message:</h3>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($order['delivery_message']) ?></p>
                    </div>
                </div>

                <?php if (! empty($order['delivery_files'])): ?>
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">Delivery Files:</h3>
                        <ul class="space-y-2">
                            <?php foreach ($order['delivery_files'] as $file): ?>
                                <?php if (empty($file['path'])) {
                                        continue;
                                    }
                                ?>
                                <?php $signedUrl = $fileService->generateSignedUrl($file['path'], 1800); ?>
                                <li>
                                    <a href="<?php echo e($signedUrl) ?>"
                                       class="text-blue-600 hover:text-blue-800 flex items-center"
                                       download>
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
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

            <!-- Revision Request Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">What needs to be revised?</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo e($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="/orders/<?php echo $order['id'] ?>/request-revision" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?>">

                    <!-- Revision Reason -->
                    <div class="mb-6">
                        <label for="revision_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Revision Details <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="revision_reason"
                            name="revision_reason"
                            rows="6"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Please be specific about what needs to be changed or improved..."
                        ><?php echo isset($_POST['revision_reason']) ? e($_POST['revision_reason']) : '' ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">
                            Be clear and specific about what changes you need. This helps the student deliver exactly what you want.
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between">
                        <a href="/orders/<?php echo $order['id'] ?>" class="text-gray-600 hover:text-gray-800">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            class="bg-orange-600 text-white px-6 py-2 rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                        >
                            Request Revision
                        </button>
                    </div>
                </form>
            </div>

            <!-- Help Text -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-medium text-yellow-900 mb-2">Revision Guidelines:</h3>
                <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                    <li>Be specific about what needs to be changed</li>
                    <li>Provide examples or references if possible</li>
                    <li>Keep your feedback constructive and professional</li>
                    <li>Remember that revisions should be within the original scope of work</li>
                    <li>If you've reached the maximum revisions, you may need to open a dispute</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>