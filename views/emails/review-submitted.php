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
        .rating { color: #fbbf24; font-size: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Skills Marketplace</h1>
        </div>
        <div class="content">
            <h2>New Review Received</h2>
            <p>Hello                     <?php echo e($student_name) ?>,</p>
            <p>You have received a new review from                                                   <?php echo e($client_name) ?>.</p>

            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Service:</span>                                                               <?php echo e($service_title) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span> #<?php echo e($order_id) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Rating:</span>
                    <span class="rating" style="color: #fbbf24; font-size: 20px;">
                        <?php for ($i = 0; $i < $rating; $i++): ?>&#9733;<?php endfor; ?>
                        <?php for ($i = $rating; $i < 5; $i++): ?>&#9734;<?php endfor; ?>
                    </span>
                    (<?php echo e($rating) ?>/5)
                </div>
            </div>

            <?php if (! empty($comment)): ?>
            <p><strong>Review Comment:</strong></p>
            <p style="background-color: white; padding: 15px; border-radius: 5px;"><?php echo nl2br(e($comment)) ?></p>
            <?php endif; ?>

            <p style="text-align: center;">
                <a href="<?php echo e($review_url) ?>" class="button">View & Reply to Review</a>
            </p>

            <p>You can reply to this review to thank the client or provide additional context.</p>
        </div>
        <div class="footer">
            <p>&copy;                      <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
