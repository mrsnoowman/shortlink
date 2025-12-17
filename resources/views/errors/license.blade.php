<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>License Error - Application Unavailable</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #1a202c;
            font-size: 28px;
            margin-bottom: 16px;
            font-weight: 700;
        }
        .message {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .error-code {
            background: #fed7d7;
            color: #c53030;
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f7fafc;
            border-left: 4px solid #4299e1;
            padding: 16px;
            border-radius: 4px;
            text-align: left;
            margin-top: 20px;
        }
        .info-box p {
            color: #2d3748;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .info-box p:last-child {
            margin-bottom: 0;
        }
        .action {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }
        .action p {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
        }
        code {
            background: #edf2f7;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <h1>Application Unavailable</h1>
        <div class="error-code">ERROR: Invalid License Key</div>
        <div class="message">
            {{ $message ?? 'License key was not found or has expired. The application cannot run.' }}
        </div>
        
        <div class="info-box">
            <p><strong>Possible causes:</strong></p>
            <p>• License key is not present on the system</p>
            <p>• License key has expired</p>
            <p>• License key is invalid or corrupted</p>
        </div>

        <div class="action">
            <p><strong>How to fix:</strong></p>
            <p>Contact the administrator to obtain a new license key or renew the existing license.</p>
            <p style="margin-top: 15px;">
                If you are the administrator, run the following command in your terminal:
            </p>
            <p style="margin-top: 10px;">
                <code>php artisan license:generate</code>
            </p>
        </div>
    </div>
</body>
</html>

