<?php
    /**
     * Admin Message Moderation Dashboard View
     *
     * @var array $messages List of messages with sender/recipient info
     * @var int $totalCount Total number of messages matching filters
     * @var int $totalPages Total number of pages
     * @var int $page Current page number
     * @var array|null $conversationThread Full conversation thread if viewing a message
     * @var int|null $viewingMessageId ID of message being viewed
     */

    $pageTitle = 'Message Moderation';
    ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Message Moderation</h1>
        <p class="mt-2 text-sm text-gray-600">Review and moderate platform messages</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="/admin/moderation/messages" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Flagged Only Filter -->
                <div>
                    <label for="flagged" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="flagged" id="flagged" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Messages</option>
                        <option value="1" <?php echo isset($_GET['flagged']) && $_GET['flagged'] === '1' ? 'selected' : '' ?>>Flagged Only</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo e($_GET['date_from'] ?? '') ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Date To -->
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo e($_GET['date_to'] ?? '') ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Sender ID -->
                <div>
                    <label for="sender_id" class="block text-sm font-medium text-gray-700 mb-1">Sender ID</label>
                    <input type="number" name="sender_id" id="sender_id" value="<?php echo e($_GET['sender_id'] ?? '') ?>" placeholder="User ID" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Order ID -->
                <div>
                    <label for="order_id" class="block text-sm font-medium text-gray-700 mb-1">Order ID</label>
                    <input type="number" name="order_id" id="order_id" value="<?php echo e($_GET['order_id'] ?? '') ?>" placeholder="Order ID" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Submit Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Apply Filters
                    </button>
                </div>

                <!-- Clear Filters -->
                <?php if (!empty($_GET['flagged']) || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['sender_id']) || !empty($_GET['order_id'])): ?>
                    <div class="flex items-end">
                        <a href="/admin/moderation/messages" class="w-full text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                            Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Showing <?php echo count($messages) ?> of <?php echo $totalCount ?> messages
            <?php if (isset($_GET['flagged']) && $_GET['flagged'] === '1'): ?>
                <span class="text-red-600 font-medium">(Flagged only)</span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Messages Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No messages found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <tr class="hover:bg-gray-50 <?php echo $message['is_flagged'] ? 'bg-red-50' : '' ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    #<?php echo e($message['id']) ?>
                                    <?php if ($message['is_flagged']): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                            🚩 Flagged
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="text-gray-900 font-medium">
                                        <?php echo e($message['sender_name'] ?? explode('@', $message['sender_email'])[0]) ?>
                                    </div>
                                    <div class="text-gray-500 text-xs">
                                        ID: <?php echo e($message['sender_id']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($message['sender_violation_count'] > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <?php echo $message['sender_violation_count'] ?> violation<?php echo $message['sender_violation_count'] > 1 ? 's' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($message['order_id']): ?>
                                        <a href="/orders/<?php echo e($message['order_id']) ?>" class="text-blue-600 hover:text-blue-800">
                                            #<?php echo e($message['order_id']) ?>
                                        </a>
                                        <div class="text-gray-500 text-xs">
                                            <?php echo e($message['service_title']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                                    <div class="truncate mb-2">
                                        <?php echo e(substr($message['content'], 0, 100)) ?><?php echo strlen($message['content']) > 100 ? '...' : '' ?>
                                    </div>
                                    <?php if (!empty($message['attachments'])): ?>
                                        <div class="text-xs text-gray-600">
                                            📎 <?php echo count($message['attachments']) ?> attachment<?php echo count($message['attachments']) > 1 ? 's' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($message['created_at'])) ?>
                                    <div class="text-xs text-gray-400">
                                        <?php echo date('g:i A', strtotime($message['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-col gap-1">
                                        <a href="?view_message=<?php echo e($message['id']) ?><?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : '' ?>" class="text-blue-600 hover:text-blue-900">
                                            View Thread
                                        </a>
                                        <?php if ($message['is_flagged']): ?>
                                            <div class="flex gap-2">
                                                <button onclick="openViolationModal(<?php echo e($message['id']) ?>, <?php echo e($message['sender_id']) ?>, '<?php echo e(addslashes($message['sender_name'] ?? explode('@', $message['sender_email'])[0])) ?>', <?php echo e($message['sender_violation_count']) ?>)" class="text-red-600 hover:text-red-900 text-sm">
                                                    Confirm Violation
                                                </button>
                                                <form method="POST" action="/admin/moderation/violations/dismiss" class="inline" onsubmit="return confirm('Are you sure you want to dismiss this flag?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="message_id" value="<?php echo e($message['id']) ?>">
                                                    <button type="submit" class="text-green-600 hover:text-green-900 text-sm">
                                                        Dismiss Flag
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <button onclick="openViolationModal(<?php echo e($message['id']) ?>, <?php echo e($message['sender_id']) ?>, '<?php echo e(addslashes($message['sender_name'] ?? explode('@', $message['sender_email'])[0])) ?>', <?php echo e($message['sender_violation_count']) ?>)" class="text-orange-600 hover:text-orange-900 text-sm text-left">
                                                Report Violation
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Previous Page -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1 ?><?php echo !empty($_GET['flagged']) ? '&flagged=' . urlencode($_GET['flagged']) : '' ?><?php echo !empty($_GET['date_from']) ? '&date_from=' . urlencode($_GET['date_from']) : '' ?><?php echo !empty($_GET['date_to']) ? '&date_to=' . urlencode($_GET['date_to']) : '' ?><?php echo !empty($_GET['sender_id']) ? '&sender_id=' . urlencode($_GET['sender_id']) : '' ?><?php echo !empty($_GET['order_id']) ? '&order_id=' . urlencode($_GET['order_id']) : '' ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        Previous
                    </a>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                            <?php echo $i ?>
                        </span>
                    <?php elseif ($i === 1 || $i === $totalPages || abs($i - $page) <= 2): ?>
                        <a href="?page=<?php echo $i ?><?php echo !empty($_GET['flagged']) ? '&flagged=' . urlencode($_GET['flagged']) : '' ?><?php echo !empty($_GET['date_from']) ? '&date_from=' . urlencode($_GET['date_from']) : '' ?><?php echo !empty($_GET['date_to']) ? '&date_to=' . urlencode($_GET['date_to']) : '' ?><?php echo !empty($_GET['sender_id']) ? '&sender_id=' . urlencode($_GET['sender_id']) : '' ?><?php echo !empty($_GET['order_id']) ? '&order_id=' . urlencode($_GET['order_id']) : '' ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <?php echo $i ?>
                        </a>
                    <?php elseif (abs($i - $page) === 3): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                            ...
                        </span>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Next Page -->
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1 ?><?php echo !empty($_GET['flagged']) ? '&flagged=' . urlencode($_GET['flagged']) : '' ?><?php echo !empty($_GET['date_from']) ? '&date_from=' . urlencode($_GET['date_from']) : '' ?><?php echo !empty($_GET['date_to']) ? '&date_to=' . urlencode($_GET['date_to']) : '' ?><?php echo !empty($_GET['sender_id']) ? '&sender_id=' . urlencode($_GET['sender_id']) : '' ?><?php echo !empty($_GET['order_id']) ? '&order_id=' . urlencode($_GET['order_id']) : '' ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>

    <!-- Conversation Thread Modal -->
    <?php if ($conversationThread && $viewingMessageId): ?>
        <div id="conversationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Conversation Thread</h3>
                    <a href="/admin/moderation/messages<?php echo isset($_GET['page']) ? '?page=' . $_GET['page'] : '' ?>" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                </div>
                <div class="max-h-96 overflow-y-auto space-y-4">
                    <?php foreach ($conversationThread as $msg): ?>
                        <div class="border-l-4 <?php echo $msg['id'] == $viewingMessageId ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50' ?> p-4 rounded">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-medium text-gray-900">
                                        <?php echo e($msg['sender_name'] ?? explode('@', $msg['sender_email'])[0]) ?>
                                    </span>
                                    <span class="text-xs text-gray-500 ml-2">
                                        (ID: <?php echo e($msg['sender_id']) ?>)
                                    </span>
                                    <?php if ($msg['is_flagged']): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                            🚩 Flagged
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('M d, Y g:i A', strtotime($msg['created_at'])) ?>
                                </span>
                            </div>
                            <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($msg['content']) ?></p>
                            <?php if (!empty($msg['attachments'])): ?>
                                <div class="mt-3">
                                    <div class="text-xs font-medium text-gray-700 mb-2">Attachments:</div>
                                    <?php
                                        // Format attachments for this message
                                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                                        $hasValidAttachments = false;
                                        
                                        foreach ($msg['attachments'] as $attachment) {
                                            $extension = strtolower($attachment['extension'] ?? '');
                                            if (!in_array($extension, $allowedExtensions)) {
                                                continue;
                                            }
                                            $hasValidAttachments = true;
                                            
                                            $filename = htmlspecialchars($attachment['original_name'] ?? $attachment['filename'] ?? 'Unknown');
                                            $path = htmlspecialchars($attachment['path'] ?? '');
                                            $size = $attachment['size'] ?? 0;
                                            
                                            // Format file size
                                            if ($size < 1024) {
                                                $sizeFormatted = $size . ' B';
                                            } elseif ($size < 1048576) {
                                                $sizeFormatted = round($size / 1024, 2) . ' KB';
                                            } else {
                                                $sizeFormatted = round($size / 1048576, 2) . ' MB';
                                            }
                                            
                                            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
                                            $fileTypeLabel = $isImage ? 'Image' : 'PDF';
                                            $icon = $isImage ? '🖼️' : '📄';
                                            
                                            // Generate signed URL for download
                                            $fileService = new FileService();
                                            $downloadUrl = htmlspecialchars($fileService->generateSignedUrl($path, 1800));
                                    ?>
                                        <div class="flex items-center gap-2 p-2 bg-white rounded border border-gray-200 mb-2">
                                            <span class="text-lg"><?php echo $icon ?></span>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <a href="<?php echo $downloadUrl ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium text-xs truncate"><?php echo $filename ?></a>
                                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700 whitespace-nowrap"><?php echo $fileTypeLabel ?></span>
                                                </div>
                                                <div class="text-xs text-gray-500"><?php echo $sizeFormatted ?></div>
                                            </div>
                                        </div>
                                    <?php
                                        }
                                        
                                        if (!$hasValidAttachments) {
                                            echo '<span class="text-xs text-gray-500">No attachments (only images and PDFs are displayed)</span>';
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action buttons for all messages -->
                            <div class="mt-3 pt-3 border-t border-gray-200 flex gap-2">
                                <?php
                                    // Get violation count for this sender
                                    $senderViolationCount = 0;
                                    try {
                                        $violationRepo = new ViolationRepository($GLOBALS['db'] ?? getDatabaseConnection());
                                        $senderViolationCount = $violationRepo->countByUserId($msg['sender_id']);
                                    } catch (Exception $e) {
                                        // Silently fail
                                    }
                                ?>
                                <?php if ($msg['is_flagged']): ?>
                                    <button onclick="openViolationModal(<?php echo e($msg['id']) ?>, <?php echo e($msg['sender_id']) ?>, '<?php echo e(addslashes($msg['sender_name'] ?? explode('@', $msg['sender_email'])[0])) ?>', <?php echo e($senderViolationCount) ?>)" class="text-sm px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                                        Confirm Violation
                                    </button>
                                    <form method="POST" action="/admin/moderation/violations/dismiss" class="inline" onsubmit="return confirm('Are you sure you want to dismiss this flag?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="message_id" value="<?php echo e($msg['id']) ?>">
                                        <button type="submit" class="text-sm px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                            Dismiss Flag
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button onclick="openViolationModal(<?php echo e($msg['id']) ?>, <?php echo e($msg['sender_id']) ?>, '<?php echo e(addslashes($msg['sender_name'] ?? explode('@', $msg['sender_email'])[0])) ?>', <?php echo e($senderViolationCount) ?>)" class="text-sm px-3 py-1 bg-orange-600 text-white rounded hover:bg-orange-700">
                                        Report Violation
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Violation Confirmation Modal (will be populated by JavaScript) -->
<div id="violationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div id="violationModalContent">
            <!-- Content will be loaded by JavaScript -->
        </div>
    </div>
</div>

<script>
function openViolationModal(messageId, senderId, senderName, violationCount) {
    window.location.href = '/admin/moderation/violations/confirm?message_id=' + messageId + '&sender_id=' + senderId;
}
</script>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
