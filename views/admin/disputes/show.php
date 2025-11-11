<?php
    $pageTitle = 'Dispute #' . $dispute['id'];
    $user      = Auth::user();
?>

<?php include __DIR__ . '/../../layouts/admin.php'; ?>

<?php ob_start(); ?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="/admin/disputes" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Back to Disputes
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Dispute #<?php echo e($dispute['id'])?></h1>
            <p class="mt-2 text-gray-600">Order #<?php echo e($dispute['order_id'])?> - <?php echo e($dispute['service_title'])?></p>
        </div>
        <div>
            <?php
                $statusColors = [
                    'open'     => 'bg-yellow-100 text-yellow-800',
                    'resolved' => 'bg-green-100 text-green-800',
                ];
            ?>
            <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $statusColors[$dispute['status']] ?? 'bg-gray-100 text-gray-800'?>">
                <?php echo e(ucfirst($dispute['status']))?>
            </span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Dispute Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Dispute Details</h2>

            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600">Opened By</p>
                    <p class="font-medium"><?php echo e($dispute['opened_by_name'] ?: $dispute['opened_by_email'])?></p>
                    <p class="text-sm text-gray-500">
                        <?php echo date('F d, Y \a\t g:i A', strtotime($dispute['created_at']))?>
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-2">Reason for Dispute</p>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-gray-900 whitespace-pre-wrap"><?php echo e($dispute['reason'])?></p>
                    </div>
                </div>

                <?php if ($dispute['status'] === 'resolved'): ?>
                    <div class="border-t pt-4">
                        <p class="text-sm text-gray-600 mb-2">Resolution</p>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="font-medium text-green-900 mb-2">
                                <?php
                                    $resolutionLabels = [
                                        'release_to_student' => 'Payment Released to Student',
                                        'refund_to_client'   => 'Full Refund to Client',
                                        'partial_refund'     => 'Partial Refund',
                                    ];
                                    echo e($resolutionLabels[$dispute['resolution']] ?? $dispute['resolution']);
                                ?>
                            </p>
                            <?php if ($dispute['resolution_notes']): ?>
                                <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($dispute['resolution_notes'])?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-600 mt-2">
                                Resolved by <?php echo e($dispute['resolved_by_name'] ?: $dispute['resolved_by_email'])?>
                                on <?php echo date('F d, Y \a\t g:i A', strtotime($dispute['resolved_at']))?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Details -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Information</h2>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-600">Order ID</p>
                    <p class="font-medium">#<?php echo e($dispute['order_id'])?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Order Status</p>
                    <p class="font-medium"><?php echo e(ucfirst(str_replace('_', ' ', $dispute['order_status'])))?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Price</p>
                    <p class="font-medium">$<?php echo number_format($dispute['order_price'], 2)?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Deadline</p>
                    <p class="font-medium"><?php echo date('M d, Y', strtotime($dispute['deadline']))?></p>
                </div>
            </div>

            <div class="border-t pt-4">
                <p class="text-sm text-gray-600 mb-2">Requirements</p>
                <p class="text-gray-900 whitespace-pre-wrap"><?php echo e($dispute['requirements'])?></p>

                <?php if (! empty($dispute['requirement_files'])): ?>
                    <div class="mt-3">
                        <p class="text-sm text-gray-600 mb-2">Requirement Files</p>
                        <div class="space-y-2">
                            <?php foreach ($dispute['requirement_files'] as $file): ?>
                                <div class="flex items-center text-sm text-blue-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                    </svg>
                                    <?php echo e($file['original_name'] ?? $file['filename'])?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($dispute['delivery_message']): ?>
                <div class="border-t pt-4 mt-4">
                    <p class="text-sm text-gray-600 mb-2">Delivery Message</p>
                    <p class="text-gray-900 whitespace-pre-wrap"><?php echo e($dispute['delivery_message'])?></p>

                    <?php if (! empty($dispute['delivery_files'])): ?>
                        <div class="mt-3">
                            <p class="text-sm text-gray-600 mb-2">Delivery Files</p>
                            <div class="space-y-2">
                                <?php foreach ($dispute['delivery_files'] as $file): ?>
                                    <div class="flex items-center text-sm text-blue-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                        <?php echo e($file['original_name'] ?? $file['filename'])?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if (! empty($messages)): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Messages</h2>

                <div class="space-y-4">
                    <?php foreach ($messages as $message): ?>
                        <div class="border-l-4 <?php echo $message['sender_role'] === 'client' ? 'border-blue-500' : 'border-green-500'?> pl-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-medium text-gray-900">
                                    <?php echo e($message['sender_name'] ?: $message['sender_email'])?>
                                    <span class="text-sm text-gray-500">(<?php echo e(ucfirst($message['sender_role']))?>)</span>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?php echo date('M d, Y g:i A', strtotime($message['created_at']))?>
                                </p>
                            </div>
                            <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($message['content'])?></p>

                            <?php if (! empty($message['attachments'])): ?>
                                <div class="mt-2 space-y-1">
                                    <?php foreach ($message['attachments'] as $attachment): ?>
                                        <div class="flex items-center text-sm text-blue-600">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                            <?php echo e($attachment['original_name'] ?? $attachment['filename'])?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($message['is_flagged']): ?>
                                <div class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"/>
                                    </svg>
                                    Flagged for review
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Parties Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Parties</h3>

            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Client</p>
                    <p class="font-medium"><?php echo e($dispute['client_name'] ?: $dispute['client_email'])?></p>
                    <p class="text-sm text-gray-500"><?php echo e($dispute['client_email'])?></p>
                    <a href="/admin/users/<?php echo e($dispute['client_id'])?>" class="text-sm text-blue-600 hover:text-blue-800">
                        View Profile →
                    </a>
                </div>

                <div class="border-t pt-4">
                    <p class="text-sm text-gray-600 mb-1">Student</p>
                    <p class="font-medium"><?php echo e($dispute['student_name'] ?: $dispute['student_email'])?></p>
                    <p class="text-sm text-gray-500"><?php echo e($dispute['student_email'])?></p>
                    <a href="/admin/users/<?php echo e($dispute['student_id'])?>" class="text-sm text-blue-600 hover:text-blue-800">
                        View Profile →
                    </a>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        <?php if ($payment): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h3>

                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Total Amount</p>
                        <p class="font-medium text-lg">$<?php echo number_format($payment['amount'], 2)?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Commission</p>
                        <p class="font-medium">$<?php echo number_format($payment['commission_amount'], 2)?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Student Amount</p>
                        <p class="font-medium">$<?php echo number_format($payment['student_amount'], 2)?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Payment Status</p>
                        <p class="font-medium"><?php echo e(ucfirst($payment['status']))?></p>
                    </div>
                    <?php if ($payment['refund_amount'] > 0): ?>
                        <div>
                            <p class="text-sm text-gray-600">Refunded Amount</p>
                            <p class="font-medium text-red-600">$<?php echo number_format($payment['refund_amount'], 2)?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Resolution Actions -->
        <?php if ($dispute['status'] === 'open'): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Resolve Dispute</h3>

                <form method="POST" action="/admin/disputes/<?php echo e($dispute['id'])?>/resolve" id="resolveForm">
                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? '')?>">

                    <!-- Resolution Type -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Resolution</label>
                        <select
                            name="resolution"
                            id="resolution"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onchange="togglePartialRefundInput()"
                        >
                            <option value="">Select resolution...</option>
                            <option value="release_to_student">Release Payment to Student</option>
                            <option value="refund_to_client">Full Refund to Client</option>
                            <option value="partial_refund">Partial Refund</option>
                        </select>
                    </div>

                    <!-- Partial Refund Amount (hidden by default) -->
                    <div id="partialRefundDiv" class="mb-4" style="display: none;">
                        <label for="partial_amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Refund Amount
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">$</span>
                            <input
                                type="number"
                                name="partial_amount"
                                id="partial_amount"
                                step="0.01"
                                min="0.01"
                                max="<?php echo $payment ? $payment['amount'] : 0?>"
                                class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="0.00"
                            />
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Maximum: $<?php echo $payment ? number_format($payment['amount'], 2) : '0.00'?>
                        </p>
                    </div>

                    <!-- Resolution Notes -->
                    <div class="mb-4">
                        <label for="resolution_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Resolution Notes
                        </label>
                        <textarea
                            name="resolution_notes"
                            id="resolution_notes"
                            rows="4"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Explain your decision..."
                        ></textarea>
                        <p class="mt-1 text-sm text-gray-500">
                            This will be sent to both parties
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        onclick="return confirm('Are you sure you want to resolve this dispute? This action cannot be undone.')"
                    >
                        Resolve Dispute
                    </button>
                </form>
            </div>

            <script>
                function togglePartialRefundInput() {
                    const resolution = document.getElementById('resolution').value;
                    const partialRefundDiv = document.getElementById('partialRefundDiv');
                    const partialAmountInput = document.getElementById('partial_amount');

                    if (resolution === 'partial_refund') {
                        partialRefundDiv.style.display = 'block';
                        partialAmountInput.required = true;
                    } else {
                        partialRefundDiv.style.display = 'none';
                        partialAmountInput.required = false;
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
