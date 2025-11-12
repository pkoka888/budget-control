# Email Configuration Quick Start

## 📧 Email Service Setup

### Option 1: Gmail (Easiest for Testing)

1. **Enable 2-Factor Authentication** in your Google Account
2. **Generate App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and your device
   - Copy the 16-character password

3. **Configure .env:**
```bash
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Budget Control"
```

### Option 2: Office 365 / Outlook

```bash
MAIL_DRIVER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=your-email@outlook.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@outlook.com
MAIL_FROM_NAME="Budget Control"
```

### Option 3: SendGrid (Production Recommended)

1. Sign up at: https://sendgrid.com
2. Create API Key with "Mail Send" permissions
3. Configure:

```bash
MAIL_DRIVER=sendgrid
MAIL_API_KEY=your-sendgrid-api-key
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Budget Control"
```

### Option 4: Mailgun (Alternative)

```bash
MAIL_DRIVER=mailgun
MAIL_API_KEY=your-mailgun-api-key
MAILGUN_DOMAIN=yourdomain.mailgun.org
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Budget Control"
```

### Option 5: AWS SES (Enterprise)

```bash
MAIL_DRIVER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=eu-central-1
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Budget Control"
```

## 🧪 Testing Email Configuration

Create a test script:

```bash
cd budget-app
php -r "
require 'vendor/autoload.php';
\$service = new BudgetApp\Services\EmailService(
    new BudgetApp\Database(__DIR__ . '/database/budget.db'),
    new BudgetApp\Config(__DIR__)
);
echo \$service->send(
    'test@example.com',
    'Test Email',
    '<h1>Test</h1>',
    'Test'
) ? 'SUCCESS' : 'FAILED';
"
```

## 📝 Email Templates Location

Templates are stored in:
```
budget-app/templates/email/
├── household-invitation.html
├── household-invitation.txt
├── approval-request.html
├── approval-request.txt
├── chore-completed.html
└── chore-completed.txt
```

## ⚠️ Common Issues

### Gmail "Less secure app" error:
- ✅ Use App Password (not regular password)
- ✅ Enable 2FA first

### Port blocked by firewall:
- Try port 465 (SSL) instead of 587 (TLS)
- Change MAIL_ENCRYPTION=ssl

### Authentication failed:
- Check username/password are correct
- Ensure no extra spaces in .env file

### Rate limiting:
- Gmail: 500 emails/day
- SendGrid: 100 emails/day (free)
- Mailgun: 5,000 emails/month (free)

## 🚀 Production Recommendations

1. **Use SendGrid or Mailgun** (not Gmail)
2. **Set up SPF/DKIM records** for your domain
3. **Monitor email delivery rates**
4. **Implement email queue** for high volume
5. **Add bounce handling**
