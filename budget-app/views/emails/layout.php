<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $subject ?? 'Budget Control'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f7fafc;
            padding: 20px;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .tagline {
            font-size: 14px;
            opacity: 0.9;
        }

        .email-body {
            padding: 30px 20px;
            color: #2d3748;
        }

        .email-footer {
            background-color: #f7fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #667eea;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }

        .button:hover {
            background-color: #5568d3;
        }

        .stats-box {
            background-color: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .alert-box {
            background-color: #fff5f5;
            border-left: 4px solid #f56565;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .success-box {
            background-color: #f0fff4;
            border-left: 4px solid #48bb78;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .info-box {
            background-color: #ebf8ff;
            border-left: 4px solid #4299e1;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        h1 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 24px;
        }

        h2 {
            color: #2d3748;
            margin: 20px 0 10px 0;
            font-size: 20px;
        }

        p {
            margin-bottom: 15px;
        }

        .footer-links {
            margin-top: 10px;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }

            .email-header {
                padding: 20px 15px;
            }

            .email-body {
                padding: 20px 15px;
            }

            .logo {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="logo">💰 Budget Control</div>
            <div class="tagline">Your Personal Financial Assistant</div>
        </div>

        <!-- Body -->
        <div class="email-body">
            <?php echo $content; ?>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>
                This email was sent by Budget Control.<br>
                You're receiving this because you have notifications enabled.
            </p>
            <div class="footer-links">
                <a href="<?php echo $_ENV['APP_URL'] ?? 'https://budget.yourdomain.com'; ?>/settings">Settings</a> |
                <a href="<?php echo $_ENV['APP_URL'] ?? 'https://budget.yourdomain.com'; ?>/settings/notifications">Unsubscribe</a> |
                <a href="<?php echo $_ENV['APP_URL'] ?? 'https://budget.yourdomain.com'; ?>">Dashboard</a>
            </p>
            <p style="margin-top: 15px; color: #a0aec0;">
                © <?php echo date('Y'); ?> Budget Control. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
