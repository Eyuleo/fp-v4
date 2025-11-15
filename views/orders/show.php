<?php
    require_once __DIR__ . '/../../src/Helpers.php';
    require_once __DIR__ . '/../../src/Auth.php';

    // Set page title
    $title = 'Order #' . e($order['id']) . ' - Student Skills Marketplace';

    // Initialize FileService for signed URLs
    require_once __DIR__ . '/../../src/Services/FileService.php';
    $fileService = new FileService();

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
                <span class="px-4 py-2 rounded-full text-sm font-medium<?php echo $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                    <?php echo ucfirst(str_replace('_', ' ', e($order['status']))) ?>
                </span>
            </div>

            <?php if ($order['status'] === 'revision_requested'): ?>
                <div class="mb-4 p-4 border border-orange-200 bg-orange-50 rounded">
                    <div class="font-medium text-orange-900">Revision requested</div>
                    <div class="text-sm text-orange-800 mt-1">
                        The client has requested a revision. Previously delivered files remain visible below.
                        Please review the messages for the revision details.
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($order['status'] === 'in_progress' && time() > strtotime($order['deadline'])): ?>
                <div class="mb-4 p-4 border border-red-200 bg-red-50 rounded">
                    <div class="font-medium text-red-900">Delivery deadline passed</div>
                    <div class="text-sm text-red-800 mt-1">
                        The delivery deadline for this order has passed. Please contact support for assistance.
                    </div>
                </div>
            <?php endif; ?>

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
                            <?php if (empty($file['path'])) {
                                    continue;
                                }
                            ?>
                            <?php $signedUrl = $fileService->generateSignedUrl($file['path'], 3600); ?>
                            <a href="<?php echo e($signedUrl) ?>" target="_blank" class="flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="font-medium"><?php echo e($file['original_name']) ?></span>
                                <span class="text-gray-400">(<?php echo number_format(($file['size'] ?? 0) / 1024, 2) ?> KB)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Delivery (show for delivered, revision_requested, completed) -->
        <?php if (in_array($order['status'], ['delivered', 'revision_requested', 'completed'])): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Delivery</h2>

                <?php if (! empty($order['delivery_message'])): ?>
                    <p class="text-gray-700 whitespace-pre-wrap mb-4"><?php echo e($order['delivery_message']) ?></p>
                <?php endif; ?>

                <?php if (! empty($order['delivery_files'])): ?>
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Delivered Files:</h3>
                        <div class="space-y-2">
                            <?php foreach ($order['delivery_files'] as $file): ?>
                                <?php if (empty($file['path'])) {
                                        continue;
                                    }
                                ?>
                                <?php $signedUrl = $fileService->generateSignedUrl($file['path'], 3600); ?>
                                <a href="<?php echo e($signedUrl) ?>" target="_blank" class="flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="font-medium"><?php echo e($file['original_name']) ?></span>
                                    <span class="text-gray-400">(<?php echo number_format(($file['size'] ?? 0) / 1024, 2) ?> KB)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500">No delivered files available.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Review (if completed and reviewed) -->
        <?php if ($order['status'] === 'completed' && ! empty($review)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Review</h2>

                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div class="flex items-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-5 h-5<?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.7 1.8-1.59 1.118L10 13.347l-2.49 1.618c-.89.683-1.89-.197-1.59-1.118l1.07-3.292a1 1 0 00-.364-1.118L3.827 8.72c-.783-.57-.38-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                            <span class="ml-2 text-sm font-medium text-gray-700"><?php echo e($review['rating']) ?>/5</span>
                        </div>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-900"><?php echo e($review['client_name']) ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($review['created_at'])) ?></p>
                        </div>

                        <?php if (! empty($review['comment'])): ?>
                            <p class="text-gray-700 mb-3"><?php echo e($review['comment']) ?></p>
                        <?php endif; ?>

                        <?php if (! empty($review['student_reply'])): ?>
                            <div class="mt-4 pl-4 border-l-2 border-gray-200">
                                <p class="text-sm font-medium text-gray-900 mb-1">Student Reply:</p>
                                <p class="text-gray-700"><?php echo e($review['student_reply']) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y', strtotime($review['student_replied_at'])) ?></p>
                            </div>
                        <?php elseif ($order['student_id'] === Auth::user()['id']): ?>
                            <button onclick="showReplyForm()" class="mt-3 text-sm text-blue-600 hover:text-blue-700">
                                Reply to review
                            </button>
                        <?php endif; ?>

                        <?php
                            $canEditUntil = strtotime($review['can_edit_until']);
                            $now          = time();
                        ?>
                        <?php if ($order['client_id'] === Auth::user()['id'] && $now <= $canEditUntil): ?>
                            <a href="/reviews/<?php echo e($review['id']) ?>/edit" class="mt-3 inline-block text-sm text-blue-600 hover:text-blue-700">
                                Edit review (<?php echo ceil(($canEditUntil - $now) / 3600) ?> hours left)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/orders" class="text-gray-600 hover:text-gray-900">
                        ← Back to Orders
                    </a>
                    <a href="/messages/thread/<?php echo e($order['id']) ?>" class="flex items-center space-x-2 text-blue-600 hover:text-blue-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4"/>
                        </svg>
                        <span>Messages</span>
                    </a>
                </div>

                <div class="space-x-3">
                    <?php if (
                        (in_array($order['status'], ['in_progress', 'revision_requested'])) &&
                        $order['student_id'] === Auth::user()['id'] &&
                        time() <= strtotime($order['deadline'])
                    ): ?>
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

                        <?php if (($order['revision_count'] ?? 0) < ($order['max_revisions'] ?? 0)): ?>
                            <button onclick="showRevisionForm()" class="bg-orange-600 text-white px-6 py-2 rounded-md hover:bg-orange-700">
                                Request Revision (<?php echo e(($order['max_revisions'] ?? 0) - ($order['revision_count'] ?? 0)) ?> left)
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'completed' && $order['client_id'] === Auth::user()['id'] && empty($review)): ?>
                        <a href="/reviews/create?order_id=<?php echo e($order['id']) ?>" class="inline-block bg-yellow-600 text-white px-6 py-2 rounded-md hover:bg-yellow-700">
                            Leave Review
                        </a>
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
                        You have                                 <?php echo e(($order['max_revisions'] ?? 0) - ($order['revision_count'] ?? 0)); ?> revision(s) left.
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

    <!-- Reply to Review Modal -->
    <?php if (! empty($review) && empty($review['student_reply']) && $order['student_id'] === Auth::user()['id']): ?>
    <div id="replyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-2xl w-full mx-4 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Reply to Review</h3>
            <form action="/reviews/<?php echo e($review['id']) ?>/reply" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">

                <div class="mb-4">
                    <label for="student_reply" class="block text-sm font-medium text-gray-700 mb-2">
                        Your Reply <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="student_reply"
                        name="student_reply"
                        rows="4"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Thank the client or respond to their feedback..."
                    ></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="hideReplyForm()" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                        Submit Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
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

        function showReplyForm() {
            document.getElementById('replyModal').classList.remove('hidden');
        }

        function hideReplyForm() {
            document.getElementById('replyModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        ['deliverModal', 'revisionModal'<?php if (! empty($review) && empty($review['student_reply']) && $order['student_id'] === Auth::user()['id']): ?>, 'replyModal'<?php endif; ?>]
        .forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('click', function(e) {
                if (e.target !== this) return;
                if (id === 'deliverModal') hideDeliverForm();
                if (id === 'revisionModal') hideRevisionForm();
                if (id === 'replyModal') hideReplyForm();
            });
        });
    </script>

<?php
    // Capture additional scripts
    $additionalScripts = ob_get_clean();

    // Include dashboard layout
    include __DIR__ . '/../layouts/dashboard.php';
?>
