# MorningNewsletter - Comprehensive Technical Documentation

## Project Overview
A modern PHP-based SAAS newsletter platform that generates personalized morning briefs from multiple data sources. Built with professional architecture patterns, security best practices, and comprehensive admin features.

---

## 🏗️ SYSTEM ARCHITECTURE

### Technology Stack
- **Backend**: PHP 8.0+ with object-oriented design
- **Database**: SQLite with normalized schema
- **Frontend**: Tailwind CSS with responsive design
- **Email**: Multi-provider system (Plunk, Resend, SMTP fallback)
- **Payments**: Stripe integration with webhooks
- **Scheduling**: Timezone-aware cron system
- **APIs**: RESTful external data source integrations

### Architecture Pattern
**MVC-Inspired Structure** with clear separation of concerns:
- **Models**: Core business logic classes (`core/`)
- **Views**: Dashboard pages and templates (`dashboard/`, `templates/`)
- **Controllers**: Entry point scripts (`auth/`, `api/`)
- **Modules**: Plugin architecture for data sources (`modules/`)

---

## 📁 FILE STRUCTURE & ORGANIZATION

```
morningnewsletter/
├── index.php                    # Marketing landing page
├── 🔐 auth/                     # Authentication system
│   ├── login.php               # User login with CSRF protection
│   ├── logout.php              # Session cleanup
│   ├── register.php            # Registration with email verification
│   ├── verify_email.php        # Email verification handler
│   ├── forgot_password.php     # Password reset initiation
│   └── reset_password.php      # Password reset completion
├── 📊 dashboard/                # User management interface
│   ├── index.php              # Dashboard overview with stats
│   ├── sources.php            # Dynamic source management
│   ├── settings.php           # Account settings and plan management
│   ├── schedule.php           # Timezone and delivery settings
│   ├── billing.php            # Stripe billing management
│   ├── account.php            # Profile and security settings
│   ├── users.php              # Admin user management
│   └── includes/navigation.php # Shared navigation component
├── 🧩 modules/                  # Data source plugins
│   ├── bitcoin.php            # Cryptocurrency pricing (CoinGecko)
│   ├── weather.php            # Weather data (OpenWeatherMap)
│   ├── stripe.php             # Revenue tracking
│   ├── news.php               # News headlines (NewsAPI)
│   ├── sp500.php              # Stock market data (Alpha Vantage)
│   └── appstore.php           # App Store analytics (placeholder)
├── ⚙️ core/                     # Business logic classes
│   ├── Auth.php               # Authentication & session management
│   ├── User.php               # User lifecycle management
│   ├── NewsletterBuilder.php  # Dynamic content generation
│   ├── EmailSender.php        # Multi-provider email delivery
│   ├── Scheduler.php          # Timezone-aware scheduling
│   ├── SourceModule.php       # Plugin interface definition
│   ├── SubscriptionManager.php # Stripe subscription handling
│   └── BlogPost.php           # File-based blog system
├── 💳 api/                      # Payment processing endpoints
│   ├── stripe-webhook.php     # Stripe event handling
│   ├── fixed-checkout.php     # Checkout session creation
│   ├── billing-portal.php     # Customer portal access
│   └── cancel-subscription.php # Subscription cancellation
├── ⚙️ config/                   # Configuration files
│   ├── database.php           # SQLite connection management
│   ├── email.php              # Email provider settings
│   ├── stripe.php             # Payment configuration
│   └── UserStats.php          # Analytics utilities
├── 📧 templates/                # Email templates
│   └── email_template.php     # Responsive HTML email layout
├── ⏰ cron/                     # Scheduling system
│   └── send_emails.php        # Email delivery engine
├── 📝 blog/                     # Content marketing system
│   ├── index.php              # Blog listing page
│   ├── post.php               # Individual post viewer
│   └── posts/                 # Markdown content files
├── 🎨 assets/                   # Static resources
│   ├── logos/                 # Brand assets
│   └── companies/             # Social proof logos
└── 📄 legal/                    # Legal pages
    ├── privacy.php            # Privacy policy
    └── terms.php              # Terms of service
```

---

## 🗄️ DATABASE SCHEMA

### SQLite Database Structure

