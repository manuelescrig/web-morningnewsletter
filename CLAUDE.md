# MorningNewsletter - Claude Development Notes

## Project Overview
A PHP-based SAAS newsletter platform that generates personalized morning briefs from multiple data sources.

## Current Status: ✅ COMPLETE BACKEND IMPLEMENTATION

### Architecture Completed
- **Tech Stack**: PHP 8.0+, SQLite, modular architecture
- **Authentication**: Session-based with email verification
- **Database**: SQLite with proper schema (users, sources, email_logs)
- **Security**: CSRF protection, password hashing, input validation
- **Email System**: HTML templates with timezone-aware delivery

### File Structure Implemented
```
morningnewsletter/
├── index.php                 # Landing page (updated with auth links)
├── auth/                     # Complete authentication system
│   ├── login.php            # User login
│   ├── logout.php           # User logout
│   ├── register.php         # Registration with timezone selection
│   └── verify_email.php     # Email verification handler
├── dashboard/                # Full dashboard implementation
│   ├── index.php           # Dashboard with stats and quick actions
│   ├── settings.php        # Plan management and account settings
│   ├── sources.php         # Dynamic source configuration
│   └── schedule.php        # Timezone and send time management
├── modules/                  # Complete source module system
│   ├── bitcoin.php         # Bitcoin price (CoinDesk API)
│   ├── sp500.php           # S&P 500 (Alpha Vantage API)
│   ├── weather.php         # Weather (OpenWeatherMap API)
│   ├── appstore.php        # App Store sales (needs implementation)
│   ├── stripe.php          # Stripe revenue tracking
│   └── news.php            # News headlines (NewsAPI)
├── core/                     # Core business logic
│   ├── User.php            # User management with plan enforcement
│   ├── Auth.php            # Authentication logic
│   ├── NewsletterBuilder.php # Dynamic newsletter generation
│   ├── EmailSender.php     # Email delivery with logging
│   ├── Scheduler.php       # Timezone-aware scheduling
│   └── SourceModule.php    # Module interface and base class
├── config/
│   └── database.php        # SQLite database setup
├── cron/
│   ├── send_emails.php     # Production cron job with testing tools
│   └── README.md           # Cron setup instructions
├── templates/
│   └── email_template.php  # Responsive HTML email template
├── preview.php             # Newsletter preview functionality
└── README.md               # Complete documentation
```

### Database Schema (SQLite)
- **users**: id, email, password_hash, plan, timezone, email_verified, send_time, verification_token
- **sources**: id, user_id, type, config (JSON), is_active, last_result, last_updated
- **email_logs**: id, user_id, status, error_message, sent_at

### Features Implemented

#### Authentication System ✅
- Email/password registration with verification
- Session-based login/logout
- CSRF protection throughout
- Password hashing with PHP's password_hash()
- Email verification with tokens

#### Subscription Plans ✅
- **Free**: 1 source, $0/month
- **Medium**: 5 sources, $5/month  
- **Premium**: Unlimited sources, $10/month
- Plan enforcement in source addition

#### Data Source Modules ✅
All modules implement `SourceModule` interface:
- `getTitle()`, `getData()`, `getConfigFields()`, `validateConfig()`
- **Bitcoin**: Real-time price from CoinDesk (no config needed)
- **S&P 500**: Stock data via Alpha Vantage API (requires API key)
- **Weather**: Local weather via OpenWeatherMap (requires API key + city)
- **News**: Headlines via NewsAPI (requires API key, country, category, limit)
- **Stripe**: Revenue tracking (requires secret key)
- **App Store**: Placeholder for App Store Connect integration

#### Dashboard Features ✅
- **Main Dashboard**: Stats overview, quick actions, source list
- **Sources Management**: Add/remove sources with dynamic config forms
- **Schedule Settings**: Timezone selection, send time configuration
- **Account Settings**: Plan management, email stats, danger zone

#### Email System ✅
- **Newsletter Builder**: Dynamic content generation from sources
- **Email Template**: Responsive HTML with mobile support
- **Email Sender**: Delivery with logging and error handling
- **Preview**: Live newsletter preview for logged-in users

#### Scheduling System ✅
- **Cron Job**: Runs every 15 minutes with multiple modes:
  - Normal operation: Send scheduled newsletters
  - `--health-check`: System diagnostics
  - `--dry-run`: Preview what would be sent
  - `--force-send USER_ID`: Test specific user
- **Timezone Support**: Accurate delivery based on user timezone
- **Error Handling**: Comprehensive logging and retry logic

### API Integrations Required
To use external data sources, users need API keys:
- **Alpha Vantage** (S&P 500): Free tier available
- **OpenWeatherMap** (Weather): Free tier available
- **NewsAPI** (News): Free tier available
- **Stripe** (Revenue): User's Stripe account
- **App Store Connect** (Sales): Apple Developer account (not fully implemented)

### Security Measures ✅
- CSRF tokens on all forms
- Password hashing with `password_hash()`
- Input validation and sanitization
- SQL injection prevention with prepared statements
- Session security best practices
- Email verification required for login

### Testing & Development Tools ✅
- Health check system for diagnostics
- Dry run mode for testing without sending
- Force send for individual user testing
- Newsletter preview functionality
- Comprehensive error logging

### Production Readiness ✅
- Proper error handling and logging
- Database auto-initialization
- File permission setup
- Cron job documentation
- Deployment instructions
- Troubleshooting guide

## Commands for Testing

```bash
# Health check
php cron/send_emails.php --health-check

# Dry run (see what would be sent)
php cron/send_emails.php --dry-run

# Force send to specific user
php cron/send_emails.php --force-send USER_ID

# Normal operation
php cron/send_emails.php
```

## Deployment Requirements

1. **Server**: PHP 8.0+, SQLite3 extension
2. **Web Server**: Apache/Nginx pointing to project root
3. **Cron Job**: `*/15 * * * * php /path/to/cron/send_emails.php`
4. **Permissions**: Writable data/ and logs/ directories
5. **API Keys**: Configure external service APIs as needed

## Known Limitations & Future Enhancements

1. **Email Delivery**: Currently uses PHP mail() - should upgrade to PHPMailer/SES for production
2. **App Store Module**: Needs full App Store Connect API implementation
3. **Payment Processing**: Stripe subscription management not implemented
4. **Data Caching**: No caching for API responses (consider for rate limits)
5. **User Management**: No admin interface for user management
6. **Analytics**: No newsletter open/click tracking

## Last Updated
June 2025 - Full backend implementation complete, ready for production deployment