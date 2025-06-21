# MorningNewsletter.com

A powerful SAAS platform for creating and managing custom email newsletters with dynamic content and automated delivery.

## 🚀 Quick Start

1. **Install Dependencies**
   ```bash
   # Ensure PHP 8.0+ and SQLite3 are installed
   php --version
   ```

2. **Setup the Application**
   ```bash
   # Clone and setup
   git clone <repository>
   cd web-morningnewsletter
   
   # The database will be automatically created on first run
   # Make sure the data directory is writable
   mkdir -p data logs
   chmod 755 data logs
   ```

3. **Configure Web Server**
   Point your web server to the project root directory. For development:
   ```bash
   php -S localhost:8000
   ```

4. **Setup Cron Job** (for production)
   ```bash
   # Add to crontab
   */15 * * * * php /path/to/project/cron/send_emails.php
   ```

## 📁 Project Structure

```
morningnewsletter/
├── index.php              # Landing page
├── auth/                  # Authentication system
│   ├── login.php         # User login
│   ├── logout.php        # User logout  
│   ├── register.php      # User registration
│   └── verify_email.php  # Email verification
├── dashboard/             # User dashboard
│   ├── index.php         # Dashboard home
│   ├── settings.php      # Account settings
│   ├── sources.php       # Data source management
│   └── schedule.php      # Email scheduling
├── modules/               # Data source modules
│   ├── bitcoin.php       # Bitcoin price tracking
│   ├── sp500.php         # S&P 500 index tracking
│   ├── weather.php       # Weather updates
│   ├── appstore.php      # App Store Connect sales
│   ├── stripe.php        # Stripe revenue tracking
│   └── news.php          # News headlines
├── core/                  # Core application classes
│   ├── User.php          # User management
│   ├── Auth.php          # Authentication logic
│   ├── NewsletterBuilder.php # Newsletter generation
│   ├── EmailSender.php   # Email delivery
│   ├── Scheduler.php     # Scheduling logic
│   └── SourceModule.php  # Source module interface
├── config/
│   └── database.php      # Database configuration
├── cron/
│   └── send_emails.php   # Email sending cron job
├── templates/
│   └── email_template.php # Email template
├── data/                  # SQLite database storage
├── logs/                  # Application logs
└── assets/               # Static assets
```

## 🗄️ Database Schema

The application uses SQLite with the following tables:

### users
- `id` - Primary key
- `email` - User email (unique)
- `password_hash` - Hashed password
- `plan` - Subscription plan (free, medium, premium)
- `timezone` - User timezone
- `email_verified` - Email verification status
- `send_time` - Preferred newsletter send time
- `verification_token` - Email verification token
- `created_at`, `updated_at` - Timestamps

### sources
- `id` - Primary key
- `user_id` - Foreign key to users
- `type` - Source type (bitcoin, weather, etc.)
- `config` - JSON configuration for the source
- `is_active` - Active status
- `last_result` - Last fetched data
- `last_updated` - Last update timestamp
- `created_at` - Creation timestamp

### email_logs
- `id` - Primary key
- `user_id` - Foreign key to users
- `status` - Delivery status (sent, failed)
- `error_message` - Error details if failed
- `sent_at` - Delivery timestamp

## 🔐 Authentication System

- **Registration**: Email/password with email verification
- **Login**: Session-based authentication
- **Security**: CSRF protection, password hashing, input validation
- **Plan Enforcement**: Source limits based on subscription tier

## 🧩 Data Source Modules

Each source module implements the `SourceModule` interface:

```php
interface SourceModule {
    public function getTitle(): string;
    public function getData(): array;
    public function getConfigFields(): array;
    public function validateConfig(array $config): bool;
}
```

### Available Sources

1. **Bitcoin** - Real-time Bitcoin price from CoinDesk API
2. **S&P 500** - Stock market data (requires Alpha Vantage API key)
3. **Weather** - Weather updates (requires OpenWeatherMap API key)
4. **News** - News headlines (requires NewsAPI key)
5. **Stripe** - Revenue tracking (requires Stripe secret key)
6. **App Store** - Sales data (requires App Store Connect API)

## 📬 Email System

- **Templates**: Responsive HTML email templates
- **Delivery**: PHP mail() for MVP, configurable for SMTP/SES
- **Scheduling**: Timezone-aware delivery system
- **Logging**: Complete delivery tracking and error logging

## 🕒 Scheduling System

- **Cron Job**: Runs every 15 minutes
- **Timezone Support**: Accurate delivery based on user timezone
- **Window System**: 15-minute delivery windows
- **Retry Logic**: Automatic retry on failure

## 💳 Subscription Plans

| Plan | Source Limit | Price | Features |
|------|-------------|-------|----------|
| Free | 1 | $0 | Basic features |
| Medium | 5 | $5/month | Priority support |
| Premium | Unlimited | $10/month | All features, no branding |

## 🛠️ Development

### Testing the System

1. **Health Check**
   ```bash
   php cron/send_emails.php --health-check
   ```

2. **Dry Run**
   ```bash
   php cron/send_emails.php --dry-run
   ```

3. **Force Send**
   ```bash
   php cron/send_emails.php --force-send USER_ID
   ```

4. **Preview Newsletter**
   Navigate to `/preview.php` while logged in

### Adding New Source Modules

1. Create a new file in `modules/` directory
2. Implement the `SourceModule` interface
3. Add the module to the available modules list in `dashboard/sources.php`
4. Update the module class mapping in `NewsletterBuilder.php`

### API Keys Required

Some sources require API keys:
- **Alpha Vantage** (S&P 500): Free tier available
- **OpenWeatherMap** (Weather): Free tier available  
- **NewsAPI** (News): Free tier available
- **Stripe** (Revenue): Your Stripe account
- **App Store Connect** (Sales): Apple Developer account

## 🔧 Configuration

The application is designed to work out of the box with minimal configuration. For production:

1. Set up proper web server (Apache/Nginx)
2. Configure SMTP for email delivery
3. Set up SSL/TLS
4. Configure proper file permissions
5. Set up monitoring and backups

## 📊 Monitoring

- Check logs in `logs/` directory
- Monitor cron job execution
- Track email delivery rates in database
- Monitor API rate limits for external services

## 🚀 Deployment

1. Upload files to web server
2. Configure web server document root
3. Set up cron job for email sending
4. Configure environment variables for API keys
5. Test all functionality

## 📄 License

MIT License - see LICENSE file for details

## 🐛 Troubleshooting

**Database Issues**
- Ensure data directory is writable
- Check SQLite3 extension is installed

**Email Delivery Issues**  
- Check SMTP configuration
- Verify email logs in database
- Test with `--force-send` command

**Cron Job Issues**
- Verify cron job is running
- Check cron logs
- Test with `--health-check`

**API Rate Limits**
- Monitor API usage
- Implement caching if needed
- Use API keys with sufficient quotas