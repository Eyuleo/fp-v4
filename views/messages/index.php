<?php
    /**
     * Messages Index View
     *
     * Displays all message conversations for the authenticated user
     */

    $title = 'Messages - Student Skills Marketplace';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Messages</h1>
        <p class="text-gray-600 mt-2">View and manage your conversations</p>
    </div>

    <?php if (empty($conversations)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No messages yet</h3>
            <p class="text-gray-600">Your message conversations will appear here</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="divide-y divide-gray-200">
                <?php foreach ($conversations as $conversation): ?>
                    <a href="/messages/thread/<?php echo $conversation['order_id'] ?>"
                       class="block hover:bg-gray-50 transition-colors duration-150">
                        <div class="p-4 sm:p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center mb-2">
                                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                            <?php echo strtoupper(substr($conversation['other_user_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($conversation['other_user_name'] ?? 'Unknown User') ?>
                                            </h3>
                                            <p class="text-xs text-gray-500">
                                                Order #<?php echo $conversation['order_id'] ?>
                                                <?php if (! empty($conversation['service_title'])): ?>
                                                    -<?php echo htmlspecialchars($conversation['service_title']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if (! empty($conversation['last_message'])): ?>
                                        <p class="text-sm text-gray-600 truncate">
                                            <?php echo htmlspecialchars($conversation['last_message']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (! empty($conversation['last_message_time'])): ?>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <?php echo date('M j, Y g:i A', strtotime($conversation['last_message_time'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php if (! empty($conversation['unread_count']) && $conversation['unread_count'] > 0): ?>
                                    <div class="ml-4">
                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full">
                                            <?php echo $conversation['unread_count'] ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