#### `users` Table
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    name TEXT,
    password_hash TEXT NOT NULL,
    plan TEXT DEFAULT 'free',           -- free/medium/premium
    timezone TEXT DEFAULT 'UTC',
    send_time TEXT DEFAULT '06:00',
    email_verified INTEGER DEFAULT 0,
    is_admin INTEGER DEFAULT 0,
    verification_token TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

#### `sources` Table
```sql
CREATE TABLE sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,                 -- bitcoin, weather, stripe, etc.
    config TEXT,                        -- JSON configuration
    is_active INTEGER DEFAULT 1,
    last_result TEXT,                   -- Cached API response
    last_updated DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `subscriptions` Table
```sql
CREATE TABLE subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    stripe_subscription_id TEXT UNIQUE,
    stripe_customer_id TEXT,
    plan TEXT NOT NULL,
    status TEXT NOT NULL,
    current_period_start DATETIME,
    current_period_end DATETIME,
    cancel_at_period_end INTEGER DEFAULT 0,
    canceled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `email_logs` Table
```sql
CREATE TABLE email_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    status TEXT NOT NULL,               -- sent/failed
    error_message TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `payments` Table
```sql
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    stripe_payment_intent_id TEXT,
    amount INTEGER NOT NULL,            -- Amount in cents
    currency TEXT DEFAULT 'usd',
    status TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 🔐 AUTHENTICATION & SECURITY SYSTEM

### Authentication Flow
1. **Registration** (`auth/register.php`)
   - Email/password validation
   - Timezone selection
   - Verification token generation
   - Email verification required

2. **Login** (`auth/login.php`)
   - Credential validation
   - Session initialization
   - CSRF token generation
   - Remember me functionality

3. **Email Verification** (`auth/verify_email.php`)
   - Token-based verification
   - Account activation
   - Automatic login post-verification

### Security Features
- **Password Security**: `password_hash()` with default algorithm
- **CSRF Protection**: Tokens on all state-changing forms
- **Session Security**: Secure session configuration
- **Input Validation**: Comprehensive sanitization
- **SQL Injection Prevention**: Prepared statements throughout
- **Email Verification**: Required for account activation
- **Admin Protection**: Role-based access control

### Authorization Levels
- **Free Plan**: 1 data source, basic features
- **Medium Plan**: 5 data sources, $5/month
- **Premium Plan**: Unlimited sources, $10/month
- **Admin Users**: Complete system management

---

## 🧩 SOURCE MODULE SYSTEM

### Plugin Architecture
All data sources implement the `SourceModule` interface:

```php
interface SourceModule {
    public function getTitle(): string;           // Display name
    public function getData(): array;             // Fetch and format data
    public function getConfigFields(): array;     // Configuration form fields
    public function validateConfig(array $config): bool; // Validate settings
}
```

### Available Modules

#### Bitcoin Module (`modules/bitcoin.php`)
- **API**: CoinGecko (free, no key required)
- **Data**: Current price, 24h change, market cap
- **Config**: None required
- **Features**: Price trend indicators, percentage changes

#### Weather Module (`modules/weather.php`)
- **API**: OpenWeatherMap
- **Data**: Current conditions, forecast, sunrise/sunset
- **Config**: API key, city name
- **Features**: Weather emojis, temperature ranges, multiple metrics

#### Stripe Module (`modules/stripe.php`)
- **API**: Stripe API
- **Data**: Revenue, transaction counts, growth metrics
- **Config**: Secret API key
- **Features**: Revenue analytics, payment tracking

#### News Module (`modules/news.php`)
- **API**: NewsAPI
- **Data**: Headlines, summaries, sources
- **Config**: API key, country, category, article limit
- **Features**: Categorized news, source filtering

#### Stock Market Module (`modules/sp500.php`)
- **API**: Alpha Vantage
- **Data**: S&P 500 index, daily changes
- **Config**: API key
- **Features**: Market trend analysis

### Data Flow Process
1. **Cron Trigger**: `cron/send_emails.php` runs every 15 minutes
2. **User Selection**: `Scheduler` identifies users in delivery window
3. **Source Fetching**: `NewsletterBuilder` calls each active source
4. **Data Processing**: Modules fetch and format external API data
5. **Template Rendering**: Data inserted into HTML email template
6. **Email Delivery**: `EmailSender` delivers via preferred provider

---

## 📧 EMAIL SYSTEM ARCHITECTURE

