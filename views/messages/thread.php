<?php
    /**
     * Message Thread View (secure attachments, robust polling)
     */
    require_once __DIR__ . '/../../src/Helpers.php';
    require_once __DIR__ . '/../../src/Services/FileService.php';

    $pageTitle   = 'Messages - Order #' . $order['id'];
    $userRole    = $_SESSION['user_role'] ?? 'guest';
    $fileService = new FileService();

    // Ensure initial attachments have signed URLs available
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
                                        <?php echo e($message['sender_name'] ?? explode('@', $message['sender_email'] ?? '')[0]) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="<?php echo $messageClass ?> rounded-lg px-4 py-3">
                                    <p class="whitespace-pre-wrap break-words"><?php echo e($message['content']) ?></p>

                                    <?php if (! empty($message['attachments'])): ?>
                                        <div class="mt-3 space-y-2">
                                            <?php foreach ($message['attachments'] as $attachment): ?>
                                                <?php if (empty($attachment['signed_url'])) {
                                                        continue;
                                                    }
                                                ?>
                                                <a href="<?php echo e($attachment['signed_url']) ?>"
                                                   target="_blank"
                                                   class="flex items-center space-x-2                                                                                                                                                                                                                                                                <?php echo $isOwnMessage ? 'text-blue-100 hover:text-white' : 'text-blue-600 hover:text-blue-700' ?>">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.586"/>
                                                    </svg>
                                                    <span class="text-sm underline">
                                                        <?php echo e($attachment['original_name'] ?? basename($attachment['path'])) ?>
                                                    </span>
                                                    <span class="text-xs text-gray-400">
                                                        <?php echo isset($attachment['size']) ? safe_number_format($attachment['size'] / 1024, 1) . ' KB' : '' ?>
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

                                <div class="text-xs text-gray-500 mt-1                                                                                                                                                                                                                   <?php echo $isOwnMessage ? 'text-right' : 'text-left' ?>">
                                    <?php echo date('M d, Y g:i A', strtotime($message['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Message Input Form -->
            <div class="border-t p-4">
                <?php if (in_array($order['status'], ['completed', 'cancelled'])): ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Messaging Closed</h3>
                        <p class="text-gray-600">
                            <?php if ($order['status'] === 'completed'): ?>
                                This order has been completed. Messaging is no longer available.
                            <?php else: ?>
                                This order has been cancelled. Messaging is no longer available.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <form action="/messages/send" method="POST" enctype="multipart/form-data" class="space-y-4" id="message-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="order_id" value="<?php echo e($order['id']) ?>">

                        <div>
                            <textarea
                                name="content"
                                id="message-content"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                placeholder="Type your message... (or attach files below)"
                            ></textarea>
                            <p class="text-xs text-gray-500 mt-1">You can send just attachments without text.</p>
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
                                        id="attachments-input"
                                        multiple
                                        class="hidden"
                                        accept=".jpg,.jpeg,.png,.gif,.pdf"
                                    >
                                </label>
                                <p class="text-xs text-gray-500 mt-1">Images (JPG, PNG, GIF) and PDFs only • Max 10MB per file</p>
                                <ul id="attachment-preview" class="mt-2 text-xs text-gray-600 space-y-1"></ul>
                            </div>

                            <button
                                type="submit"
                                id="send-button"
                                disabled
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600 flex items-center space-x-2"
                            >
                                <span id="send-button-text">Send Message</span>
                                <svg id="send-button-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const messageContent = document.getElementById('message-content');
const attachmentsInput = document.getElementById('attachments-input');
const sendButton = document.getElementById('send-button');
const attachmentPreview = document.getElementById('attachment-preview');

function updateSendButtonState() {
    const hasContent = messageContent && messageContent.value.trim().length > 0;
    const hasAttachments = attachmentsInput && attachmentsInput.files.length > 0;
    
    if (sendButton) {
        sendButton.disabled = !hasContent && !hasAttachments;
    }
}

// Listen for textarea input
messageContent?.addEventListener('input', updateSendButtonState);

// Listen for file selection
attachmentsInput?.addEventListener('change', function(e) {
    attachmentPreview.innerHTML = '';
    [...this.files].forEach(f => {
        const li = document.createElement('li');
        li.textContent = `${f.name} (${(f.size/1024).toFixed(1)} KB)`;
        attachmentPreview.appendChild(li);
    });
    updateSendButtonState();
});

// Handle form submission with loading state
document.getElementById('message-form')?.addEventListener('submit', function(e) {
    const buttonText = document.getElementById('send-button-text');
    const spinner = document.getElementById('send-button-spinner');
    
    // Disable button and show loading state
    sendButton.disabled = true;
    buttonText.textContent = 'Sending...';
    spinner.classList.remove('hidden');
});

function messageThread(orderId, currentUserId) {
    return {
        orderId: orderId,
        currentUserId: currentUserId,
        lastMessageId:                                                                   <?php echo ! empty($messages) ? (int) end($messages)['id'] : 0; ?>,
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
                        ${!isOwnMessage ? `<div class="text-xs text-gray-500 mb-1">${this.escapeHtml(message.sender_name || (message.sender_email ? message.sender_email.split('@')[0] : ''))}</div>` : ''}
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