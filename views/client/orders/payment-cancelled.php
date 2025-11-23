<?php
/**
 * Payment Cancelled/Failed View
 * 
 * Displays when a payment is cancelled or fails, with option to retry
 */

$pageTitle = 'Payment Cancelled - Student Skills Marketplace';

?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-lg shadow-md p-8">
        <!-- Error Icon -->
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-bold text-center text-gray-900 mb-4">
            Payment Cancelled
        </h1>

        <!-- Message -->
        <p class="text-center text-gray-600 mb-8">
            Your payment was cancelled or failed to process. Don't worry, you can try again.
        </p>

        <!-- Service Details -->
        <?php if (isset($service)): ?>
        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Service:</span>
                    <span class="font-medium text-gray-900"><?= htmlspecialchars($service['title']) ?></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Student:</span>
                    <span class="font-medium text-gray-900"><?= htmlspecialchars($service['student_name'] ?? 'N/A') ?></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Price:</span>
                    <span class="font-medium text-gray-900">$<?= number_format($service['price'], 2) ?></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Delivery Time:</span>
                    <span class="font-medium text-gray-900"><?= htmlspecialchars($service['delivery_days']) ?> days</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Retry Information -->
        <?php if (isset($canRetry) && $canRetry): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-blue-800">
                            <strong>Retry Attempts:</strong> <?= $retryAttempts ?> of <?= $maxRetries ?>
                        </p>
                        <p class="text-sm text-blue-700 mt-1">
                            You have <?= $maxRetries - $retryAttempts ?> retry attempt<?= ($maxRetries - $retryAttempts) !== 1 ? 's' : '' ?> remaining.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4">
                <form method="POST" action="/orders/retry-payment" class="flex-1">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Retry Payment
                    </button>
                </form>

                <a href="/services/search" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-lg transition duration-200 text-center">
                    Browse Services
                </a>
            </div>
        <?php else: ?>
            <!-- Max Retries Reached -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-red-800">Maximum Retry Attempts Reached</p>
                        <p class="text-sm text-red-700 mt-1">
                            You've reached the maximum number of payment retry attempts (<?= $maxRetries ?>). 
                            Please contact support if you continue to experience issues.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="/services/search" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 text-center">
                    Browse Services
                </a>
                
                <a href="/disputes/create" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-lg transition duration-200 text-center">
                    Contact Support
                </a>
            </div>
        <?php endif; ?>

        <!-- Help Text -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Common Payment Issues:</h3>
            <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                <li>Insufficient funds in your account</li>
                <li>Card declined by your bank</li>
                <li>Incorrect card details entered</li>
                <li>Network connection issues</li>
            </ul>
            <p class="text-sm text-gray-600 mt-4">
                If you continue to experience issues, please ensure your payment method is valid and has sufficient funds, 
                or try using a different payment method.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/dashboard.php'; ?>
