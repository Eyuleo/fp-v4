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
        .warning { background-color: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Skills Marketplace</h1>
        </div>
        <div class="content">
            <h2>Order Cancelled</h2>
            <p>Hello                     <?php echo e($recipient_name) ?>,</p>

            <div class="warning">
                Order #<?php echo e($order_id) ?> has been cancelled.
            </div>

            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Service:</span>                                                               <?php echo e($service_title) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span> #<?php echo e($order_id) ?>
                </div>
                <?php if (isset($cancelled_at)): ?>
                <div class="detail-row">
                    <span class="detail-label">Cancelled:</span>                                                                 <?php echo e($cancelled_at) ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (isset($cancellation_reason) && ! empty($cancellation_reason)): ?>
            <p><strong>Cancellation Reason:</strong></p>
            <p style="background-color: white; padding: 15px; border-radius: 5px;"><?php echo nl2br(e($cancellation_reason)) ?></p>
            <?php endif; ?>

            <?php if (isset($is_client) && $is_client): ?>
            <p>A full refund has been processed and will appear in your account within 5-10 business days.</p>
            <?php else: ?>
            <p>The order has been cancelled by the client. No payment will be processed for this order.</p>
            <?php endif; ?>

            <p style="text-align: center;">
                <a href="<?php echo e($order_url) ?>" class="button">View Order Details</a>
            </p>
        </div>
        <div class="footer">
            <p>&copy;                      <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
