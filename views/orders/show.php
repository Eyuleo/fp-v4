<?php
    require_once __DIR__ . '/../../src/Helpers.php';
    require_once __DIR__ . '/../../src/Auth.php';

    // Set page title
    $title = 'Order #' . e($order['id']) . ' - Student Skills Marketplace';

    // Start output buffering for content
    ob_start();
?>

<div class="max-w-6xl mx-auto">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <?php echo e($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <?php echo e($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

        <!-- Order Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-900">Order #<?php echo e($order['id']) ?></h1>

                <?php
                    $statusColors = [
                        'pending'            => 'bg-yellow-100 text-yellow-800',
                        'in_progress'        => 'bg-blue-100 text-blue-800',
                        'delivered'          => 'bg-purple-100 text-purple-800',
                        'revision_requested' => 'bg-orange-100 text-orange-800',
                        'completed'          => 'bg-green-100 text-green-800',
                        'cancelled'          => 'bg-red-100 text-red-800',
                    ];
                ?>
                <span class="px-4 py-2 rounded-full text-sm font-medium<?php echo $statusColors[$order['status']] ?>">
                    <?php echo ucfirst(str_replace('_', ' ', e($order['status']))) ?>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Service</h3>
                    <p class="text-lg font-semibold text-gray-900"><?php echo e($order['service_title']) ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Price</h3>
                    <p class="text-lg font-semibold text-gray-900">$<?php echo number_format($order['price'], 2) ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Client</h3>
                    <p class="text-gray-900"><?php echo e($order['client_name'] ?? $order['client_email']) ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Student</h3>
                    <p class="text-gray-900"><?php echo e($order['student_name'] ?? $order['student_email']) ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Deadline</h3>
                    <p class="text-gray-900"><?php echo date('M d, Y H:i', strtotime($order['deadline'])) ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Created</h3>
                    <p class="text-gray-900"><?php echo date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Requirements -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Requirements</h2>
            <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($order['requirements']) ?></p>

            <?php if (! empty($order['requirement_files'])): ?>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Attached Files:</h3>
                    <div class="space-y-2">
                        <?php foreach ($order['requirement_files'] as $file): ?>
                            <div class="flex items-center space-x-2 text-sm text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                <span><?php echo e($file['original_name']) ?></span>
                                <span class="text-gray-400">(<?php echo number_format($file['size'] / 1024, 2) ?> KB)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Delivery (if delivered) -->
        <?php if ($order['status'] === 'delivered' || $order['status'] === 'completed'): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Delivery</h2>

                <?php if ($order['delivery_message']): ?>
                    <p class="text-gray-700 whitespace-pre-wrap mb-4"><?php echo e($order['delivery_message']) ?></p>
                <?php endif; ?>

                <?php if (! empty($order['delivery_files'])): ?>
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Delivered Files:</h3>
                        <div class="space-y-2">
                            <?php foreach ($order['delivery_files'] as $file): ?>
                                <div class="flex items-center space-x-2 text-sm text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                    <span><?php echo e($file['original_name']) ?></span>
                                    <span class="text-gray-400">(<?php echo number_format($file['size'] / 1024, 2) ?> KB)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <a href="/orders" class="text-gray-600 hover:text-gray-900">
                    ← Back to Orders
                </a>

                <div class="space-x-3">
                    <?php if ($order['status'] === 'pending' && $order['student_id'] === Auth::user()['id']): ?>
                        <form action="/orders/<?php echo e($order['id']) ?>/accept" method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                                Accept Order
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (($order['status'] === 'in_progress' || $order['status'] === 'revision_requested') && $order['student_id'] === Auth::user()['id']): ?>
                        <button onclick="showDeliverForm()" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                            Deliver Order
                        </button>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'delivered' && $order['client_id'] === Auth::user()['id']): ?>
                        <form action="/orders/<?php echo e($order['id']) ?>/complete" method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                                Accept & Complete
                            </button>
                        </form>

                        <?php if ($order['revision_count'] < $order['max_revisions']): ?>
                            <button onclick="showRevisionForm()" class="bg-orange-600 text-white px-6 py-2 rounded-md hover:bg-orange-700">
                                Request Revision (<?php echo e($order['max_revisions'] - $order['revision_count']) ?> left)
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'pending'): ?>
                        <button onclick="showCancelModal()" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700">
                            Cancel Order
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
</div>

<?php
    // Capture content
    $content = ob_get_clean();

    // Start capturing additional scripts
    ob_start();
?>

    <!-- Cancel Order Modal -->
    <div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-md w-full mx-4 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Cancel Order</h3>
            <form action="/orders/<?php echo e($order['id']) ?>/cancel" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">

                <div class="mb-4">
                    <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for cancellation (optional)
                    </label>
                    <textarea
                        id="cancellation_reason"
                        name="cancellation_reason"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Please provide a reason for cancelling this order..."
                    ></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="hideCancelModal()" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                        Keep Order
                    </button>
                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700">
                        Cancel Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deliver Order Modal -->
    <div id="deliverModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center overflow-y-auto">
        <div class="bg-white rounded-lg max-w-2xl w-full mx-4 my-8 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Deliver Order</h3>
            <form action="/orders/<?php echo e($order['id']) ?>/deliver" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">

                <div class="mb-4">
                    <label for="delivery_message" class="block text-sm font-medium text-gray-700 mb-2">
                        Delivery Message <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="delivery_message"
                        name="delivery_message"
                        rows="4"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Describe what you've delivered..."
                    ></textarea>
                </div>

                <div class="mb-4">
                    <label for="delivery_files" class="block text-sm font-medium text-gray-700 mb-2">
                        Delivery Files <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="file"
                        id="delivery_files"
                        name="delivery_files[]"
                        multiple
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-sm text-gray-500 mt-1">Maximum 25MB total</p>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="hideDeliverForm()" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                        Submit Delivery
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Request Revision Modal -->
    <div id="revisionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-2xl w-full mx-4 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Request Revision</h3>
            <form action="/orders/<?php echo e($order['id']) ?>/request-revision" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">

                <div class="mb-4">
                    <label for="revision_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        What needs to be revised? <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="revision_reason"
                        name="revision_reason"
                        rows="6"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                        placeholder="Please be specific about what changes you need..."
                    ></textarea>
                    <p class="text-sm text-gray-500 mt-1">
                        You have                                                                                                                                                                 <?php echo e($order['max_revisions'] - $order['revision_count']) ?> revision(s) remaining.
                    </p>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="hideRevisionForm()" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                        Cancel
                    </button>
                    <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-md hover:bg-orange-700">
                        Request Revision
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showCancelModal() {
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function hideCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }

        function showDeliverForm() {
            document.getElementById('deliverModal').classList.remove('hidden');
        }

        function hideDeliverForm() {
            document.getElementById('deliverModal').classList.add('hidden');
        }

        function showRevisionForm() {
            document.getElementById('revisionModal').classList.remove('hidden');
        }

        function hideRevisionForm() {
            document.getElementById('revisionModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) hideCancelModal();
        });

        document.getElementById('deliverModal').addEventListener('click', function(e) {
            if (e.target === this) hideDeliverForm();
        });

        document.getElementById('revisionModal').addEventListener('click', function(e) {
            if (e.target === this) hideRevisionForm();
        });
    </script>

<?php
    // Capture additional scripts
    $additionalScripts = ob_get_clean();

    // Include dashboard layout
    include __DIR__ . '/../layouts/dashboard.php';
?>
