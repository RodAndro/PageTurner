# Email Configuration Guide for Gmail SMTP

## Problem
You're seeing: `Username and Password not accepted` when trying to send emails.

## Solution Options

### Option 1: Use Gmail App Password (Recommended)

1. **Enable 2-Step Verification on your Google Account**
   - Go to: https://myaccount.google.com/security
   - Under "How you sign in to Google", enable "2-Step Verification"

2. **Generate an App Password**
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Name it "Laravel PageTurner"
   - Copy the 16-character password

3. **Update your `.env` file**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=rodandro57@gmail.com
   MAIL_PASSWORD=your-16-char-app-password-here
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=rodandro57@gmail.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

4. **Clear config cache**
   ```bash
   php artisan config:clear
   ```

---

### Option 2: Use Mailtrap for Development (Easy Testing)

1. **Sign up at https://mailtrap.io (Free)**

2. **Get your credentials from Mailtrap inbox**

3. **Update your `.env` file**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your-mailtrap-username
   MAIL_PASSWORD=your-mailtrap-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@pageturner.local
   MAIL_FROM_NAME="${APP_NAME}"
   ```

4. **Clear config cache**
   ```bash
   php artisan config:clear
   ```

**Benefit:** All emails are caught by Mailtrap - perfect for testing!

---

### Option 3: Use Log Driver (Quick Testing - No Email Sent)

For quick testing without actual email:

**Update your `.env` file**
```env
MAIL_MAILER=log
```

**Clear config cache**
```bash
php artisan config:clear
```

**View emails:** Check `storage/logs/laravel.log`

---

## Quick Test

After configuring, test email with:

```bash
php artisan tinker
```

Then run:
```php
Mail::raw('Test email', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

If no errors appear, your email is configured correctly!

---

## Current Changes Made

The app has been updated to handle email failures gracefully:
- ✅ Login won't crash if email fails
- ✅ Error messages will display instead
- ✅ You can still use recovery codes
- ⚠️ 2FA codes won't be sent until email is configured

Choose one of the options above to fix email sending!
