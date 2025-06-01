# MorningNewsletter.com

A powerful SAAS platform for creating and managing custom email newsletters with dynamic content and automated delivery.

## Features

- User authentication and management
- Custom newsletter creation with dynamic sections
- Multiple content integrations:
  - Weather forecasts
  - News headlines
  - Stripe sales reports
  - App Store revenue
  - GitHub activity
  - Calendar events
- Flexible delivery scheduling
- Newsletter preview functionality
- Admin dashboard
- Email tracking and analytics

## Requirements

- PHP 8.0 or higher
- SQLite3
- Web server (Apache/Nginx)
- SMTP server for email delivery

## Installation

1. Clone the repository
2. Configure your web server to point to the `public` directory
3. Copy `config/config.example.php` to `config/config.php` and update the settings
4. Run the database migrations: `php scripts/migrate.php`
5. Set up the cron job for newsletter delivery:
   ```bash
   * * * * * php /path/to/project/scripts/send_newsletters.php
   ```

## Directory Structure

```
├── config/             # Configuration files
├── public/            # Public web root
├── src/              # Application source code
│   ├── Controllers/  # Request handlers
│   ├── Models/       # Database models
│   ├── Services/     # Business logic
│   └── Views/        # Templates
├── scripts/          # CLI scripts
└── tests/            # Test files
```

## Security

- Passwords are hashed using PHP's password_hash()
- CSRF protection on all forms
- Input validation and sanitization
- Secure session handling

## License

MIT License