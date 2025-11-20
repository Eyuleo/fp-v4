<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #059669; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9fafb; padding: 30px; }
        .button { display: inline-block; background-color: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
        .resolution-details { background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .detail-row { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-label { font-weight: bold; color: #6b7280; }
        .info-box { background-color: #ecfdf5; border-left: 4px solid #059669; padding: 15px; margin: 15px 0; }
        .financial-summary { background-color: #f0fdf4; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Skills Marketplace</h1>
        </div>
        <div class="content">
            <h2>Dispute Resolved</h2>
            <p>Hello <?php echo e($recipient_name) ?>,</p>
            <p>The dispute for order #<?php echo e($order_id) ?> has been resolved by an administrator.</p>

            <div class="resolution-details">
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span> #<?php echo e($order_id) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service:</span> <?php echo e($service_title) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Resolution:</span> <?php echo e(ucwords(str_replace('_', ' ', $resolution))) ?>
                </div>
            </div>

            <p><strong>Administrator's Decision:</strong></p>
            <p style="background-color: white; padding: 15px; border-radius: 5px;"><?php echo nl2br(e($resolution_notes)) ?></p>

            <?php if ($refund_amount > 0 || $student_payment > 0): ?>
            <div class="financial-summary">
                <h3 style="margin-top: 0;">Financial Outcome</h3>
                <?php if ($refund_amount > 0): ?>
                <div class="detail-row">
                    <span class="detail-label">Refund to Client:</span> $<?php echo e(safe_number_format($refund_amount, 2)) ?>
                </div>
                <?php endif; ?>
                <?php if ($student_payment > 0): ?>
                <div class="detail-row">
                    <span class="detail-label">Payment to Student:</span> $<?php echo e(safe_number_format($student_payment, 2)) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="info-box">
                <?php if ($resolution === 'release_to_student'): ?>
                <p><strong>Payment Released:</strong> The full payment has been released to the student's account.</p>
                <?php elseif ($resolution === 'refund_to_client'): ?>
                <p><strong>Refund Processed:</strong> A full refund has been issued to the client's payment method.</p>
                <?php elseif ($resolution === 'partial_refund'): ?>
                <p><strong>Partial Resolution:</strong> A partial refund has been issued to the client, and the remaining amount has been released to the student.</p>
                <?php endif; ?>
            </div>

            <p style="text-align: center;">
                <a href="<?php echo e($order_url) ?>" class="button">View Order Details</a>
            </p>

            <p>If you have any questions about this resolution, please contact our support team.</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
