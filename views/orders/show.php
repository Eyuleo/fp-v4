<?php
    require_once __DIR__ . '/../../src/Helpers.php';
    require_once __DIR__ . '/../../src/Auth.php';

    $title = 'Order #' . e($order['id']) . ' - Student Skills Marketplace';

    require_once __DIR__ . '/../../src/Services/FileService.php';
    $fileService = new FileService();

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

        <?php if ($order['status'] === 'revision_requested' && !empty($currentRevision)): ?>
            <div class="mb-4 p-4 border-2 border-orange-300 bg-orange-50 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-orange-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <div class="font-bold text-orange-900 text-lg mb-2">Revision Requested</div>
                        <div class="text-sm text-gray-700 mb-2">
                            <span class="font-medium">Requested by:</span> <?php echo e($currentRevision['requester_name'] ?? explode('@', $currentRevision['requester_email'] ?? '')[0]) ?>
                            <span class="text-gray-500 ml-2">on <?php echo date('M d, Y H:i', strtotime($currentRevision['requested_at'])) ?></span>
                        </div>
                        <div class="bg-white border border-orange-200 rounded p-3 mt-2">
                            <div class="text-sm font-medium text-gray-700 mb-1">Revision Reason:</div>
                            <p class="text-gray-900 whitespace-pre-wrap"><?php echo e($currentRevision['revision_reason']) ?></p>
                        </div>
                        <div class="text-xs text-orange-700 mt-2">
                            Previously delivered files remain visible below.
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($order['status'] === 'revision_requested'): ?>
            <div class="mb-4 p-4 border border-orange-200 bg-orange-50 rounded">
                <div class="font-medium text-orange-900">Revision requested</div>
                <div class="text-sm text-orange-800 mt-1">
                    The client has requested a revision. Previously delivered files remain visible below.
                </div>
            </div>
        <?php endif; ?>

        <?php
            $isPastDeadline         = strtotime($order['deadline']) < time();
            $isInProgressOrRevision = in_array($order['status'], ['in_progress', 'revision_requested']);
        ?>

        <?php if ($isPastDeadline && $isInProgressOrRevision): ?>
            <div class="mb-4 p-4 border-2 border-red-300 bg-red-50 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856..."/>
                    </svg>
                    <div class="flex-1">
                        <div class="font-bold text-red-900 text-lg mb-1">⚠️ Order Past Deadline</div>
                        <div class="text-sm text-red-800">
                            This order is overdue. Delivery functionality has been disabled.
                            <?php if ($order['student_id'] === Auth::user()['id']): ?>
                                Only an administrator can resolve this order.
                            <?php endif; ?>
                        </div>
                    </div>
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
                <p class="text-lg font-semibold text-gray-900">$<?php echo safe_number_format($order['price'], 2) ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Client</h3>
                <p class="text-gray-900"><?php echo e($order['client_name'] ?? explode('@', $order['client_email'] ?? '')[0]) ?></p>
                <?php if (Auth::user()['role'] === 'admin'): ?>
                    <p class="text-sm text-gray-500"><?php echo e($order['client_email']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Student</h3>
                <p class="text-gray-900"><?php echo e($order['student_name'] ?? explode('@', $order['student_email'] ?? '')[0]) ?></p>
                <?php if (Auth::user()['role'] === 'admin'): ?>
                    <p class="text-sm text-gray-500"><?php echo e($order['student_email']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Deadline</h3>
                <p class="text-gray-900 font-semibold"><?php echo date('M d, Y H:i', strtotime($order['deadline'])) ?></p>
                <?php
                    $deadlineTimestamp = strtotime($order['deadline']);
                    $currentTimestamp  = time();
                    $timeDiff          = $deadlineTimestamp - $currentTimestamp;
                    if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled') {
                        if ($timeDiff > 0) {
                            $days    = floor($timeDiff / 86400);
                            $hours   = floor(($timeDiff % 86400) / 3600);
                            $minutes = floor(($timeDiff % 3600) / 60);

                            $timeRemaining = '';
                            if ($days > 0) {
                                $timeRemaining = $days . ' day' . ($days > 1 ? 's' : '') . ', ' . $hours . ' hour' . ($hours > 1 ? 's' : '');
                            } elseif ($hours > 0) {
                                $timeRemaining = $hours . ' hour' . ($hours > 1 ? 's' : '') . ', ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                            } else {
                                $timeRemaining = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                            }

                            $urgencyClass = 'text-green-600';
                            if ($days < 1) {
                                $urgencyClass = 'text-red-600 font-bold';
                            } elseif ($days < 2) {
                                $urgencyClass = 'text-orange-600 font-semibold';
                            }
                            echo '<p class="text-sm ' . $urgencyClass . ' mt-1">⏱️ ' . $timeRemaining . ' remaining</p>';
                        } else {
                            $daysOverdue  = abs(floor($timeDiff / 86400));
                            $hoursOverdue = abs(floor(($timeDiff % 86400) / 3600));
                            $overdueText  = $daysOverdue > 0
                                ? $daysOverdue . ' day' . ($daysOverdue > 1 ? 's' : '') . ' overdue'
                                : $hoursOverdue . ' hour' . ($hoursOverdue > 1 ? 's' : '') . ' overdue';
                            echo '<p class="text-sm text-red-600 font-bold mt-1">🚨 ' . $overdueText . '</p>';
                        }
                    }
                ?>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Created</h3>
                <p class="text-gray-900"><?php echo date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
            </div>
        </div>
    </div>

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
                        <a href="<?php echo e($signedUrl) ?>" target="_blank"
                           class="flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded">
                            <span class="font-medium"><?php echo e($file['original_name']) ?></span>
                            <span class="text-gray-400">(<?php echo safe_number_format(($file['size'] ?? 0) / 1024, 2) ?> KB)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($order['status'] === 'delivered' || $order['status'] === 'revision_requested' || $order['status'] === 'completed'): ?>
        <?php if (!empty($deliveryHistory) && count($deliveryHistory) > 0): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    Delivery History
                    <?php if (count($deliveryHistory) > 1): ?>
                        <span class="text-sm font-normal text-gray-500">(<?php echo count($deliveryHistory) ?> deliveries)</span>
                    <?php endif; ?>
                </h2>
                <div class="space-y-6">
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
                            
                            <?php if (! empty($delivery['delivery_message'])): ?>
                                <div class="bg-white border <?php echo $delivery['is_current'] ? 'border-purple-200' : 'border-gray-200' ?> rounded p-3 mb-3">
                                    <div class="text-xs font-medium text-gray-600 mb-1">Message:</div>
                                    <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo e($delivery['delivery_message']) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (! empty($delivery['delivery_files'])): ?>
                                <div>
                                    <h3 class="text-xs font-medium text-gray-700 mb-2">Delivered Files:</h3>
                                    <div class="space-y-1">
                                        <?php foreach ($delivery['delivery_files'] as $file): ?>
                                            <?php if (empty($file['path'])) {
                                                    continue;
                                                }
                                            ?>
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
                            <?php else: ?>
                                <p class="text-xs text-gray-500">No files attached to this delivery.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($order['status'] === 'delivered' || $order['status'] === 'revision_requested' || $order['status'] === 'completed'): ?>
            <!-- Fallback for orders without delivery history (legacy data) -->
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
                                <a href="<?php echo e($signedUrl) ?>" target="_blank"
                                   class="flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded">
                                    <span class="font-medium"><?php echo e($file['original_name']) ?></span>
                                    <span class="text-gray-400">(<?php echo safe_number_format(($file['size'] ?? 0) / 1024, 2) ?> KB)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500">No delivered files available.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

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

<?php if ($order['status'] === 'completed'): ?>
    <?php if (! empty($review)): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Review</h2>

            <!-- Rating -->
            <div class="flex items-center mb-3">
                <?php
                    $rating = (int) ($review['rating'] ?? 0);
                    for ($i = 1; $i <= 5; $i++):
                        $filled = $i <= $rating;
                    ?>
                    <svg class="w-5 h-5 <?php echo $filled ? 'text-yellow-400' : 'text-gray-300' ?> mr-1"
                         fill="<?php echo $filled ? 'currentColor' : 'none' ?>"
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M11.48 3.499a.562.562 0 011.04 0l2.062 4.178a.563.563 0 00.424.308l4.616.671a.563.563 0 01.312.96l-3.339 3.255a.563.563 0 00-.162.498l.788 4.592a.563.563 0 01-.817.593l-4.123-2.168a.563.563 0 00-.524 0l-4.123 2.168a.563.563 0 01-.817-.593l.788-4.592a.563.563 0 00-.162-.498L3.37 9.616a.563.563 0 01.312-.96l4.616-.671a.563.563 0 00.424-.308l2.062-4.178z" />
                    </svg>
                <?php endfor; ?>
                <span class="ml-2 text-sm text-gray-600">
                    <?php echo safe_number_format((float) ($review['rating'] ?? 0), 1) ?>/5
                </span>
            </div>

            <!-- Comment -->
            <?php if (! empty($review['comment'])): ?>
                <p class="text-gray-800 whitespace-pre-wrap"><?php echo e($review['comment']) ?></p>
            <?php else: ?>
                <p class="text-sm text-gray-500">No comment provided.</p>
            <?php endif; ?>

            <!-- Meta -->
            <div class="mt-3 text-xs text-gray-500">
                Reviewed by                            <?php echo e($review['client_name'] ?? 'Client') ?>
                on:<?php echo ! empty($review['created_at']) ? date('M d, Y H:i', strtotime($review['created_at'])) : '' ?>
            </div>

            <!-- Student Reply -->
            <?php if (! empty($review['student_reply'])): ?>
                <div class="mt-5 border rounded bg-gray-50 p-4">
                    <div class="text-sm font-medium text-gray-700 mb-1">Student reply</div>
                    <p class="text-gray-800 whitespace-pre-wrap"><?php echo e($review['student_reply']) ?></p>
                    <?php if (! empty($review['student_replied_at'])): ?>
                        <div class="mt-2 text-xs text-gray-500">
                            Replied on                                                                                                                   <?php echo date('M d, Y H:i', strtotime($review['student_replied_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="/orders" class="text-gray-600 hover:text-gray-900">← Back to Orders</a>
                <a href="/messages/thread/<?php echo e($order['id']) ?>" class="flex items-center space-x-2 text-blue-600 hover:text-blue-700">
                    <span>Messages</span>
                </a>
            </div>

            <div class="space-x-3">
                <?php if (($order['status'] === 'in_progress' || $order['status'] === 'revision_requested') && $order['student_id'] === Auth::user()['id']): ?>
                    <?php if ($isPastDeadline): ?>
                        <button disabled class="bg-gray-400 text-white px-6 py-2 rounded-md cursor-not-allowed opacity-60">
                            Deliver Order (Disabled)
                        </button>
                    <?php else: ?>
                        <a href="/orders/<?php echo e($order['id']) ?>/deliver" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                            Deliver Order
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($order['status'] === 'delivered' && $order['client_id'] === Auth::user()['id']): ?>
                    <form action="/orders/<?php echo e($order['id']) ?>/complete" method="POST" class="inline" data-loading>
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                            Accept & Complete
                        </button>
                    </form>
                    <?php if (($order['revision_count'] ?? 0) < ($order['max_revisions'] ?? 0)): ?>
                        <a href="/orders/<?php echo e($order['id']) ?>/request-revision" class="inline-block bg-orange-600 text-white px-6 py-2 rounded-md hover:bg-orange-700">
                            Request Revision (<?php echo e(($order['max_revisions'] ?? 0) - ($order['revision_count'] ?? 0)) ?> left)
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
                    // Check if user can open a dispute (for delivered or revision_requested orders)
                    $canOpenDispute = false;
                    $hasOpenDispute = false;
                    if (in_array($order['status'], ['delivered', 'revision_requested'])) {
                        $currentUserId = Auth::user()['id'];
                        $isPartyToOrder = ($order['client_id'] === $currentUserId || $order['student_id'] === $currentUserId);
                        
                        if ($isPartyToOrder) {
                            // Check if there's already an open dispute
                            try {
                                $db = require __DIR__ . '/../../config/database.php';
                                require_once __DIR__ . '/../../src/Repositories/DisputeRepository.php';
                                $disputeRepo = new DisputeRepository($db);
                                $hasOpenDispute = $disputeRepo->hasOpenDispute($order['id']);
                                $canOpenDispute = !$hasOpenDispute;
                            } catch (Exception $e) {
                                // Silently fail
                            }
                        }
                    }
                ?>
                <?php if ($canOpenDispute): ?>
                    <a href="/disputes/create?order_id=<?php echo e($order['id']) ?>" class="inline-block bg-yellow-600 text-white px-6 py-2 rounded-md hover:bg-yellow-700">
                        Open Dispute
                    </a>
                <?php endif; ?>

                <?php
                    // NEW: Leave a Review button for client when order is completed and no review exists yet
                ?>
                <?php if ($order['status'] === 'completed' && $order['client_id'] === Auth::user()['id'] && empty($review)): ?>
                    <a href="/reviews/create?order_id=<?php echo e($order['id']) ?>"
                       class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                        Leave a Review
                    </a>
                <?php endif; ?>

                <?php if (Auth::user()['role'] === 'admin' && $order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                    <button onclick="showCancelModal()" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700">
                        Cancel Order
                    </button>
                    <?php if ($order['status'] === 'delivered' && $isPastDeadline): ?>
                        <!-- PATCH: Force Complete button -->
                        <button onclick="showForceCompleteModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                            Force Complete (Overdue)
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
    $content = ob_get_clean();
    ob_start();
?>

<!-- Cancel Order Modal -->
<div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg max-w-md w-full mx-4 p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Cancel Order</h3>
        <form action="/orders/<?php echo e($order['id']) ?>/cancel" method="POST" data-loading>
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reason (optional)
                </label>
                <textarea name="cancellation_reason" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                          placeholder="Provide a reason..."></textarea>
            </div>
            <div class="flex items-center justify-end space-x-3">
                <button type="button" onclick="hideCancelModal()" class="px-4 py-2 text-gray-700 hover:text-gray-900">Keep Order</button>
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700">Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<!-- PATCH: Force Complete Modal -->
<div id="forceCompleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg max-w-md w-full mx-4 p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Force Complete Order</h3>
        <p class="text-sm text-gray-600 mb-4">
            This order is overdue and still in delivered status. Completing will credit the student and mark the order as completed.
        </p>
        <form action="/admin/orders/<?php echo e($order['id']) ?>/force-complete" method="POST" data-loading>
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Internal note (optional)</label>
                <textarea name="force_complete_reason" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder="Add a note for audit purposes..."></textarea>
            </div>
            <div class="flex items-center justify-end space-x-3">
                <button type="button" onclick="hideForceCompleteModal()" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">Force Complete</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCancelModal(){ document.getElementById('cancelModal').classList.remove('hidden'); }
function hideCancelModal(){ document.getElementById('cancelModal').classList.add('hidden'); }
function showForceCompleteModal(){ document.getElementById('forceCompleteModal').classList.remove('hidden'); }
function hideForceCompleteModal(){ document.getElementById('forceCompleteModal').classList.add('hidden'); }

['cancelModal','forceCompleteModal']
.forEach(id=>{
    const el=document.getElementById(id);
    if(!el) return;
    el.addEventListener('click',e=>{
        if(e.target!==el) return;
        if(id==='cancelModal') hideCancelModal();
        if(id==='forceCompleteModal') hideForceCompleteModal();
    });
});
</script>

<?php
    $additionalScripts = ob_get_clean();
    $userRole          = $_SESSION['user_role'] ?? 'guest';
    if ($userRole === 'admin') {
        include __DIR__ . '/../layouts/admin.php';
    } else {
        include __DIR__ . '/../layouts/dashboard.php';
    }
?>