### Multi-Provider Email Delivery
**Provider Hierarchy** (with automatic failover):
1. **Plunk API** (Primary) - Modern transactional email service
2. **Resend API** (Secondary) - Developer-focused email platform  
3. **PHP mail()** (Fallback) - Basic SMTP delivery

### Email Types
- **Newsletter Delivery**: Daily personalized content
- **Email Verification**: Account activation
- **Password Reset**: Secure password recovery
- **Admin Notifications**: System alerts

### Template System
- **Responsive Design**: Mobile-optimized HTML
- **Dark Mode Support**: CSS media query adaptation
- **Dynamic Content**: Placeholder-based insertion
- **Brand Consistency**: Professional styling with gradients

### Delivery Features
- **Timezone Awareness**: Accurate local delivery timing
- **Duplicate Prevention**: One email per day per user
- **Error Logging**: Comprehensive delivery tracking
- **Provider Failover**: Automatic backup email delivery

---

## ⏰ SCHEDULING SYSTEM

### Cron Job Architecture (`cron/send_emails.php`)
**Execution Modes**:
- **Normal**: `php cron/send_emails.php`
- **Health Check**: `--health-check` (system diagnostics)
- **Dry Run**: `--dry-run` (preview without sending)
- **Force Send**: `--force-send USER_ID` (test specific user)

### Scheduling Algorithm
1. **Time Window Calculation**: 15-minute delivery intervals
2. **Timezone Conversion**: User timezone → server timezone
3. **User Selection**: Find users in current delivery window
4. **Duplicate Prevention**: Check last send date
5. **Email Generation**: Build personalized newsletters
6. **Delivery Execution**: Send via email providers
7. **Status Logging**: Record success/failure

### Configuration
- **Frequency**: Every 15 minutes via cron
- **Delivery Windows**: User-configurable send times
- **Timezone Support**: 400+ timezone identifiers
- **Error Handling**: Retry logic with exponential backoff

---

## 💳 PAYMENT SYSTEM (STRIPE INTEGRATION)

### Subscription Plans
- **Free**: $0/month, 1 data source
- **Medium**: $5/month, 5 data sources
- **Premium**: $10/month, unlimited sources

### Payment Flow
1. **Plan Selection**: User chooses subscription tier
2. **Checkout Creation**: Stripe Checkout session via `api/fixed-checkout.php`
3. **Payment Processing**: Stripe handles payment securely
4. **Webhook Processing**: `api/stripe-webhook.php` receives events
5. **Account Update**: `SubscriptionManager` updates user plan
6. **Access Grant**: User gains additional source limits

### Stripe Features
- **Subscription Management**: Automatic recurring billing
- **Customer Portal**: Self-service billing management
- **Webhook Security**: Signature verification
- **Trial Periods**: 7-day free trials
- **Cancellation**: Immediate or end-of-period
- **Payment History**: Complete transaction tracking

---

## 👨‍💼 ADMIN FEATURES

### User Management (`dashboard/users.php`)
- **User Overview**: Complete user listing with statistics
- **Plan Management**: Promote/demote users between plans
- **Admin Privileges**: Grant/revoke admin access
- **Account Actions**: Delete users with data cleanup
- **Search & Filter**: Find users by email, plan, status

### System Administration
- **Plan Distribution**: Visual analytics of user plans
- **Source Limits**: Real-time usage monitoring
- **Email Statistics**: Delivery success/failure rates
- **Health Monitoring**: System diagnostics and alerts

### Admin Capabilities
- **Plan Changes**: Override subscription plans
- **Source Limits**: Bypass normal restrictions
- **User Impersonation**: Test user experience
- **System Maintenance**: Health checks and diagnostics

---

## 🔄 KEY WORKFLOWS

### User Registration → First Newsletter
1. User visits landing page
2. Registers with email/password/timezone
3. Receives verification email
4. Clicks verification link
5. Logs into dashboard
6. Adds first data source (e.g., weather)
7. Configures source (API key, city)
8. Waits for next delivery window
9. Receives personalized newsletter

### Newsletter Generation Process
1. **Cron Execution**: Every 15 minutes
2. **User Query**: Find users in delivery window
3. **Source Processing**: For each active source:
   - Load configuration from database
   - Call external API
   - Process and format data
   - Handle errors gracefully
4. **Template Rendering**: Insert data into HTML template
5. **Email Delivery**: Send via preferred provider
6. **Logging**: Record delivery status

