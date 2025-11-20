<?php
    /**
     * User Violation History View
     *
     * @var array $user User details
     * @var array $violations List of violations
     */

    $pageTitle = 'Violation History - ' . e($user['email']);
    ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="/admin/users/<?php echo e($user['id']) ?>" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to User Profile
        </a>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Violation History</h1>
                <p class="mt-2 text-sm text-gray-600">
                    User: <span class="font-medium"><?php echo e($user['name'] ?? explode('@', $user['email'])[0]) ?></span>
                    (<?php echo e($user['email']) ?>)
                </p>
                <div class="mt-3 flex items-center space-x-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        <?php if ($user['role'] === 'admin'): ?>
                            bg-purple-100 text-purple-800
                        <?php elseif ($user['role'] === 'student'): ?>
                            bg-blue-100 text-blue-800
                        <?php else: ?>
                            bg-green-100 text-green-800
                        <?php endif; ?>
                    ">
                        <?php echo e(ucfirst($user['role'])) ?>
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        <?php if ($user['status'] === 'active'): ?>
                            bg-green-100 text-green-800
                        <?php elseif ($user['status'] === 'suspended'): ?>
                            bg-red-100 text-red-800
                        <?php else: ?>
                            bg-yellow-100 text-yellow-800
                        <?php endif; ?>
                    ">
                        <?php echo e(ucfirst($user['status'])) ?>
                    </span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Total Violations</div>
                <div class="text-4xl font-bold text-gray-900"><?php echo count($violations) ?></div>
            </div>
        </div>

        <!-- Suspension Info -->
        <?php if ($user['status'] === 'suspended' && $user['suspension_end_date']): ?>
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">Currently Suspended</p>
                        <p class="text-sm text-red-700 mt-1">
                            Suspension ends: <?php echo date('F d, Y \a\t g:i A', strtotime($user['suspension_end_date'])) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php elseif ($user['status'] === 'suspended' && !$user['suspension_end_date']): ?>
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">Permanently Banned</p>
                        <p class="text-sm text-red-700 mt-1">This user has been permanently banned from the platform</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Violations Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($violations)): ?>
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No Violations</h3>
                <p class="mt-1 text-sm text-gray-500">This user has a clean record with no policy violations.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violation Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penalty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin Notes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Confirmed By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($violations as $violation): ?>
                            <tr class="hover:bg-gray-50">
                                <!-- Date -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($violation['created_at'])) ?>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('g:i A', strtotime($violation['created_at'])) ?>
                                    </div>
                                </td>

                                <!-- Violation Type -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php if ($violation['violation_type'] === 'off_platform_contact'): ?>
                                            bg-orange-100 text-orange-800
                                        <?php elseif ($violation['violation_type'] === 'payment_circumvention'): ?>
                                            bg-red-100 text-red-800
                                        <?php else: ?>
                                            bg-gray-100 text-gray-800
                                        <?php endif; ?>
                                    ">
                                        <?php echo ucfirst(str_replace('_', ' ', $violation['violation_type'])) ?>
                                    </span>
                                    <?php if ($violation['message_id']): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Message #<?php echo e($violation['message_id']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Severity -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php if ($violation['severity'] === 'critical'): ?>
                                            bg-red-100 text-red-800
                                        <?php elseif ($violation['severity'] === 'major'): ?>
                                            bg-orange-100 text-orange-800
                                        <?php elseif ($violation['severity'] === 'minor'): ?>
                                            bg-yellow-100 text-yellow-800
                                        <?php else: ?>
                                            bg-blue-100 text-blue-800
                                        <?php endif; ?>
                                    ">
                                        <?php echo ucfirst($violation['severity']) ?>
                                    </span>
                                </td>

                                <!-- Penalty -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-medium">
                                        <?php echo ucfirst(str_replace('_', ' ', $violation['penalty_type'])) ?>
                                    </div>
                                    <?php if ($violation['suspension_days']): ?>
                                        <div class="text-xs text-gray-500">
                                            <?php echo $violation['suspension_days'] ?> day<?php echo $violation['suspension_days'] > 1 ? 's' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Admin Notes -->
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                                    <?php if ($violation['admin_notes']): ?>
                                        <div class="line-clamp-2" title="<?php echo e($violation['admin_notes']) ?>">
                                            <?php echo e($violation['admin_notes']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400">No notes</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Confirmed By -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    Admin #<?php echo e($violation['confirmed_by']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Summary Statistics -->
    <?php if (!empty($violations)): ?>
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- By Severity -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 mb-3">By Severity</h3>
                <div class="space-y-2">
                    <?php
                        $severityCounts = array_count_values(array_column($violations, 'severity'));
                    ?>
                    <?php foreach (['critical', 'major', 'minor', 'warning'] as $severity): ?>
                        <?php if (isset($severityCounts[$severity])): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700"><?php echo ucfirst($severity) ?>:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo $severityCounts[$severity] ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- By Type -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 mb-3">By Type</h3>
                <div class="space-y-2">
                    <?php
                        $typeCounts = array_count_values(array_column($violations, 'violation_type'));
                    ?>
                    <?php foreach ($typeCounts as $type => $count): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700"><?php echo ucfirst(str_replace('_', ' ', $type)) ?>:</span>
                            <span class="text-sm font-medium text-gray-900"><?php echo $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- By Penalty -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 mb-3">By Penalty</h3>
                <div class="space-y-2">
                    <?php
                        $penaltyCounts = array_count_values(array_column($violations, 'penalty_type'));
                    ?>
                    <?php foreach ($penaltyCounts as $penalty => $count): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700"><?php echo ucfirst(str_replace('_', ' ', $penalty)) ?>:</span>
                            <span class="text-sm font-medium text-gray-900"><?php echo $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 mb-3">Timeline</h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">First Violation:</span>
                        <span class="text-sm font-medium text-gray-900">
                            <?php echo date('M d, Y', strtotime(end($violations)['created_at'])) ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Latest Violation:</span>
                        <span class="text-sm font-medium text-gray-900">
                            <?php echo date('M d, Y', strtotime($violations[0]['created_at'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
