<?php
    require_once __DIR__ . '/../../../src/Helpers.php';
    require_once __DIR__ . '/../../../src/Auth.php';
    require_once __DIR__ . '/../../../src/Services/FileService.php';

    $title = 'Dispute #' . e($dispute['id']) . ' - Admin';
    $fileService = new FileService();
    $isAdmin = Auth::user()['role'] === 'admin';

    ob_start();
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="/admin/disputes" class="text-blue-600 hover:text-blue-800">← Back to Disputes</a>
    </div>

    <!-- Dispute Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-gray-900">Dispute #<?php echo e($dispute['id']) ?></h1>
            <?php
                $statusColors = [
                    'open' => 'bg-yellow-100 text-yellow-800',
                    'resolved' => 'bg-green-100 text-green-800',
                ];
                $statusClass = $statusColors[$dispute['status']] ?? 'bg-gray-100 text-gray-800';
            ?>
            <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $statusClass ?>">
                <?php echo ucfirst(e($dispute['status'])) ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Order ID</h3>
                <a href="/orders/<?php echo e($dispute['order_id']) ?>" class="text-lg font-semibold text-blue-600 hover:text-blue-800">
                    #<?php echo e($dispute['order_id']) ?>
                </a>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Opened By</h3>
                <p class="text-lg font-semibold text-gray-900">
                    <?php echo e($dispute['opener_name'] ?? explode('@', $dispute['opener_email'] ?? '')[0]) ?>
                    <span class="text-sm font-normal text-gray-500">
                        (<?php echo $dispute['opened_by'] == $dispute['client_id'] ? 'Client' : 'Student' ?>)
                    </span>
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Created</h3>
                <p class="text-lg font-semibold text-gray-900"><?php echo date('M d, Y H:i', strtotime($dispute['created_at'])) ?></p>
            </div>
        </div>

        <!-- Dispute Reason -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Dispute Reason</h3>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-gray-900 whitespace-pre-wrap"><?php echo e($dispute['reason']) ?></p>
            </div>
        </div>

        <?php if ($dispute['status'] === 'resolved'): ?>
            <!-- Resolution Details -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Resolution</h3>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Resolution Type:</span>
                            <span class="ml-2 text-sm text-gray-900 font-semibold">
                                <?php echo ucfirst(str_replace('_', ' ', e($dispute['resolution']))) ?>
                            </span>
                        </div>
                        <?php if ($dispute['resolution'] === 'partial_refund' && !empty($dispute['refund_percentage'])): ?>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Refund Percentage:</span>
                                <span class="ml-2 text-sm text-gray-900 font-semibold">
                                    <?php echo safe_number_format($dispute['refund_percentage'], 2) ?>%
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($dispute['resolution_notes'])): ?>
                        <div class="mb-3">
                            <div class="text-sm font-medium text-gray-700 mb-1">Resolution Notes:</div>
                            <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo e($dispute['resolution_notes']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($dispute['admin_notes'])): ?>
                        <div>
                            <div class="text-sm font-medium text-gray-700 mb-1">Admin Notes:</div>
                            <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo e($dispute['admin_notes']) ?></p>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3 text-xs text-gray-600">
                        Resolved by <?php echo e($dispute['resolver_name'] ?? 'Admin') ?> on <?php echo date('M d, Y H:i', strtotime($dispute['resolved_at'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Details -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Order Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Service</h3>
                <p class="text-lg font-semibold text-gray-900"><?php echo e($order['service_title']) ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Price</h3>
                <p class="text-lg font-semibold text-gray-900">$<?php echo safe_number_format($order['price'], 2) ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Client</h3>
                <p class="text-gray-900"><?php echo e($order['client_name'] ?? explode('@', $order['client_email'] ?? '')[0]) ?></p>
                <p class="text-sm text-gray-500"><?php echo e($order['client_email']) ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Student</h3>
                <p class="text-gray-900"><?php echo e($order['student_name'] ?? explode('@', $order['student_email'] ?? '')[0]) ?></p>
                <p class="text-sm text-gray-500"><?php echo e($order['student_email']) ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Order Status</h3>
                <p class="text-gray-900"><?php echo ucfirst(str_replace('_', ' ', e($order['status']))) ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Deadline</h3>
                <p class="text-gray-900"><?php echo date('M d, Y H:i', strtotime($order['deadline'])) ?></p>
            </div>
        </div>

        <!-- Original Requirements -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Original Requirements</h3>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-gray-900 whitespace-pre-wrap"><?php echo e($order['requirements']) ?></p>
            </div>
            <?php if (!empty($order['requirement_files'])): ?>
                <div class="mt-3">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Attached Files:</h4>
                    <div class="space-y-2">
                        <?php foreach ($order['requirement_files'] as $file): ?>
                            <?php if (empty($file['path'])) continue; ?>
                            <?php $signedUrl = $fileService->generateSignedUrl($file['path'], 3600); ?>
                            <a href="<?php echo e($signedUrl) ?>" target="_blank"
                               class="flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="font-medium"><?php echo e($file['original_name']) ?></span>
                                <span class="text-gray-400">(<?php echo safe_number_format(($file['size'] ?? 0) / 1024, 2) ?> KB)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delivery History -->
    <?php if (!empty($deliveryHistory) && count($deliveryHistory) > 0): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                Delivery History
                <span class="text-sm font-normal text-gray-500">(<?php echo count($deliveryHistory) ?> deliveries)</span>
            </h2>
            <div class="space-y-4">
                <?php foreach ($deliveryHistory as $delivery): ?>
                    <div class="border <?php echo $delivery['is_current'] ? 'border-purple-300 bg-purple-50' : 'border-gray-200 bg-gray-50' ?> rounded-lg p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <?php if ($delivery['is_current']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-600 text-white">
                                        Current
                                    </span>
                                <?php endif; ?>
                                <span class="text-sm font-semibold text-gray-900">
                                    Delivery #<?php echo e($delivery['delivery_number']) ?>
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo date('M d, Y H:i', strtotime($delivery['delivered_at'])) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($delivery['delivery_message'])): ?>
                            <div class="bg-white border <?php echo $delivery['is_current'] ? 'border-purple-200' : 'border-gray-200' ?> rounded p-3 mb-3">
                                <div class="text-xs font-medium text-gray-600 mb-1">Message:</div>
                                <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo e($delivery['delivery_message']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($delivery['delivery_files'])): ?>
                            <div>
                                <h3 class="text-xs font-medium text-gray-700 mb-2">Delivered Files:</h3>
                                <div class="space-y-1">
                                    <?php foreach ($delivery['delivery_files'] as $file): ?>
                                        <?php if (empty($file['path'])) continue; ?>
                                        <?php $signedUrl = $fileService->generateSignedUrl($file['path'], 3600); ?>
                                        <a href="<?php echo e($signedUrl) ?>" target="_blank"
                                           class="flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span class="font-medium"><?php echo e($file['original_name']) ?></span>
                                            <span class="text-gray-400">(<?php echo safe_number_format(($file['size'] ?? 0) / 1024, 2) ?> KB)</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Revision History -->
    <?php if (!empty($revisionHistory) && count($revisionHistory) > 0): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Revision History</h2>
            <div class="space-y-4">
                <?php foreach ($revisionHistory as $revision): ?>
                    <div class="border <?php echo $revision['is_current'] ? 'border-orange-300 bg-orange-50' : 'border-gray-200 bg-gray-50' ?> rounded-lg p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <?php if ($revision['is_current']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-600 text-white">
                                        Current
                                    </span>
                                <?php endif; ?>
                                <span class="text-sm font-semibold text-gray-900">
                                    Revision #<?php echo e($revision['revision_number']) ?>
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo date('M d, Y H:i', strtotime($revision['requested_at'])) ?>
                            </div>
                        </div>
                        <div class="text-sm text-gray-700 mb-2">
                            <span class="font-medium">Requested by:</span> 
                            <?php echo e($revision['requester_name'] ?? explode('@', $revision['requester_email'] ?? '')[0]) ?>
                        </div>
                        <div class="bg-white border <?php echo $revision['is_current'] ? 'border-orange-200' : 'border-gray-200' ?> rounded p-3">
                            <div class="text-xs font-medium text-gray-600 mb-1">Reason:</div>
                            <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo e($revision['revision_reason']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Message Thread -->
    <?php if (!empty($messages)): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Message Thread</h2>
            <div class="space-y-4">
                <?php foreach ($messages as $message): ?>
                    <div class="border border-gray-200 rounded-lg p-4 <?php echo $message['is_flagged'] ? 'bg-red-50 border-red-300' : 'bg-gray-50' ?>">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <span class="font-semibold text-gray-900">
                                    <?php echo e($message['sender_name'] ?? explode('@', $message['sender_email'] ?? '')[0]) ?>
                                </span>
                                <span class="text-sm text-gray-500 ml-2">
                                    <?php echo date('M d, Y H:i', strtotime($message['created_at'])) ?>
                                </span>
                            </div>
                            <?php if ($message['is_flagged']): ?>
                                <span class="px-2 py-1 bg-red-600 text-white text-xs font-semibold rounded">
                                    FLAGGED
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-900 whitespace-pre-wrap"><?php echo e($message['content']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Resolution Form (Admin Only) -->
    <?php if ($isAdmin && $dispute['status'] === 'open'): ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Resolve Dispute</h2>
            
            <form action="/admin/disputes/<?php echo e($dispute['id']) ?>/resolve" method="POST" id="resolution-form" data-loading>
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">

                <!-- Resolution Type -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Resolution Type *
                    </label>
                    <div class="space-y-3">
                        <label class="flex items-start p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input
                                type="radio"
                                name="resolution"
                                value="release_to_student"
                                required
                                class="mt-1 mr-3"
                                onchange="toggleRefundPercentage()"
                            >
                            <div>
                                <div class="font-medium text-gray-900">Release to Student</div>
                                <div class="text-sm text-gray-600">Release full payment to the student. Order will be marked as completed.</div>
                            </div>
                        </label>

                        <label class="flex items-start p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input
                                type="radio"
                                name="resolution"
                                value="refund_to_client"
                                required
                                class="mt-1 mr-3"
                                onchange="toggleRefundPercentage()"
                            >
                            <div>
                                <div class="font-medium text-gray-900">Refund to Client</div>
                                <div class="text-sm text-gray-600">Issue full refund to the client. Order will be marked as cancelled.</div>
                            </div>
                        </label>

                        <label class="flex items-start p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input
                                type="radio"
                                name="resolution"
                                value="partial_refund"
                                required
                                class="mt-1 mr-3"
                                onchange="toggleRefundPercentage()"
                            >
                            <div>
                                <div class="font-medium text-gray-900">Partial Refund</div>
                                <div class="text-sm text-gray-600">Split payment between client (refund) and student. Order will be marked as completed.</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Refund Percentage (shown only for partial_refund) -->
                <div id="refund-percentage-container" class="mb-6 hidden">
                    <label for="refund_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                        Refund Percentage (0-100) *
                    </label>
                    <div class="flex items-center space-x-4">
                        <input
                            type="number"
                            id="refund_percentage"
                            name="refund_percentage"
                            min="0"
                            max="100"
                            step="0.01"
                            class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="50.00"
                        >
                        <span class="text-sm text-gray-600">% of $<?php echo safe_number_format($order['price'], 2) ?> will be refunded to client</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        The remaining percentage will be paid to the student.
                    </p>
                </div>

                <!-- Resolution Notes -->
                <div class="mb-6">
                    <label for="resolution_notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Resolution Notes (visible to all parties) *
                    </label>
                    <textarea
                        id="resolution_notes"
                        name="resolution_notes"
                        rows="4"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Explain your decision and reasoning. This will be sent to both the client and student..."
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500">
                        Provide a clear explanation of your decision. Both parties will receive this in their notification.
                    </p>
                </div>

                <!-- Admin Notes (Internal) -->
                <div class="mb-6">
                    <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Admin Notes (internal only)
                    </label>
                    <textarea
                        id="admin_notes"
                        name="admin_notes"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Internal notes for admin reference (not visible to users)..."
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500">
                        Optional internal notes for record keeping. Not visible to users.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="/admin/disputes" class="px-6 py-2 text-gray-700 hover:text-gray-900">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-8 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium"
                    >
                        Resolve Dispute
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
    $content = ob_get_clean();
    ob_start();
?>

<script>
    function toggleRefundPercentage() {
        const partialRefundRadio = document.querySelector('input[name="resolution"][value="partial_refund"]');
        const refundPercentageContainer = document.getElementById('refund-percentage-container');
        const refundPercentageInput = document.getElementById('refund_percentage');
        
        if (partialRefundRadio && partialRefundRadio.checked) {
            refundPercentageContainer.classList.remove('hidden');
            refundPercentageInput.required = true;
        } else {
            refundPercentageContainer.classList.add('hidden');
            refundPercentageInput.required = false;
            refundPercentageInput.value = '';
        }
    }

    // Form validation
    document.getElementById('resolution-form')?.addEventListener('submit', function(e) {
        const resolution = document.querySelector('input[name="resolution"]:checked');
        
        if (!resolution) {
            e.preventDefault();
            alert('Please select a resolution type');
            return false;
        }

        if (resolution.value === 'partial_refund') {
            const refundPercentage = parseFloat(document.getElementById('refund_percentage').value);
            
            if (isNaN(refundPercentage) || refundPercentage < 0 || refundPercentage > 100) {
                e.preventDefault();
                alert('Refund percentage must be between 0 and 100');
                return false;
            }
        }

        const resolutionNotes = document.getElementById('resolution_notes').value.trim();
        
        if (resolutionNotes.length < 10) {
            e.preventDefault();
            alert('Resolution notes must be at least 10 characters');
            return false;
        }

        return confirm('Are you sure you want to resolve this dispute? This action cannot be undone.');
    });
</script>

<?php
    $additionalScripts = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
