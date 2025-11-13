<?php
    /**
     * Notification Center View
     */

    // Get notification type icons
    function getNotificationIcon($type)
    {
        $icons = [
            'order_placed'       => '📦',
            'order_delivered'    => '✅',
            'order_completed'    => '🎉',
            'revision_requested' => '🔄',
            'message_received'   => '💬',
            'review_submitted'   => '⭐',
            'payment_received'   => '💰',
            'dispute_opened'     => '⚠️',
            'default'            => '🔔',
        ];

        return $icons[$type] ?? $icons['default'];
    }

    // Get notification type color
    function getNotificationColor($type)
    {
        $colors = [
            'order_placed'       => 'blue',
            'order_delivered'    => 'green',
            'order_completed'    => 'green',
            'revision_requested' => 'yellow',
            'message_received'   => 'blue',
            'review_submitted'   => 'yellow',
            'payment_received'   => 'green',
            'dispute_opened'     => 'red',
            'default'            => 'gray',
        ];

        return $colors[$type] ?? $colors['default'];
    }

    // Ensure a CSRF token exists and expose it to the page
    $csrfToken = CsrfMiddleware::getToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Student Skills Marketplace</title>
    <meta name="csrf-token" content="<?php echo e($csrfToken) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require __DIR__ . '/../partials/navigation.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
                <p class="text-gray-600 mt-1">
                    <?php if ($unreadCount > 0): ?>
                        You have<?php echo $unreadCount ?> unread notification<?php echo $unreadCount !== 1 ? 's' : '' ?>
                    <?php else: ?>
                        You're all caught up!
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($unreadCount > 0): ?>
            <button
                onclick="markAllAsRead()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
                Mark All as Read
            </button>
            <?php endif; ?>
        </div>

        <!-- Notifications List -->
        <div class="space-y-3">
            <?php if (empty($notifications)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-6xl mb-4">🔔</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No notifications yet</h3>
                    <p class="text-gray-600">When you receive notifications, they'll appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <?php
                        $isUnread = ! $notification['is_read'];
                        $icon     = getNotificationIcon($notification['type']);
                        $color    = getNotificationColor($notification['type']);
                        $bgClass  = $isUnread ? 'bg-blue-50 border-l-4 border-blue-500' : 'bg-white';
                    ?>
                    <div
                        class="<?php echo $bgClass ?> rounded-lg shadow p-4 hover:shadow-md transition notification-item"
                        data-notification-id="<?php echo $notification['id'] ?>"
                        data-is-read="<?php echo $notification['is_read'] ? 'true' : 'false' ?>"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="text-3xl flex-shrink-0">
                                <?php echo $icon ?>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 mb-1">
                                            <?php echo e($notification['title']) ?>
                                            <?php if ($isUnread): ?>
                                                <span class="inline-block w-2 h-2 bg-blue-600 rounded-full ml-2"></span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-gray-700 mb-2">
                                            <?php echo e($notification['message']) ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php
                                                $time = strtotime($notification['created_at']);
                                                $diff = time() - $time;

                                                if ($diff < 60) {
                                                    echo 'Just now';
                                                } elseif ($diff < 3600) {
                                                    echo floor($diff / 60) . ' minutes ago';
                                                } elseif ($diff < 86400) {
                                                    echo floor($diff / 3600) . ' hours ago';
                                                } elseif ($diff < 604800) {
                                                    echo floor($diff / 86400) . ' days ago';
                                                } else {
                                                    echo date('M j, Y', $time);
                                                }
                                            ?>
                                        </p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-2">
                                        <?php if ($notification['link']): ?>
                                            <a
                                                href="<?php echo e($notification['link']) ?>"
                                                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                                                onclick="markAsRead(<?php echo $notification['id'] ?>)"
                                            >
                                                View
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($isUnread): ?>
                                            <button
                                                onclick="markAsRead(<?php echo $notification['id'] ?>)"
                                                class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 transition"
                                                title="Mark as read"
                                            >
                                                ✓
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Mark single notification as read
        function markAsRead(notificationId) {
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch('/notifications/mark-as-read', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const notificationEl = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (notificationEl && notificationEl.dataset.isRead === 'false') {
                        notificationEl.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500');
                        notificationEl.classList.add('bg-white');
                        notificationEl.dataset.isRead = 'true';

                        // Remove unread badge
                        const badge = notificationEl.querySelector('.bg-blue-600.rounded-full');
                        if (badge) badge.remove();

                        // Remove mark as read button
                        const markBtn = notificationEl.querySelector('button[title="Mark as read"]');
                        if (markBtn) markBtn.remove();
                    }

                    // Update unread count in navigation
                    updateUnreadCount(data.unread_count);
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }

        // Mark all notifications as read
        function markAllAsRead() {
            if (!confirm('Mark all notifications as read?')) {
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);

            fetch('/notifications/mark-all-as-read', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated state
                    window.location.reload();
                }
            })
            .catch(error => console.error('Error marking all as read:', error));
        }

        // Update unread count in navigation
        function updateUnreadCount(count) {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        }
    </script>
</body>
</html>