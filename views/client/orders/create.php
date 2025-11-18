<?php
    require_once __DIR__ . '/../../../src/Helpers.php';

    // Set page title
    $title = 'Place Order - Student Skills Marketplace';

    // Start output buffering for content
    ob_start();
?>

<div class="max-w-4xl mx-auto">
        <!-- Service Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Order Summary</h2>

            <div class="flex items-start space-x-4">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold text-gray-900"><?php echo e($service['title']) ?></h3>
                    <p class="text-gray-600 mt-2"><?php echo e($service['description']) ?></p>

                    <div class="mt-4 flex items-center space-x-4">
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span><?php echo e($service['delivery_days']) ?> days delivery</span>
                        </div>

                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-1 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <span><?php echo safe_number_format($service['average_rating'] ?? 0, 1) ?> (<?php echo e($service['total_reviews'] ?? 0) ?> reviews)</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <span class="text-sm text-gray-600">By:</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo e($service['student_name'] ?? explode('@', $service['student_email'] ?? '')[0]) ?></span>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-3xl font-bold text-gray-900">$<?php echo safe_number_format($service['price'], 2) ?></div>
                </div>
            </div>
        </div>

        <!-- Order Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Order Requirements</h2>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?php echo e($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="/orders/store" method="POST" enctype="multipart/form-data" id="order-form">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="service_id" value="<?php echo e($service['id']) ?>">

                <!-- Requirements -->
                <div class="mb-6">
                    <label for="requirements" class="block text-sm font-medium text-gray-700 mb-2">
                        Describe your requirements *
                    </label>
                    <textarea
                        id="requirements"
                        name="requirements"
                        rows="6"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Please provide detailed requirements for your order (minimum 10 characters)..."
                    ><?php echo e(old('requirements')) ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">Minimum 10 characters</p>
                </div>

                <!-- File Upload -->
                <div class="mb-6">
                    <label for="requirement_files" class="block text-sm font-medium text-gray-700 mb-2">
                        Attach files (optional)
                    </label>
                    <input
                        type="file"
                        id="requirement_files"
                        name="requirement_files[]"
                        multiple
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip,.txt"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="mt-1 text-sm text-gray-500">
                        Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX, ZIP, TXT<br>
                        Maximum 10MB per file, 25MB total
                    </p>

                    <!-- File preview -->
                    <div id="file-preview" class="mt-3 space-y-2"></div>
                </div>

                <!-- Order Summary -->
                <div class="border-t border-gray-200 pt-6 mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600">Service Price:</span>
                        <span class="text-gray-900 font-medium">$<?php echo safe_number_format($service['price'], 2) ?></span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600">Delivery Time:</span>
                        <span class="text-gray-900 font-medium"><?php echo e($service['delivery_days']) ?> days</span>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <span class="text-lg font-semibold text-gray-900">Total:</span>
                        <span class="text-2xl font-bold text-gray-900">$<?php echo safe_number_format($service['price'], 2) ?></span>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between">
                    <a href="/services/<?php echo e($service['id']) ?>" class="text-gray-600 hover:text-gray-900">
                        ← Back to Service
                    </a>
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-8 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium"
                    >
                        Proceed to Payment
                    </button>
                </div>
            </form>
        </div>
</div>

<?php
    // Capture content
    $content = ob_get_clean();

    // Additional scripts
    ob_start();
?>
<script>
    // File upload preview and validation
    document.getElementById('requirement_files').addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        const preview = document.getElementById('file-preview');
        preview.innerHTML = '';

        let totalSize = 0;

        files.forEach(file => {
            totalSize += file.size;

            const fileDiv = document.createElement('div');
            fileDiv.className = 'flex items-center justify-between bg-gray-50 px-3 py-2 rounded';
            fileDiv.innerHTML = `
                <span class="text-sm text-gray-700">${file.name}</span>
                <span class="text-sm text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
            `;
            preview.appendChild(fileDiv);
        });

        // Show warning if total size exceeds limit
        if (totalSize > 25 * 1024 * 1024) {
            const warning = document.createElement('div');
            warning.className = 'text-sm text-red-600 font-medium';
            warning.textContent = 'Warning: Total file size exceeds 25MB limit';
            preview.appendChild(warning);
        }
    });

    // Form validation
    document.getElementById('order-form').addEventListener('submit', function(e) {
        const requirements = document.getElementById('requirements').value.trim();

        if (requirements.length < 10) {
            e.preventDefault();
            alert('Requirements must be at least 10 characters');
            return false;
        }

        const files = document.getElementById('requirement_files').files;
        let totalSize = 0;

        for (let file of files) {
            totalSize += file.size;

            if (file.size > 10 * 1024 * 1024) {
                e.preventDefault();
                alert('Each file must not exceed 10MB');
                return false;
            }
        }

        if (totalSize > 25 * 1024 * 1024) {
            e.preventDefault();
            alert('Total file size must not exceed 25MB');
            return false;
        }
    });
</script>
<?php
    $additionalScripts = ob_get_clean();

    // Include dashboard layout
    include __DIR__ . '/../../layouts/dashboard.php';
?>
