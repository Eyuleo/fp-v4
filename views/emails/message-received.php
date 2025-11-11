<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9fafb; padding: 30px; }
        .button { display: inline-block; background-color: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
        .order-details { background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .detail-row { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-label { font-weight: bold; color: #6b7280; }
        .message-preview { background-color: white; padding: 15px; border-radius: 5px; border-left: 4px solid #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Skills Marketplace</h1>
        </div>
        <div class="content">
            <h2>New Message Received</h2>
            <p>Hello                     <?php echo e($recipient_name) ?>,</p>
            <p>You have received a new message from                                                    <?php echo e($sender_name) ?>.</p>

            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span> #<?php echo e($order_id) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service:</span>                                                               <?php echo e($service_title) ?>
                </div>
            </div>

            <p><strong>Message Preview:</strong></p>
            <div class="message-preview">
                <?php
                    $preview = strlen($message_content) > 200
                        ? substr($message_content, 0, 200) . '...'
                        : $message_content;
                    echo nl2br(e($preview));
                ?>
            </div>

            <?php if (isset($has_attachments) && $has_attachments): ?>
            <p><em>This message includes attachments.</em></p>
            <?php endif; ?>

            <p style="text-align: center;">
                <a href="<?php echo e($message_url) ?>" class="button">View & Reply</a>
            </p>

            <p>Click the button above to view the full message and reply.</p>
        </div>
        <div class="footer">
            <p>&copy;                      <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
