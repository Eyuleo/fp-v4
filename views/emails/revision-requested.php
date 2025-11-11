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
        .warning { background-color: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Skills Marketplace</h1>
        </div>
        <div class="content">
            <h2>Revision Requested</h2>
            <p>Hello                     <?php echo e($student_name) ?>,</p>
            <p>The client has requested revisions for your delivered work.</p>

            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Service:</span>                                                               <?php echo e($service_title) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span> #<?php echo e($order_id) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Revision Count:</span>                                                                      <?php echo e($revision_count) ?> of<?php echo e($max_revisions) ?>
                </div>
            </div>

            <p><strong>Revision Reason:</strong></p>
            <p style="background-color: white; padding: 15px; border-radius: 5px;"><?php echo nl2br(e($revision_reason)) ?></p>

            <?php if ($revision_count >= $max_revisions): ?>
            <div class="warning">
                <strong>Note:</strong> This is the final revision allowed for this order.
            </div>
            <?php endif; ?>

            <p style="text-align: center;">
                <a href="<?php echo e($order_url) ?>" class="button">View Order & Redeliver</a>
            </p>

            <p>Please review the feedback and submit your revised work.</p>
        </div>
        <div class="footer">
            <p>&copy;                      <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
