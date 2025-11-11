<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispute Resolved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f0fdf4; border-radius: 8px; padding: 30px; margin-bottom: 20px;">
        <h1 style="color: #16a34a; margin-top: 0;">Dispute Resolved</h1>
        <p>Hello <?php echo htmlspecialchars($user_name)?>,</p>
        <p>The dispute for order <strong>#<?php echo htmlspecialchars($order_id)?></strong> has been resolved by an administrator.</p>
    </div>

    <div style="background-color: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #1f2937; margin-top: 0;">Order Details</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; width: 40%;">Order ID:</td>
                <td style="padding: 8px 0; font-weight: bold;">#<?php echo htmlspecialchars($order_id)?></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Service:</td>
                <td style="padding: 8px 0; font-weight: bold;"><?php echo htmlspecialchars($service_title)?></td>
            </tr>
        </table>
    </div>

    <div style="background-color: #dbeafe; border: 1px solid #3b82f6; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h3 style="color: #1e40af; margin-top: 0;">Resolution</h3>
        <p style="margin: 0 0 10px 0; font-weight: bold; color: #1e3a8a;"><?php echo htmlspecialchars($resolution)?></p>
        <p style="margin: 0; color: #1e3a8a;"><?php echo nl2br(htmlspecialchars($resolution_notes))?></p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="<?php echo htmlspecialchars($order_url)?>" style="display: inline-block; background-color: #2563eb; color: #fff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: bold;">
            View Order
        </a>
    </div>

    <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 30px; color: #6b7280; font-size: 14px;">
        <p>If you have any questions about this resolution, please contact our support team.</p>
        <p style="margin-bottom: 0;">Best regards,<br>Student Skills Marketplace</p>
    </div>
</body>
</html>
