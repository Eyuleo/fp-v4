<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service                   <?php echo ucfirst($action) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Service                    <?php echo ucfirst($action) ?></h2>
    </div>

    <div class="content">
        <p>Hello                 <?php echo htmlspecialchars($student_name) ?>,</p>

        <p>Your service listing has been                                         <?php echo $action ?> by an administrator.</p>

        <div class="alert">
            <strong>Service:</strong>                                      <?php echo htmlspecialchars($service_title) ?><br>
            <strong>Action:</strong>                                     <?php echo ucfirst($action) ?><br>
            <strong>Reason:</strong>                                     <?php echo htmlspecialchars($reason) ?>
        </div>

        <?php if ($action === 'activated'): ?>
            <p>Congratulations! Your service is now active and visible to clients on the platform.</p>
        <?php elseif ($action === 'deactivated'): ?>
            <p>Your service is no longer visible to clients. You can review and update your service listing if needed.</p>
        <?php elseif ($action === 'deleted'): ?>
            <p>Your service has been permanently removed from the platform. If you believe this was done in error, please contact support.</p>
        <?php endif; ?>

        <a href="<?php echo htmlspecialchars($service_url) ?>" class="button">View My Services</a>
    </div>

    <div class="footer">
        <p>This is an automated message from Student Skills Marketplace. Please do not reply to this email.</p>
        <p>If you have questions, please contact our support team.</p>
    </div>
</body>
</html>
