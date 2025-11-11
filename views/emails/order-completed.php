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
        .success { background-color: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Skills Marketplace</h1>
        </div>
        <div class="content">
            <h2>Order Completed!</h2>
            <p>Hello                     <?php echo e($recipient_name) ?>,</p>

            <div class="success">
                <strong>Congratulations!</strong> The order has been successfully completed.
            </div>

            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Service:</span>                                                               <?php echo e($service_title) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span> #<?php echo e($order_id) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Completed:</span>                                                                 <?php echo e($completed_at) ?>
                </div>
                <?php if (isset($earnings)): ?>
                <div class="detail-row">
                    <span class="detail-label">Your Earnings:</span> $<?php echo e(number_format($earnings, 2)) ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (isset($is_client) && $is_client): ?>
            <p>Thank you for using Student Skills Marketplace! We hope you're satisfied with the service.</p>
            <p style="text-align: center;">
                <a href="<?php echo e($review_url) ?>" class="button">Leave a Review</a>
            </p>
            <?php else: ?>
            <p>The payment has been processed and added to your balance. Great work!</p>
            <p style="text-align: center;">
                <a href="<?php echo e($order_url) ?>" class="button">View Order</a>
            </p>
            <?php endif; ?>
        </div>
        <div class="footer">
            <p>&copy;                      <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
