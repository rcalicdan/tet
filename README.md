## Project Setup

### 1. Clone the Repository


### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration

Copy the example `.env` file and update the values accordingly:
```bash
cp .env.example .env
```

Update your `.env` with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=olejos_mobile_api
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

This sets the `APP_KEY` value in your `.env` file. The application will not run without it.

### 5. Configure Email Settings

This application uses SMTP for sending emails (e.g., email verification). For development, configure your `.env` to log emails instead of sending them:
```env
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**For Development:**
- `MAIL_MAILER=log` - Emails will be logged to `storage/logs/laravel.log` instead of being sent
- This is useful for testing without a real mail server

**For Production:**
- Use a real SMTP service (e.g., Gmail, SendGrid, Mailgun, AWS SES)
- Update `MAIL_MAILER=smtp` and provide valid credentials

**Example with Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourapp.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> **Note:** This application uses Laravel's `defer()` function to send emails in the background without requiring queue workers. Emails are sent after the HTTP response is returned to the user, providing a faster user experience.

### 6. Run Migrations

Creates all database tables defined in your migration files:
```bash
php artisan migrate
```

If you also have seeders and want to populate initial data:
```bash
php artisan migrate:fresh --seed
```

### 7. Set Up Passport

Install Passport's database tables:
```bash
php artisan passport:install
```

Then create a personal access client. This is required for token-based authentication when issuing tokens directly (e.g. login/register):
```bash
php artisan passport:client --personal
```

When prompted, you can press `Enter` to accept the default name (`Personal Access Client`). The generated client ID and secret will be stored in your database and referenced in your `.env`:
```env
PASSPORT_PERSONAL_CLIENT_ID=1
PASSPORT_PERSONAL_CLIENT_SECRET=<generated-secret>
```

> **Note:** If you already have these values in your `.env.example`, `passport:install` handles this automatically. Only run `passport:client --personal` manually if the personal client is missing or was deleted.

### 8. Start the Development Server
```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`.

---

## Viewing the API Documentation

To auto-generate docs on every request in development, set this in your `.env`:
```env
L5_SWAGGER_GENERATE_ALWAYS=true
```

Open the Swagger UI in your browser:
```
http://127.0.0.1:8000/api/documentation
```

> Disable `L5_SWAGGER_GENERATE_ALWAYS` in production.

---

## Email Configuration Details

### Development Setup (Recommended)

Use the `log` driver to preview emails in your log files:
```env
MAIL_MAILER=log
```

Emails will be written to `storage/logs/laravel.log`. You can tail the log to see emails as they're sent:
```bash
tail -f storage/logs/laravel.log
```

### Testing with Mailtrap (Optional)

For a better development experience, use [Mailtrap](https://mailtrap.io/):
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=null
```

### Production Setup

Use a reliable SMTP service:

**Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

**SendGrid:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
```

**Mailgun:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_mailgun_username
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
```

### Important Notes

- **No Queue Workers Required**: This application uses Laravel's `defer()` function to handle email sending in the background without needing Redis or queue workers.
- **Email Verification**: New user registrations will automatically receive a verification email.
- **Email Templates**: Verification emails use custom Polish language templates located in `resources/views/emails/`.

---

## Quick Reference

| Command | Description |
|---|---|
| `php artisan key:generate` | Generate the application encryption key |
| `php artisan migrate` | Run database migrations |
| `php artisan migrate:fresh --seed` | Re-run migrations and seed the database |
| `php artisan passport:install` | Install Passport database tables |
| `php artisan passport:client --personal` | Create a personal access client for token auth |
| `php artisan serve` | Start the local development server |
| `tail -f storage/logs/laravel.log` | View email logs in real-time (when using `log` mailer) |

### Email-Related Commands

| Command | Description |
|---|---|
| `php artisan config:clear` | Clear config cache after changing mail settings |
| `php artisan view:clear` | Clear compiled email template views |

---

## Troubleshooting

### Emails Not Sending

1. Check your `.env` mail configuration
2. Clear the config cache: `php artisan config:clear`
3. Check logs: `tail -f storage/logs/laravel.log`
4. Verify `storage/logs` directory is writable

### Email Verification Issues

1. Ensure `APP_URL` is set correctly in `.env`
2. Check that the email template exists: `resources/views/emails/verify-email-custom.blade.php`
3. Verify user's `email_verified_at` column exists in database

---