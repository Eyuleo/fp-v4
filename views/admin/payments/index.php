<?php
    /**
     * Admin Payment History View
     */

    $pageTitle = 'Payment History - Admin';
    ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment History</h1>
        <p class="text-gray-600">View and manage all platform payments</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="/admin/payments" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="pending"                                            <?php echo($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="succeeded"                                              <?php echo($status ?? '') === 'succeeded' ? 'selected' : '' ?>>Succeeded</option>
                    <option value="refunded"                                             <?php echo($status ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                    <option value="partially_refunded"                                                       <?php echo($status ?? '') === 'partially_refunded' ? 'selected' : '' ?>>Partially Refunded</option>
                    <option value="failed"                                           <?php echo($status ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>

            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo e($dateFrom ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo e($dateTo ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <?php
            $totalAmount     = 0;
            $totalCommission = 0;
            $totalRefunded   = 0;
            $succeededCount  = 0;

            foreach ($payments as $payment) {
                if ($payment['status'] === 'succeeded') {
                    $totalAmount += $payment['amount'];
                    $totalCommission += $payment['commission_amount'];
                    $succeededCount++;
                }
                $totalRefunded += $payment['refund_amount'];
            }
        ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Total Volume</div>
            <div class="text-2xl font-bold text-gray-900">$<?php echo number_format($totalAmount, 2) ?></div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Total Commission</div>
            <div class="text-2xl font-bold text-green-600">$<?php echo number_format($totalCommission, 2) ?></div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Total Refunded</div>
            <div class="text-2xl font-bold text-red-600">$<?php echo number_format($totalRefunded, 2) ?></div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Successful Payments</div>
            <div class="text-2xl font-bold text-gray-900"><?php echo $succeededCount ?></div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <?php if (empty($payments)): ?>
            <div class="p-8 text-center text-gray-500">
                <p>No payments found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payment ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Student
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Commission
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stripe IDs
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                            <?php
                                $statusColors = [
                                    'pending'            => 'bg-yellow-100 text-yellow-800',
                                    'succeeded'          => 'bg-green-100 text-green-800',
                                    'refunded'           => 'bg-red-100 text-red-800',
                                    'partially_refunded' => 'bg-orange-100 text-orange-800',
                                    'failed'             => 'bg-gray-100 text-gray-800',
                                ];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo e($payment['id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="/orders/<?php echo e($payment['order_id']) ?>" class="text-blue-600 hover:text-blue-800">
                                        Order #<?php echo e($payment['order_id']) ?>
                                    </a>
                                    <div class="text-xs text-gray-500"><?php echo e($payment['service_title']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($payment['client_name'] ?? explode('@', $payment['client_email'] ?? '')[0]) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($payment['student_name'] ?? explode('@', $payment['student_email'] ?? '')[0]) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-semibold">$<?php echo number_format($payment['amount'], 2) ?></div>
                                    <?php if ($payment['refund_amount'] > 0): ?>
                                        <div class="text-xs text-red-600">
                                            Refunded: $<?php echo number_format($payment['refund_amount'], 2) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    $<?php echo number_format($payment['commission_amount'], 2) ?>
                                    <div class="text-xs text-gray-500">
                                        Student: $<?php echo number_format($payment['student_amount'], 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full                                                                                                                    <?php echo $statusColors[$payment['status']] ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', e($payment['status']))) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php if ($payment['stripe_payment_intent_id']): ?>
                                        <div class="text-xs mb-1">
                                            <span class="font-medium">PI:</span>
                                            <span class="font-mono"><?php echo e(substr($payment['stripe_payment_intent_id'], 0, 20)) ?>...</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($payment['stripe_checkout_session_id']): ?>
                                        <div class="text-xs mb-1">
                                            <span class="font-medium">CS:</span>
                                            <span class="font-mono"><?php echo e(substr($payment['stripe_checkout_session_id'], 0, 20)) ?>...</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($payment['stripe_transfer_id']): ?>
                                        <div class="text-xs">
                                            <span class="font-medium">TR:</span>
                                            <span class="font-mono"><?php echo e(substr($payment['stripe_transfer_id'], 0, 20)) ?>...</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($payment['created_at'])) ?>
                                    <div class="text-xs"><?php echo date('H:i:s', strtotime($payment['created_at'])) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing page                                         <?php echo $page ?> of<?php echo $totalPages ?> (<?php echo $totalCount ?> total payments)
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
