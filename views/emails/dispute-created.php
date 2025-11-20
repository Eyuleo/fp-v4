<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc2626; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9fafb; padding: 30px; }
        .button { display: inline-block; background-color: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
        .dispute-details { background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .detail-row { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-label { font-weight: bold; color: #6b7280; }
        .warning { background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Skills Marketplace</h1>
        </div>
        <div class="content">
            <h2>Dispute Opened</h2>
            <p>Hello <?php echo e($recipient_name) ?>,</p>
            <p>A dispute has been opened for an order.</p>

            <div class="dispute-details">
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span> #<?php echo e($order_id) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service:</span> <?php echo e($service_title) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Opened By:</span> <?php echo e($opener_name) ?>
                </div>
            </div>

            <p><strong>Dispute Reason:</strong></p>
            <p style="background-color: white; padding: 15px; border-radius: 5px;"><?php echo nl2br(e($reason)) ?></p>

            <div class="warning">
                <p><strong>What happens next?</strong></p>
                <p>An administrator will review the dispute and all order details. Both parties will be notified once a decision has been made.</p>
            </div>

            <p style="text-align: center;">
                <a href="<?php echo e($dispute_url) ?>" class="button">View Dispute Details</a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