### Payment Processing Workflow
1. **Upgrade Request**: User selects premium plan
2. **Stripe Session**: Create checkout session
3. **Payment**: User completes payment on Stripe
4. **Webhook**: Stripe sends completion event
5. **Verification**: Validate webhook signature
6. **Update**: Modify user plan in database
7. **Notification**: Confirm upgrade to user

---

## 🛠️ DEVELOPMENT & TESTING

### Testing Tools
- **Health Check**: `php cron/send_emails.php --health-check`
- **Dry Run**: Preview newsletter generation without sending
- **Force Send**: Test specific user delivery
- **Preview Page**: Real-time newsletter preview in browser
- **Error Logging**: Comprehensive debug information

### Development Workflow
1. **Local Setup**: SQLite database auto-initialization
2. **Source Development**: Implement `SourceModule` interface
3. **Testing**: Use dry run and force send modes
4. **Configuration**: Add to `$availableModules` array
5. **Deployment**: Update cron job and dependencies

---

## 🚀 DEPLOYMENT & PRODUCTION

### Server Requirements
- **PHP**: 8.0+ with SQLite3 extension
- **Web Server**: Apache/Nginx with mod_rewrite
- **Cron**: System cron access for scheduling
- **SSL**: HTTPS certificate for security
- **File Permissions**: Writable directories for database/logs

### Environment Configuration
- **Database**: SQLite file with proper permissions
- **API Keys**: Environment variables for external services
- **Email Providers**: Configure primary/backup providers
- **Stripe**: Webhook endpoints and secret keys
- **Timezone**: Server timezone configuration

### Monitoring & Maintenance
- **Error Logging**: Monitor application logs
- **Email Delivery**: Track success/failure rates
- **API Quotas**: Monitor external service usage
- **Database Growth**: Periodic cleanup of old logs
- **Security Updates**: Regular PHP and dependency updates

---

## 🔧 REFACTORING OPPORTUNITIES

### Performance Optimizations
1. **API Caching**: Cache external API responses
2. **Database Indexing**: Add indexes for common queries
3. **Email Queuing**: Implement background job processing
4. **CDN Integration**: Serve static assets via CDN

### Architecture Improvements
1. **Framework Migration**: Consider Laravel/Symfony migration
2. **Microservices**: Split email/payment/user services
3. **Docker Containerization**: Improve deployment consistency
4. **Database Migration**: PostgreSQL for better scalability

### Feature Enhancements
1. **Analytics Dashboard**: User engagement metrics
2. **A/B Testing**: Newsletter template variants
3. **Mobile App**: Native mobile application
4. **API Access**: RESTful API for third-party integrations

### Security Hardening
1. **Rate Limiting**: Prevent abuse of registration/login
2. **2FA Implementation**: Two-factor authentication
3. **Audit Logging**: Track all admin actions
4. **GDPR Compliance**: Data privacy and export features

---

## 📊 METRICS & ANALYTICS

### User Metrics
- **Registration Rate**: New signups per day
- **Email Verification**: Activation completion rate
- **Plan Conversion**: Free to paid upgrade rate
- **Churn Rate**: Subscription cancellation rate

### System Metrics
- **Email Delivery**: Success/failure rates per provider
- **API Performance**: External service response times
- **Source Usage**: Most popular data sources
- **Error Rates**: Application error frequency

### Business Metrics
- **Revenue**: Monthly recurring revenue (MRR)
- **Customer Lifetime Value**: Average user value
- **Source Adoption**: Data source usage patterns
- **Support Tickets**: Customer service volume

---

## 🏁 CONCLUSION

This is a professionally architected SAAS newsletter platform with:
- **Scalable Architecture**: Plugin-based source system
- **Security Best Practices**: CSRF, password hashing, input validation
- **Reliable Email Delivery**: Multi-provider failover system
- **Comprehensive Admin Tools**: User and plan management
- **Payment Integration**: Stripe subscription handling
- **Production Ready**: Error handling, logging, monitoring

The codebase demonstrates modern PHP development practices with clean architecture, security consciousness, and user experience focus. It's well-positioned for scaling and feature enhancement while maintaining reliability and performance.

---

**Last Updated**: January 2025 - Comprehensive technical documentation with enhanced admin features and weather module improvements