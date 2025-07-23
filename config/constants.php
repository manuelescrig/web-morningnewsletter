<?php
/**
 * Application Constants Configuration
 * 
 * This file contains configurable constants that can be easily adjusted
 * without modifying the core application code.
 */

// Newsletter Configuration
define('MAX_DAILY_TIMES', 4); // Maximum number of times a newsletter can be sent per day
define('MAX_NEWSLETTERS_PER_USER', 10); // Maximum number of newsletters per user

// Time Configuration
define('DEFAULT_SEND_TIME', '06:00'); // Default newsletter send time
define('DEFAULT_TIMEZONE', 'UTC'); // Default timezone for new users

// Email Configuration
define('EMAIL_FROM_NAME', 'MorningNewsletter');
define('EMAIL_FROM_ADDRESS', 'noreply@morningnewsletter.com');
define('EMAIL_REPLY_TO', 'support@morningnewsletter.com');

// Plan Limits
define('FREE_PLAN_SOURCE_LIMIT', 1);
define('STARTER_PLAN_SOURCE_LIMIT', 5);
define('PRO_PLAN_SOURCE_LIMIT', 15);
define('UNLIMITED_PLAN_SOURCE_LIMIT', PHP_INT_MAX);

// Registration Configuration
define('REQUIRE_EMAIL_VERIFICATION', true);
define('REGISTRATION_RATE_LIMIT', 5); // Max registrations per IP per 5 minutes
define('REGISTRATION_RATE_WINDOW', 300); // 5 minutes in seconds

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours
define('REMEMBER_ME_DURATION', 2592000); // 30 days

// API Rate Limits
define('API_RATE_LIMIT_PER_MINUTE', 60);
define('API_RATE_LIMIT_PER_HOUR', 1000);

// File Upload Limits
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour
define('CACHE_DIRECTORY', __DIR__ . '/../cache');

// Debug Mode
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', __DIR__ . '/../logs/error.log');
?>