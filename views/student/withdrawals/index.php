<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Withdrawals</h1>
                <p class="text-gray-600 mt-2">Manage your earnings and withdraw funds to your Stripe account</p>
            </div>
            <?php if (! empty($stripeStatus['stripe_onboarding_complete'])): ?>
                <a
                    href="/student/withdrawals/stripe-dashboard"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Stripe Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['errors']['general'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo e($_SESSION['errors']['general']) ?>
        </div>
    <?php endif; ?>

    <!-- Stripe Connect Status Warning -->
    <?php if (empty($stripeStatus['stripe_onboarding_complete'])): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
            <div class="flex">
                <svg class="w-5 h-5 text-yellow-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-yellow-800">Stripe Connect Required</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        You need to complete Stripe Connect onboarding before you can withdraw funds.
                    </p>
                    <a
                        href="/student/profile/edit"
                        class="inline-flex items-center mt-3 px-4 py-2 bg-yellow-600 text-white text-sm rounded-md hover:bg-yellow-700"
                    >
                        Connect Stripe Account
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 mx-3 md:mx-0">
        <!-- Available Balance -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Available Balance</h3>
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900">$<?php echo number_format($balance['available_balance'], 2) ?></p>
            <p class="text-sm text-gray-500 mt-1">Ready to withdraw</p>
        </div>

        <!-- Pending Balance -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Pending Balance</h3>
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900">$<?php echo number_format($balance['pending_balance'], 2) ?></p>
            <p class="text-sm text-gray-500 mt-1">From active orders</p>
        </div>

        <!-- Total Withdrawn -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Total Withdrawn</h3>
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900">$<?php echo number_format($balance['total_withdrawn'], 2) ?></p>
            <p class="text-sm text-gray-500 mt-1">All time</p>
        </div>
    </div>

    <!-- Withdrawal Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Request Withdrawal</h2>

        <?php if (! empty($stripeStatus['stripe_onboarding_complete'])): ?>
        <form action="/student/withdrawals/request" method="POST" class="space-y-4">
            <?php echo csrf_field() ?>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                    Withdrawal Amount
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        step="0.01"
                        min="10"
                        max="<?php echo $balance['available_balance'] ?>"
                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <?php echo isset($_SESSION['errors']['amount']) ? 'border-red-500' : '' ?>"
                        placeholder="0.00"
                        value="<?php echo e(old('amount')) ?>"
                        required
                    >
                </div>
                <?php if (isset($_SESSION['errors']['amount'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['amount']) ?></p>
                <?php endif; ?>
                <p class="text-gray-500 text-sm mt-1">
                    Minimum: $10.00 • Available: $<?php echo number_format($balance['available_balance'], 2) ?>
                </p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium">Withdrawal Information</p>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>Funds will be transferred to your connected Stripe account</li>
                            <li>Processing typically takes 1-2 business days</li>
                            <li>You'll receive an email confirmation once processed</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
                    <?php echo $balance['available_balance'] < 10 ? 'disabled' : '' ?>
                >
                    Request Withdrawal
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="text-center py-8">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <p class="text-gray-600 font-medium">Stripe Connect Required</p>
            <p class="text-sm text-gray-500 mt-1">Connect your Stripe account to enable withdrawals</p>
            <a
                href="/student/profile/edit"
                class="inline-block mt-4 px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700"
            >
                Connect Stripe Account
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Withdrawal History -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Withdrawal History</h2>

        <?php if (empty($withdrawals)): ?>
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-600">No withdrawal history yet</p>
                <p class="text-sm text-gray-500 mt-1">Your withdrawal requests will appear here</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transfer ID</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($withdrawals as $withdrawal): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($withdrawal['requested_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    $<?php echo number_format($withdrawal['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $statusColors = [
                                            'pending'    => 'bg-yellow-100 text-yellow-800',
                                            'processing' => 'bg-blue-100 text-blue-800',
                                            'completed'  => 'bg-green-100 text-green-800',
                                            'failed'     => 'bg-red-100 text-red-800',
                                        ];
                                        $colorClass = $statusColors[$withdrawal['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full<?php echo $colorClass ?>">
                                        <?php echo ucfirst($withdrawal['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($withdrawal['stripe_transfer_id']): ?>
                                        <code class="text-xs"><?php echo e($withdrawal['stripe_transfer_id']) ?></code>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
    // Clear errors and old input after displaying
    unset($_SESSION['errors']);
    clear_old_input();
?>
