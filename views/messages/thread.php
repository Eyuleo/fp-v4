<?php
    /**
     * Message Thread View (secure attachments)
     */

    require_once __DIR__ . '/../../src/Helpers.php';
    require_once __DIR__ . '/../../src/Services/FileService.php';

    $pageTitle   = 'Messages - Order #' . $order['id'];
    $userRole    = $_SESSION['user_role'] ?? 'guest';
    $fileService = new FileService();

    // Signed URLs are pre-attached in controller for initial messages; fallback here if missing
    foreach ($messages as &$m) {
        if (! empty($m['attachments']) && is_array($m['attachments'])) {
            foreach ($m['attachments'] as &$a) {
                if (! empty($a['path']) && empty($a['signed_url'])) {
                    $a['signed_url'] = $fileService->generateSignedUrl($a['path'], 1800);
                }
            }
            unset($a);
        }
    }
    unset($m);

    include __DIR__ . '/../partials/navigation.php';
?>

<div class="pt-16 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Messages</h1>
                    <p class="text-gray-600 mt-1">
                        Order #<?php echo e($order['id']) ?> -<?php echo e($order['service_title']) ?>
                    </p>
                </div>
                <a href="/orders/<?php echo e($order['id']) ?>" class="text-blue-600 hover:text-blue-700">
                    View Order Details
                </a>
            </div>
        </div>

        <?php include __DIR__ . '/../partials/alert.php'; ?>

        <!-- Message Thread -->
        <div class="bg-white rounded-lg shadow-sm" x-data="messageThread(<?php echo e($order['id']) ?>,<?php echo e($user['id']) ?>)">
            <!-- Messages Container -->
            <div class="h-[500px] overflow-y-auto p-6 space-y-4" id="messages-container" x-ref="messagesContainer">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-gray-500 py-12">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.307-4.571A9.978 9.978 0 013 12c0-5.523 4.477-10 10-10s10 4.477 10 10z"/>
                        </svg>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <?php
                            $isOwnMessage = $message['sender_id'] == $user['id'];
                            $messageClass = $isOwnMessage ? 'ml-auto bg-blue-600 text-white' : 'mr-auto bg-gray-100 text-gray-900';
                            $alignClass   = $isOwnMessage ? 'justify-end' : 'justify-start';
                        ?>
                        <div class="flex<?php echo $alignClass ?>" data-message-id="<?php echo e($message['id']) ?>">
                            <div class="max-w-[70%]">
                                <?php if (! $isOwnMessage): ?>
                                    <div class="text-xs text-gray-500 mb-1">
                                        <?php echo e($message['sender_name'] ?? $message['sender_email']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="<?php echo $messageClass ?> rounded-lg px-4 py-3">
                                    <p class="whitespace-pre-wrap break-words"><?php echo e($message['content']) ?></p>

                                    <?php if (! empty($message['attachments'])): ?>
                                        <div class="mt-3 space-y-2">
                                            <?php foreach ($message['attachments'] as $attachment): ?>
                                                <?php
                                                    if (empty($attachment['signed_url'])) {
                                                        continue;
                                                    }
                                                ?>
                                                <a href="<?php echo e($attachment['signed_url']) ?>"
                                                   target="_blank"
                                                   class="flex items-center space-x-2                                                                                      <?php echo $isOwnMessage ? 'text-blue-100 hover:text-white' : 'text-blue-600 hover:text-blue-700' ?>">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.586"/>
                                                    </svg>
                                                    <span class="text-sm underline">
                                                        <?php echo e($attachment['original_name'] ?? basename($attachment['path'])) ?>
                                                    </span>
                                                    <span class="text-xs text-gray-400">
                                                        <?php echo isset($attachment['size']) ? number_format($attachment['size'] / 1024, 1) . ' KB' : '' ?>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (! empty($message['is_flagged']) && $user['role'] === 'admin'): ?>
                                        <div class="mt-2 text-xs text-yellow-300">
                                            ⚠️ Flagged for review
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="text-xs text-gray-500 mt-1                                                                       <?php echo $isOwnMessage ? 'text-right' : 'text-left' ?>">
                                    <?php echo date('M d, Y g:i A', strtotime($message['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Message Input Form -->
            <div class="border-t p-4">
                <form action="/messages/send" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="order_id" value="<?php echo e($order['id']) ?>">

                    <div>
                        <textarea
                            name="content"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                            placeholder="Type your message..."
                            required
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="flex items-center space-x-2 text-sm text-gray-600 cursor-pointer hover:text-gray-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.586"/>
                                </svg>
                                <span>Attach files</span>
                                <input
                                    type="file"
                                    name="attachments[]"
                                    multiple
                                    class="hidden"
                                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip"
                                >
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Max 10MB per file</p>
                            <ul id="attachment-preview" class="mt-2 text-xs text-gray-600 space-y-1"></ul>
                        </div>

                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('input[name="attachments[]"]').addEventListener('change', function (e) {
    const list = document.getElementById('attachment-preview');
    list.innerHTML = '';
    [...this.files].forEach(f => {
        const li = document.createElement('li');
        li.textContent = `${f.name} (${(f.size/1024).toFixed(1)} KB)`;
        list.appendChild(li);
    });
});

function messageThread(orderId, currentUserId) {
    return {
        orderId: orderId,
        currentUserId: currentUserId,
        lastMessageId:                       <?php echo ! empty($messages) ? (int) end($messages)['id'] : 0; ?>,
        pollingInterval: null,

        init() {
            this.scrollToBottom();
            this.pollingInterval = setInterval(() => {
                this.pollForNewMessages();
            }, 10000);
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        async pollForNewMessages() {
            try {
                const response = await fetch(`/messages/poll?order_id=${this.orderId}&after=${this.lastMessageId}`);
                const data = await response.json();

                if (data.success && data.messages && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        this.appendMessage(message);
                        this.lastMessageId = message.id;
                    });
                    this.scrollToBottom();
                }
            } catch (error) {
                console.error('Error polling for messages:', error);
            }
        },

        appendMessage(message) {
            const container = this.$refs.messagesContainer;
            const isOwnMessage = message.sender_id == this.currentUserId;
            const messageClass = isOwnMessage ? 'ml-auto bg-blue-600 text-white' : 'mr-auto bg-gray-100 text-gray-900';
            const alignClass = isOwnMessage ? 'justify-end' : 'justify-start';

            let attachmentsHtml = '';
            if (message.attachments && message.attachments.length > 0) {
                attachmentsHtml = '<div class="mt-3 space-y-2">';
                message.attachments.forEach(attachment => {
                    if (!attachment.signed_url) return;
                    const linkClass = isOwnMessage ? 'text-blue-100 hover:text-white' : 'text-blue-600 hover:text-blue-700';
                    const name = attachment.original_name || (attachment.path || '').split('/').pop();
                    attachmentsHtml += `
                        <a href="${attachment.signed_url}" target="_blank" class="flex items-center space-x-2 ${linkClass}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.586"/>
                            </svg>
                            <span class="text-sm underline">${this.escapeHtml(name)}</span>
                        </a>
                    `;
                });
                attachmentsHtml += '</div>';
            }

            const messageHtml = `
                <div class="flex ${alignClass}" data-message-id="${message.id}">
                    <div class="max-w-[70%]">
                        ${!isOwnMessage ? `<div class="text-xs text-gray-500 mb-1">${this.escapeHtml(message.sender_name || message.sender_email || '')}</div>` : ''}
                        <div class="${messageClass} rounded-lg px-4 py-3">
                            <p class="whitespace-pre-wrap break-words">${this.escapeHtml(message.content)}</p>
                            ${attachmentsHtml}
                        </div>
                        <div class="text-xs text-gray-500 mt-1 ${isOwnMessage ? 'text-right' : 'text-left'}">
                            ${new Date(message.created_at).toLocaleString()}
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', messageHtml);
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    }
}
</script>