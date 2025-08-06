# SMTP Email Configuration for AuthHub

To enable email functionality for the join request form, you need to configure SMTP settings in your `.env` file.

## Option 1: Mailtrap (Recommended for Development)

Mailtrap is a free SMTP testing service that's perfect for development and testing.

1. Sign up at [https://mailtrap.io](https://mailtrap.io) for a free account
2. Create a new inbox in your Mailtrap dashboard
3. Copy the SMTP credentials and add them to your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@authhub.dev"
MAIL_FROM_NAME="AuthHub"
```

## Option 2: Gmail SMTP (For Production)

If you want to use Gmail for sending emails:

1. Enable 2-factor authentication on your Gmail account
2. Generate an App Password: [https://support.google.com/accounts/answer/185833](https://support.google.com/accounts/answer/185833)
3. Add these settings to your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your_gmail@gmail.com"
MAIL_FROM_NAME="AuthHub"
```

## Option 3: Other SMTP Services

You can also use other free/paid SMTP services:

- **SendGrid**: Free tier with 100 emails/day
- **Mailgun**: Free tier with 5,000 emails/month
- **Amazon SES**: Pay-as-you-go pricing
- **Postmark**: Free tier with 100 emails/month

## Testing the Configuration

After setting up your SMTP configuration:

1. Run `php artisan config:cache` to cache the configuration
2. Visit the home page and submit a join request
3. Check your email inbox (or Mailtrap inbox) for the notification

## Important Notes

- The form will send emails to `dtavares86@gmail.com` as requested
- Make sure to replace placeholder values with your actual credentials
- For production, consider using environment-specific configurations
- Never commit your `.env` file to version control