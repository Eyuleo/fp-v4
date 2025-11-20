<?php
    /**
     * Violation Confirmation Form View
     *
     * @var array $message Message details
     * @var array $sender Sender user details
     * @var array $violations Sender's violation history
     * @var string $suggestedPenalty Suggested penalty type
     */

    $pageTitle = 'Confirm Violation';
    ob_start();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="/admin/moderation/messages" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Messages
        </a>
    </div>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Confirm Violation</h1>
        <p class="mt-2 text-sm text-gray-600">Review the message and apply appropriate penalty</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <!-- Message Details -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Flagged Message</h2>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm font-medium text-gray-500">Message ID:</span>
                        <span class="text-sm text-gray-900 ml-2">#<?php echo e($message['id']) ?></span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Sender:</span>
                        <span class="text-sm text-gray-900 ml-2">
                            <?php echo e($sender['name'] ?? explode('@', $sender['email'])[0]) ?>
                            (ID: <?php echo e($sender['id']) ?>)
                        </span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Date:</span>
                        <span class="text-sm text-gray-900 ml-2">
                            <?php echo date('F d, Y \a\t g:i A', strtotime($message['created_at'])) ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500 block mb-2">Message Content:</span>
                        <div class="bg-gray-50 border border-gray-200 rounded p-4">
                            <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo e($message['content']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Violation Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Violation Details</h2>
                <form method="POST" action="/admin/moderation/violations/confirm" data-loading>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="message_id" value="<?php echo e($message['id']) ?>">

                    <div class="space-y-4">
                        <!-- Violation Type -->
                        <div>
                            <label for="violation_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Violation Type <span class="text-red-500">*</span>
                            </label>
                            <select name="violation_type" id="violation_type" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select violation type</option>
                                <option value="off_platform_contact">Off-Platform Contact</option>
                                <option value="payment_circumvention">Payment Circumvention</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Severity -->
                        <div>
                            <label for="severity" class="block text-sm font-medium text-gray-700 mb-1">
                                Severity <span class="text-red-500">*</span>
                            </label>
                            <select name="severity" id="severity" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select severity</option>
                                <option value="warning">Warning</option>
                                <option value="minor">Minor</option>
                                <option value="major">Major</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <!-- Penalty Type -->
                        <div>
                            <label for="penalty_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Penalty Type <span class="text-red-500">*</span>
                            </label>
                            <select name="penalty_type" id="penalty_type" required onchange="toggleSuspensionDays()" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select penalty type</option>
                                <option value="warning" <?php echo $suggestedPenalty === 'warning' ? 'selected' : '' ?>>Warning Only</option>
                                <option value="temp_suspension" <?php echo $suggestedPenalty === 'temp_suspension' ? 'selected' : '' ?>>Temporary Suspension</option>
                                <option value="permanent_ban" <?php echo $suggestedPenalty === 'permanent_ban' ? 'selected' : '' ?>>Permanent Ban</option>
                            </select>
                            <?php if ($suggestedPenalty): ?>
                                <p class="mt-1 text-xs text-blue-600">
                                    💡 Suggested: <?php echo ucfirst(str_replace('_', ' ', $suggestedPenalty)) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Suspension Days (conditional) -->
                        <div id="suspension_days_container" style="display: none;">
                            <label for="suspension_days" class="block text-sm font-medium text-gray-700 mb-1">
                                Suspension Duration (days) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="suspension_days" id="suspension_days" min="1" max="365" placeholder="e.g., 7, 30, 90" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">
                                Common durations: 7 days (first offense), 30 days (repeat offense), 90 days (serious violation)
                            </p>
                        </div>

                        <!-- Admin Notes -->
                        <div>
                            <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">
                                Admin Notes <span class="text-red-500">*</span>
                            </label>
                            <textarea name="admin_notes" id="admin_notes" rows="4" required placeholder="Explain the reason for this violation and penalty..." class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                This will be included in the notification sent to the user
                            </p>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-3 pt-4">
                            <button type="submit" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                Confirm Violation
                            </button>
                            <a href="/admin/moderation/messages" class="flex-1 text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- User Info -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">User Information</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm font-medium text-gray-500">Email:</span>
                        <p class="text-sm text-gray-900"><?php echo e($sender['email']) ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Role:</span>
                        <p class="text-sm text-gray-900"><?php echo e(ucfirst($sender['role'])) ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Status:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php if ($sender['status'] === 'active'): ?>
                                bg-green-100 text-green-800
                            <?php elseif ($sender['status'] === 'suspended'): ?>
                                bg-red-100 text-red-800
                            <?php else: ?>
                                bg-yellow-100 text-yellow-800
                            <?php endif; ?>
                        ">
                            <?php echo e(ucfirst($sender['status'])) ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Member Since:</span>
                        <p class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($sender['created_at'])) ?></p>
                    </div>
                    <div class="pt-2">
                        <a href="/admin/users/<?php echo e($sender['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800">
                            View Full Profile →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Violation History -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    Violation History
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        <?php echo count($violations) ?>
                    </span>
                </h3>
                
                <?php if (empty($violations)): ?>
                    <p class="text-sm text-gray-500">No previous violations</p>
                <?php else: ?>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php foreach ($violations as $violation): ?>
                            <div class="border-l-4 <?php 
                                echo $violation['severity'] === 'critical' ? 'border-red-500' : 
                                    ($violation['severity'] === 'major' ? 'border-orange-500' : 
                                    ($violation['severity'] === 'minor' ? 'border-yellow-500' : 'border-gray-300'))
                            ?> pl-3 py-2">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-xs font-medium text-gray-900">
                                        <?php echo ucfirst(str_replace('_', ' ', $violation['violation_type'])) ?>
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('M d, Y', strtotime($violation['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-600">
                                    <span class="font-medium">Penalty:</span>
                                    <?php echo ucfirst(str_replace('_', ' ', $violation['penalty_type'])) ?>
                                    <?php if ($violation['suspension_days']): ?>
                                        (<?php echo $violation['suspension_days'] ?> days)
                                    <?php endif; ?>
                                </div>
                                <?php if ($violation['admin_notes']): ?>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                        <?php echo e($violation['admin_notes']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <a href="/admin/users/<?php echo e($sender['id']) ?>/violations" class="text-sm text-blue-600 hover:text-blue-800">
                            View All Violations →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSuspensionDays() {
    const penaltyType = document.getElementById('penalty_type').value;
    const container = document.getElementById('suspension_days_container');
    const input = document.getElementById('suspension_days');
    
    if (penaltyType === 'temp_suspension') {
        container.style.display = 'block';
        input.required = true;
    } else {
        container.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleSuspensionDays();
});
</script>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
