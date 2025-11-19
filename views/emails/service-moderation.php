<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service <?php echo ucfirst($action) ?></title>
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
            background-color: <?php echo $action === 'rejected' ? '#dc3545' : '#007bff' ?>;
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
        .service-info {
            background-color: #f8f9fa;
            border-left: 4px solid <?php echo $action === 'rejected' ? '#dc3545' : '#007bff' ?>;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .service-info strong {
            display: block;
            margin-bottom: 5px;
            color: #495057;
        }
        .reason-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .reason-box h3 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 16px;
        }
        .reason-box p {
            margin: 0;
            color: #856404;
        }
        .guidance-box {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .guidance-box h3 {
            margin: 0 0 10px 0;
            color: #004085;
            font-size: 16px;
        }
        .guidance-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .guidance-box li {
            margin: 5px 0;
            color: #004085;
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
            <h1>Service <?php echo ucfirst($action) ?></h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($student_name) ?>,</p>

            <?php if ($action === 'rejected'): ?>
                <p>We've reviewed your service listing and unfortunately it doesn't meet our current platform guidelines. We want to help you get it approved!</p>

                <div class="service-info">
                    <strong>Service Title:</strong>
                    <?php echo htmlspecialchars($service_title) ?>
                </div>

                <div class="reason-box">
                    <h3>📋 Reason for Rejection</h3>
                    <p><?php echo nl2br(htmlspecialchars($reason)) ?></p>
                </div>

                <div class="guidance-box">
                    <h3>✅ How to Get Your Service Approved</h3>
                    <p>Follow these steps to address the issues and resubmit your service:</p>
                    <ul>
                        <li><strong>Review the feedback:</strong> Carefully read the rejection reason above to understand what needs to be changed.</li>
                        <li><strong>Edit your service:</strong> Click the button below to access your service and make the necessary updates.</li>
                        <li><strong>Address all concerns:</strong> Make sure to fully address each point mentioned in the rejection reason.</li>
                        <li><strong>Save your changes:</strong> Once you save your updated service, it will automatically be resubmitted for review.</li>
                        <li><strong>Wait for approval:</strong> Our team will review your resubmission and notify you of the outcome.</li>
                    </ul>
                </div>

                <p><strong>Need help?</strong> If you have questions about the rejection or need clarification on our guidelines, please don't hesitate to contact our support team.</p>

                <center>
                    <a href="<?php echo htmlspecialchars($service_url) ?>" class="button">Edit My Service</a>
                </center>

            <?php elseif ($action === 'approved'): ?>
                <p>Great news! Your service listing has been approved and is now live on the platform.</p>

                <div class="service-info">
                    <strong>Service Title:</strong>
                    <?php echo htmlspecialchars($service_title) ?>
                </div>

                <p>Your service is now visible to clients and you can start receiving orders. Make sure to:</p>
                <ul>
                    <li>Keep your service description up to date</li>
                    <li>Respond promptly to client inquiries</li>
                    <li>Deliver high-quality work on time</li>
                </ul>

                <center>
                    <a href="<?php echo htmlspecialchars($service_url) ?>" class="button">View My Service</a>
                </center>

            <?php else: ?>
                <p>Your service listing has been <?php echo $action ?> by an administrator.</p>

                <div class="service-info">
                    <strong>Service Title:</strong>
                    <?php echo htmlspecialchars($service_title) ?><br>
                    <strong>Action:</strong>
                    <?php echo ucfirst($action) ?><br>
                    <?php if (!empty($reason)): ?>
                        <strong>Reason:</strong>
                        <?php echo htmlspecialchars($reason) ?>
                    <?php endif; ?>
                </div>

                <center>
                    <a href="<?php echo htmlspecialchars($service_url) ?>" class="button">View My Services</a>
                </center>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>This is an automated message from Student Skills Marketplace.</p>
            <p>Please do not reply to this email. If you have questions, please contact our support team.</p>
        </div>
    </div>
</body>
</html>
