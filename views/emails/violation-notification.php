<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Violation - Action Taken</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #dc3545;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 20px;
        }
        .violation-info {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .violation-info strong {
            display: block;
            margin-bottom: 5px;
            color: #721c24;
        }
        .violation-info p {
            margin: 5px 0;
            color: #721c24;
        }
        .penalty-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .penalty-box h3 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 16px;
        }
        .penalty-box p {
            margin: 5px 0;
            color: #856404;
        }
        .notes-box {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .notes-box h3 {
            margin: 0 0 10px 0;
            color: #004085;
            font-size: 16px;
        }
        .notes-box p {
            margin: 0;
            color: #004085;
        }
        .guidance-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .guidance-box h3 {
            margin: 0 0 10px 0;
            color: #155724;
            font-size: 16px;
        }
        .guidance-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .guidance-box li {
            margin: 5px 0;
            color: #155724;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: 600;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Policy Violation - Action Taken</h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($user_name) ?>,</p>

            <p>We are writing to inform you that a policy violation has been confirmed on your account. Our moderation team has reviewed the flagged content and determined that it violates our platform policies.</p>

            <div class="violation-info">
                <strong>Violation Details:</strong>
                <p><strong>Type:</strong> <?php echo htmlspecialchars($violation_type) ?></p>
                <p><strong>Severity:</strong> <?php echo htmlspecialchars($severity) ?></p>
            </div>

            <div class="penalty-box">
                <h3>⚖️ Penalty Applied</h3>
                <p><strong><?php echo htmlspecialchars($penalty_type) ?></strong></p>
                <p><?php echo htmlspecialchars($duration) ?></p>
            </div>

            <?php if (!empty($admin_notes)): ?>
                <div class="notes-box">
                    <h3>📝 Administrator Notes</h3>
                    <p><?php echo nl2br(htmlspecialchars($admin_notes)) ?></p>
                </div>
            <?php endif; ?>

            <div class="guidance-box">
                <h3>📖 Platform Policies</h3>
                <p>To maintain a safe and trustworthy marketplace, we enforce the following policies:</p>
                <ul>
                    <li><strong>No off-platform communication:</strong> All communication must occur through our platform messaging system</li>
                    <li><strong>No payment circumvention:</strong> All payments must be processed through our secure payment system</li>
                    <li><strong>Professional conduct:</strong> Maintain respectful and professional communication at all times</li>
                </ul>
                <p><strong>Future violations may result in more severe penalties, including permanent account suspension.</strong></p>
            </div>

            <p>If you believe this action was taken in error or would like to appeal this decision, please contact our support team through your account settings.</p>

            <center>
                <a href="<?php echo htmlspecialchars($appeal_url) ?>" class="button">View Account Settings</a>
            </center>
        </div>

        <div class="footer">
            <p>This is an automated message from Student Skills Marketplace.</p>
            <p>Please do not reply to this email. If you have questions, please contact our support team.</p>
        </div>
    </div>
</body>
</html>
